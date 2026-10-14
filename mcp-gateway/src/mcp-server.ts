import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
  ErrorCode,
  McpError,
} from '@modelcontextprotocol/sdk/types.js';
import { AuthenticatedClientContext, getTargetPool, recordAuditLog } from './db.js';
import { validateAndSanitizeQuery, applyDataMasking } from './policy.js';

/**
 * Creates and configures an MCP Server for an authenticated client session
 */
export function createMcpServer(context: AuthenticatedClientContext, clientIp: string = '127.0.0.1'): Server {
  const server = new Server(
    {
      name: `shifr-shield-gateway-${context.dbName}`,
      version: '0.1.0',
    },
    {
      capabilities: {
        tools: {},
      },
    }
  );

  // 1. ListTools Request Handler
  server.setRequestHandler(ListToolsRequestSchema, async () => {
    return {
      tools: [
        {
          name: 'get_schema_metadata',
          description:
            'Retrieve available schema tables and column information permitted by the security whitelist policy.',
          inputSchema: {
            type: 'object',
            properties: {
              table_name: {
                type: 'string',
                description: 'Optional specific table name to inspect. Omit to list all whitelisted tables.',
              },
            },
          },
        },
        {
          name: 'run_readonly_query',
          description:
            'Safely execute a read-only SQL query against the database. Automatically enforces table whitelisting, blocks mutation commands (INSERT/UPDATE/DELETE/DROP), injects LIMIT limits, and applies data masking on sensitive columns.',
          inputSchema: {
            type: 'object',
            properties: {
              query: {
                type: 'string',
                description: 'The read-only SQL query to execute (must start with SELECT or WITH).',
              },
            },
            required: ['query'],
          },
        },
      ],
    };
  });

  // 2. CallTool Request Handler
  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;
    const pool = getTargetPool(context);

    // ---------------- TOOL 1: get_schema_metadata ----------------
    if (name === 'get_schema_metadata') {
      const specificTable = (args as any)?.table_name?.trim();
      const whitelist = context.policy.whitelistTables;

      try {
        let query = `
          SELECT table_name, column_name, data_type, is_nullable
          FROM information_schema.columns
          WHERE table_schema = 'public'
        `;
        const params: any[] = [];

        if (specificTable) {
          // If whitelist active, check if requested table is whitelisted
          if (context.policy.isActive && whitelist.length > 0) {
            if (!whitelist.map((t) => t.toLowerCase()).includes(specificTable.toLowerCase())) {
              throw new McpError(
                ErrorCode.InvalidRequest,
                `Access Denied: Table '${specificTable}' is not in the allowed whitelist.`
              );
            }
          }
          query += ` AND table_name = $1`;
          params.push(specificTable);
        } else if (context.policy.isActive && whitelist.length > 0) {
          query += ` AND table_name = ANY($1)`;
          params.push(whitelist);
        }

        query += ` ORDER BY table_name, ordinal_position;`;

        const res = await pool.query(query, params);

        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(
                {
                  database: context.dbName,
                  whitelisted_tables: context.policy.whitelistTables,
                  schema: res.rows,
                },
                null,
                2
              ),
            },
          ],
        };
      } catch (err: any) {
        if (err instanceof McpError) throw err;
        throw new McpError(ErrorCode.InternalError, `Failed to retrieve schema: ${err.message}`);
      }
    }

    // ---------------- TOOL 2: run_readonly_query ----------------
    if (name === 'run_readonly_query') {
      const rawQuery = (args as any)?.query;
      if (!rawQuery || typeof rawQuery !== 'string') {
        throw new McpError(ErrorCode.InvalidParams, 'Parameter "query" is required and must be a string');
      }

      const startTime = performance.now();

      // Policy validation & limit injection
      const validation = validateAndSanitizeQuery(rawQuery, context.policy);

      if (!validation.valid) {
        const duration = parseFloat((performance.now() - startTime).toFixed(2));
        // Record blocked audit log
        await recordAuditLog({
          databaseSourceId: context.databaseSourceId,
          apiKeyId: context.apiKeyId,
          status: 'blocked',
          queryRaw: rawQuery,
          querySanitized: null,
          reason: validation.reason,
          durationMs: duration,
          rowsReturned: 0,
          clientIp,
        });

        return {
          isError: true,
          content: [
            {
              type: 'text',
              text: `[SHIFR SHIELD SECURITY BLOCKED]: ${validation.reason}`,
            },
          ],
        };
      }

      const sanitizedQuery = validation.sanitizedQuery!;

      try {
        // Execute sanitized query on target database
        const dbResult = await pool.query(sanitizedQuery);
        const duration = parseFloat((performance.now() - startTime).toFixed(2));

        // Apply data masking
        const { maskedRows, maskedCount } = applyDataMasking(dbResult.rows, context.policy.maskedColumns);

        const reason =
          maskedCount > 0
            ? `Executed successfully with automatic limit injection and data redaction on ${maskedCount} column value(s).`
            : `Executed successfully with automatic limit injection.`;

        // Record success audit log
        await recordAuditLog({
          databaseSourceId: context.databaseSourceId,
          apiKeyId: context.apiKeyId,
          status: 'success',
          queryRaw: rawQuery,
          querySanitized: sanitizedQuery,
          reason,
          durationMs: duration,
          rowsReturned: maskedRows.length,
          clientIp,
        });

        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(
                {
                  status: 'success',
                  rows_returned: maskedRows.length,
                  execution_duration_ms: duration,
                  masked_columns_protected: maskedCount > 0,
                  data: maskedRows,
                },
                null,
                2
              ),
            },
          ],
        };
      } catch (dbErr: any) {
        const duration = parseFloat((performance.now() - startTime).toFixed(2));
        const errMsg = dbErr.message || 'Database query execution failed';

        // Record blocked / execution failure audit log
        await recordAuditLog({
          databaseSourceId: context.databaseSourceId,
          apiKeyId: context.apiKeyId,
          status: 'blocked',
          queryRaw: rawQuery,
          querySanitized: sanitizedQuery,
          reason: `SQL Execution Error: ${errMsg}`,
          durationMs: duration,
          rowsReturned: 0,
          clientIp,
        });

        return {
          isError: true,
          content: [
            {
              type: 'text',
              text: `[DATABASE ERROR]: ${errMsg}`,
            },
          ],
        };
      }
    }

    throw new McpError(ErrorCode.MethodNotFound, `Tool '${name}' not recognized`);
  });

  return server;
}

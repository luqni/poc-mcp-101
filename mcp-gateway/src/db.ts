import pg from 'pg';
import { decryptLaravelString, hashToken } from './crypto.js';
import { SecurityPolicyConfig, MaskRule } from './policy.js';

const { Pool } = pg;

export interface AuthenticatedClientContext {
  apiKeyId: number;
  apiKeyName: string;
  databaseSourceId: number;
  dbName: string;
  targetDb: {
    host: string;
    port: number;
    database: string;
    username: string;
    password: string;
    ssl: boolean;
  };
  policy: SecurityPolicyConfig;
}

// Control plane connection to Shifr Shield Postgres database
const controlPlanePool = new Pool({
  host: process.env.SHIFR_DB_HOST || '127.0.0.1',
  port: parseInt(process.env.SHIFR_DB_PORT || '5432', 10),
  database: process.env.SHIFR_DB_NAME || 'shifr_shield',
  user: process.env.SHIFR_DB_USER || 'postgres',
  password: process.env.SHIFR_DB_PASSWORD || 'P@ssw0rd',
});

// Cache for target database connection pools
const targetPools = new Map<number, pg.Pool>();

/**
 * Authenticate raw Bearer token against api_keys table
 */
export async function authenticateApiKey(rawToken: string): Promise<AuthenticatedClientContext | null> {
  const tokenHash = hashToken(rawToken);
  const appKey = process.env.APP_KEY || 'base64:UL/uXWWYcM13SsElMcSamVtGD4RYboJMBhpPGN03I1Q=';

  const query = `
    SELECT 
      ak.id as api_key_id,
      ak.name as api_key_name,
      ak.is_active as api_key_active,
      ak.database_source_id,
      ds.name as db_name,
      ds.host as db_host,
      ds.port as db_port,
      ds.database as db_database,
      ds.username as db_username,
      ds.password as db_password,
      ds.ssl as db_ssl,
      sp.whitelist_tables,
      sp.masked_columns,
      sp.max_query_limit,
      sp.is_active as policy_active
    FROM api_keys ak
    JOIN database_sources ds ON ds.id = ak.database_source_id
    LEFT JOIN security_policies sp ON sp.database_source_id = ds.id
    WHERE ak.key_hash = $1
    LIMIT 1;
  `;

  try {
    const res = await controlPlanePool.query(query, [tokenHash]);
    if (res.rows.length === 0) {
      return null;
    }

    const row = res.rows[0];
    if (!row.api_key_active) {
      return null;
    }

    // Update last_used_at timestamp
    controlPlanePool.query('UPDATE api_keys SET last_used_at = NOW() WHERE id = $1', [row.api_key_id]).catch(() => {});

    // Decrypt target database password
    const decryptedPassword = decryptLaravelString(row.db_password, appKey);

    // Parse policy
    let whitelistTables: string[] = [];
    if (row.whitelist_tables) {
      whitelistTables = typeof row.whitelist_tables === 'string' 
        ? JSON.parse(row.whitelist_tables) 
        : row.whitelist_tables;
    }

    let maskedColumns: MaskRule[] = [];
    if (row.masked_columns) {
      maskedColumns = typeof row.masked_columns === 'string'
        ? JSON.parse(row.masked_columns)
        : row.masked_columns;
    }

    return {
      apiKeyId: row.api_key_id,
      apiKeyName: row.api_key_name,
      databaseSourceId: row.database_source_id,
      dbName: row.db_name,
      targetDb: {
        host: row.db_host,
        port: row.db_port,
        database: row.db_database,
        username: row.db_username,
        password: decryptedPassword,
        ssl: Boolean(row.db_ssl),
      },
      policy: {
        whitelistTables,
        maskedColumns,
        maxQueryLimit: row.max_query_limit || 50,
        isActive: row.policy_active !== false,
      },
    };
  } catch (err) {
    console.error('Failed to authenticate token against DB:', err);
    return null;
  }
}

/**
 * Get or create connection pool for the target database
 */
export function getTargetPool(context: AuthenticatedClientContext): pg.Pool {
  const dbId = context.databaseSourceId;
  if (!targetPools.has(dbId)) {
    const pool = new Pool({
      host: context.targetDb.host,
      port: context.targetDb.port,
      database: context.targetDb.database,
      user: context.targetDb.username,
      password: context.targetDb.password,
      ssl: context.targetDb.ssl ? { rejectUnauthorized: false } : undefined,
      max: 10,
      idleTimeoutMillis: 30000,
    });
    targetPools.set(dbId, pool);
  }
  return targetPools.get(dbId)!;
}

/**
 * Write query audit log entry to gateway_audit_logs table
 */
export async function recordAuditLog(params: {
  databaseSourceId: number;
  apiKeyId: number;
  status: 'success' | 'blocked';
  queryRaw: string;
  querySanitized?: string | null;
  reason?: string | null;
  durationMs: number;
  rowsReturned: number;
  clientIp?: string;
}): Promise<void> {
  const insertQuery = `
    INSERT INTO gateway_audit_logs (
      database_source_id,
      api_key_id,
      status,
      query_raw,
      query_sanitized,
      reason,
      duration_ms,
      rows_returned,
      client_ip,
      created_at,
      updated_at
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, NOW(), NOW());
  `;

  try {
    await controlPlanePool.query(insertQuery, [
      params.databaseSourceId,
      params.apiKeyId,
      params.status,
      params.queryRaw,
      params.querySanitized || null,
      params.reason || null,
      params.durationMs,
      params.rowsReturned,
      params.clientIp || '127.0.0.1',
    ]);
  } catch (err) {
    console.error('Failed to write audit log to database:', err);
  }
}

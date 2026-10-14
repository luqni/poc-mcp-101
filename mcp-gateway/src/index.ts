import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { SSEServerTransport } from '@modelcontextprotocol/sdk/server/sse.js';
import { authenticateApiKey } from './db.js';
import { createMcpServer } from './mcp-server.js';

dotenv.config();

const app = express();
const PORT = parseInt(process.env.PORT || '3000', 10);

app.use(cors());

// In-memory store for active SSE client transports
const activeTransports = new Map<string, { transport: SSEServerTransport; apiKeyName: string }>();

/**
 * Health check endpoint
 */
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'Shifr Shield Managed MCP Gateway',
    version: '0.1.0-alpha',
    active_sse_sessions: activeTransports.size,
    timestamp: new Date().toISOString(),
  });
});

/**
 * SSE Transport Endpoint: /sse
 * Accepts Bearer Token in Authorization header or ?token= query parameter.
 */
app.get('/sse', async (req, res) => {
  // Extract Bearer token
  let token: string | undefined;
  const authHeader = req.headers.authorization;

  if (authHeader && authHeader.startsWith('Bearer ')) {
    token = authHeader.substring(7).trim();
  } else if (req.query.token && typeof req.query.token === 'string') {
    token = req.query.token.trim();
  } else if (req.query.apiKey && typeof req.query.apiKey === 'string') {
    token = req.query.apiKey.trim();
  }

  if (!token) {
    res.status(401).json({
      error: 'Unauthorized',
      message: 'Missing Bearer token. Please provide Authorization: Bearer <shifr_live_***> or ?token=<shifr_live_***>',
    });
    return;
  }

  // Validate API key against database
  const context = await authenticateApiKey(token);
  if (!context) {
    res.status(401).json({
      error: 'Unauthorized',
      message: 'Invalid or revoked API Key',
    });
    return;
  }

  const clientIp = (req.headers['x-forwarded-for'] as string) || req.socket.remoteAddress || '127.0.0.1';

  console.log(`[SSE Connected] Client: "${context.apiKeyName}" -> DB: "${context.dbName}" (IP: ${clientIp})`);

  // Initialize SSE Server Transport
  const transport = new SSEServerTransport('/messages', res);
  const mcpServer = createMcpServer(context, clientIp);

  await mcpServer.connect(transport);

  const sessionId = transport.sessionId;
  activeTransports.set(sessionId, { transport, apiKeyName: context.apiKeyName });

  req.on('close', () => {
    console.log(`[SSE Disconnected] Session: ${sessionId} (Client: "${context.apiKeyName}")`);
    activeTransports.delete(sessionId);
  });
});

/**
 * Messages Endpoint: POST /messages
 * Handles JSON-RPC requests from MCP client
 */
app.post('/messages', async (req, res) => {
  const sessionId = req.query.sessionId as string;

  if (!sessionId) {
    res.status(400).json({ error: 'Missing sessionId query parameter' });
    return;
  }

  const entry = activeTransports.get(sessionId);
  if (!entry) {
    res.status(404).json({ error: `Session '${sessionId}' not found or expired` });
    return;
  }

  await entry.transport.handlePostMessage(req, res);
});

// Start listening
app.listen(PORT, '0.0.0.0', () => {
  console.log(`=======================================================`);
  console.log(`🛡️  SHIFR SHIELD MCP DATA GATEWAY (Alpha Internal)`);
  console.log(`🚀 SSE Endpoint: http://localhost:${PORT}/sse`);
  console.log(`📡 Health Check: http://localhost:${PORT}/health`);
  console.log(`=======================================================`);
});

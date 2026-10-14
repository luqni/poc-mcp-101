import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { SSEClientTransport } from '@modelcontextprotocol/sdk/client/sse.js';
import pg from 'pg';

const { Pool } = pg;

async function runTests() {
  console.log('--- Starting Shifr Shield MCP Gateway Test Suite ---');

  const pool = new Pool({
    host: '127.0.0.1',
    port: 5432,
    database: 'shifr_shield',
    user: 'postgres',
    password: 'P@ssw0rd',
  });

  // 1. Test Unauthenticated Request
  console.log('1. Testing unauthenticated /sse request...');
  const unauthRes = await fetch('http://localhost:3000/sse');
  console.log('   Unauth HTTP Status:', unauthRes.status, '(expected 401)');
  if (unauthRes.status !== 401) {
    throw new Error('Expected 401 for unauthenticated request');
  }

  // 2. Test Invalid Token
  console.log('2. Testing invalid token request...');
  const invalidRes = await fetch('http://localhost:3000/sse', {
    headers: { Authorization: 'Bearer shifr_live_invalidtoken123' },
  });
  console.log('   Invalid Token HTTP Status:', invalidRes.status, '(expected 401)');
  if (invalidRes.status !== 401) {
    throw new Error('Expected 401 for invalid token');
  }

  // 3. Connect via MCP Client with valid token
  console.log('3. Connecting MCP client via SSE with valid token...');
  const validToken = 'shifr_live_claudedesktoptestkey2026alpha';
  const transport = new SSEClientTransport(
    new URL(`http://localhost:3000/sse?token=${validToken}`)
  );

  const client = new Client(
    { name: 'test-runner', version: '1.0.0' },
    { capabilities: {} }
  );

  await client.connect(transport);
  console.log('   ✅ MCP Client successfully connected via SSE!');

  // 4. List Tools
  console.log('4. Testing ListTools...');
  const tools = await client.listTools();
  console.log('   Available tools:', tools.tools.map(t => t.name));

  // 5. Call get_schema_metadata
  console.log('5. Calling get_schema_metadata...');
  const schemaResult = await client.callTool({
    name: 'get_schema_metadata',
    arguments: {},
  });
  console.log('   get_schema_metadata output:', (schemaResult.content[0] as any).text.slice(0, 150) + '...');

  // 6. Call run_readonly_query with SELECT (should be masked)
  console.log('6. Calling run_readonly_query: SELECT * FROM demo_customers...');
  const queryResult = await client.callTool({
    name: 'run_readonly_query',
    arguments: { query: 'SELECT * FROM demo_customers' },
  });
  const parsedData = JSON.parse((queryResult.content[0] as any).text);
  console.log('   Query Status:', parsedData.status);
  console.log('   Rows Returned:', parsedData.rows_returned);
  console.log('   Sample Masked Row:', parsedData.data[0]);

  if (parsedData.data[0].credit_card !== '[REDACTED]') {
    throw new Error(`Expected credit_card to be [REDACTED], got: ${parsedData.data[0].credit_card}`);
  }
  if (!parsedData.data[0].email.includes('***')) {
    throw new Error(`Expected email to be partially masked, got: ${parsedData.data[0].email}`);
  }
  console.log('   ✅ Data Masking (Full Redact + Partial Mask) verified!');

  // 7. Call run_readonly_query with mutation DROP TABLE
  console.log('7. Calling run_readonly_query with mutation: DROP TABLE demo_customers...');
  const dropResult = await client.callTool({
    name: 'run_readonly_query',
    arguments: { query: 'DROP TABLE demo_customers' },
  });
  console.log('   Drop Result text:', (dropResult.content[0] as any).text);
  if (!(dropResult.content[0] as any).text.includes('BLOCKED')) {
    throw new Error('Expected DROP TABLE query to be blocked!');
  }
  console.log('   ✅ Mutation prevention verified!');

  // 8. Call run_readonly_query with non-whitelisted table
  console.log('8. Calling run_readonly_query with non-whitelisted table: SELECT * FROM pg_database...');
  const nonWhiteResult = await client.callTool({
    name: 'run_readonly_query',
    arguments: { query: 'SELECT * FROM pg_database' },
  });
  console.log('   Non-whitelist Result text:', (nonWhiteResult.content[0] as any).text);
  if (!(nonWhiteResult.content[0] as any).text.includes('BLOCKED')) {
    throw new Error('Expected non-whitelisted table to be blocked!');
  }
  console.log('   ✅ Table whitelisting verified!');

  // 9. Verify gateway_audit_logs recorded in database
  console.log('9. Checking gateway_audit_logs in PostgreSQL...');
  const logRes = await pool.query('SELECT status, query_raw, reason FROM gateway_audit_logs ORDER BY id DESC LIMIT 3');
  console.log('   Recent Audit Logs in DB:');
  logRes.rows.forEach(r => console.log(`   - [${r.status}] ${r.query_raw} => ${r.reason}`));

  await client.close();
  await pool.end();

  console.log('🎉 ALL MCP GATEWAY INTEGRATION TESTS PASSED PERFECTLY!');
  process.exit(0);
}

runTests().catch(err => {
  console.error('❌ Test failed:', err);
  process.exit(1);
});

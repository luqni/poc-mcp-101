import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { SSEClientTransport } from '@modelcontextprotocol/sdk/client/sse.js';

const token = process.env.TOKEN || 'shifr_live_claudedesktoptestkey2026alpha';
const query = process.argv.slice(2).join(' ') || 'SELECT * FROM demo_customers';

async function main() {
  console.log(`\n🛡️  SHIFR SHIELD MCP QUERY TESTER`);
  console.log(`📡 Connecting to http://localhost:3000/sse...`);
  console.log(`🔑 Using Token: ${token.slice(0, 15)}••••\n`);

  const transport = new SSEClientTransport(
    new URL(`http://localhost:3000/sse?token=${token}`)
  );

  const client = new Client(
    { name: 'cli-tester', version: '1.0.0' },
    { capabilities: {} }
  );

  await client.connect(transport);
  console.log(`💬 Mengirim Query ke Gateway:`);
  console.log(`   \x1b[36m${query}\x1b[0m\n`);

  try {
    const result = await client.callTool({
      name: 'run_readonly_query',
      arguments: { query },
    });

    const outputText = (result.content[0] as any).text;

    if (result.isError) {
      console.log(`\x1b[31m❌ [SECURITY BLOCKED / ERROR]\x1b[0m`);
      console.log(outputText);
    } else {
      console.log(`\x1b[32m✅ [BERHASIL DIJALANKAN DENGAN PROTEKSI DATA]\x1b[0m`);
      try {
        const parsed = JSON.parse(outputText);
        console.log(`⏱️  Durasi: ${parsed.execution_duration_ms} ms`);
        console.log(`📊 Baris Data: ${parsed.rows_returned}`);
        console.log(`🔒 Data Masking Aktif: ${parsed.masked_columns_protected ? 'Ya' : 'Tidak'}`);
        console.log(`\nHasil Data (Sudah di-masking):`);
        console.table(parsed.data);
      } catch {
        console.log(outputText);
      }
    }
  } catch (err: any) {
    console.error(`\x1b[31mError memanggil tool:\x1b[0m`, err.message);
  }

  await client.close();
  console.log(`\n👉 Cek riwayat query ini di: http://localhost:8000/audit-logs\n`);
  process.exit(0);
}

main().catch((err) => {
  console.error('Koneksi gagal:', err.message);
  process.exit(1);
});

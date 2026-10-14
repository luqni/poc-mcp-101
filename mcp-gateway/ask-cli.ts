import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { SSEClientTransport } from '@modelcontextprotocol/sdk/client/sse.js';

const token = process.env.TOKEN || 'shifr_live_claudedesktoptestkey2026alpha';
const naturalQuestion = process.argv.slice(2).join(' ').trim();

if (!naturalQuestion) {
  console.log(`\n❌ Harap masukkan pertanyaan dalam bahasa manusia.`);
  console.log(`Contoh penggunaan:`);
  console.log(`  npm run ask "tampilkan semua pelanggan aktif"`);
  console.log(`  npm run ask "siapa saja pelanggan yang pending"`);
  console.log(`  npm run ask "berapa total jumlah pelanggan kita"`);
  console.log(`  npm run ask "coba hapus pelanggan yang suspended" (untuk tes keamanan)`);
  console.log(`  npm run ask "tampilkan pelanggan terbaru"\n`);
  process.exit(1);
}

/**
 * Text-to-SQL Translator (Natural Language to SQL)
 */
function translateNaturalLanguageToSql(prompt: string): { sql: string; explanation: string } {
  const p = prompt.toLowerCase();

  // Test mutation security
  if (p.includes('hapus') || p.includes('delete') || p.includes('buang')) {
    if (p.includes('suspended')) {
      return {
        sql: "DELETE FROM demo_customers WHERE status = 'suspended'",
        explanation: 'Menerjemahkan instruksi penghapusan data pelanggan suspended'
      };
    }
    return {
      sql: 'DELETE FROM demo_customers WHERE id = 1',
      explanation: 'Menerjemahkan instruksi hapus data'
    };
  }

  if (p.includes('drop') || p.includes('rusak') || p.includes('hancurkan')) {
    return {
      sql: 'DROP TABLE demo_customers',
      explanation: 'Menerjemahkan instruksi drop tabel'
    };
  }

  // Count / agregasi
  if (p.includes('jumlah') || p.includes('berapa') || p.includes('hitung') || p.includes('total')) {
    if (p.includes('status')) {
      return {
        sql: 'SELECT status, COUNT(*) as jumlah_pelanggan FROM demo_customers GROUP BY status',
        explanation: 'Menghitung total pelanggan dikelompokkan berdasarkan status'
      };
    }
    return {
      sql: 'SELECT COUNT(*) as total_pelanggan FROM demo_customers',
      explanation: 'Menghitung total seluruh pelanggan yang terdaftar'
    };
  }

  // Filter status aktif
  if (p.includes('aktif') || p.includes('active')) {
    return {
      sql: "SELECT name, email, credit_card, salary, status FROM demo_customers WHERE status = 'active'",
      explanation: 'Menampilkan data pelanggan yang statusnya aktif'
    };
  }

  // Filter status pending
  if (p.includes('pending') || p.includes('menunggu')) {
    return {
      sql: "SELECT name, email, credit_card, salary, status FROM demo_customers WHERE status = 'pending'",
      explanation: 'Menampilkan data pelanggan yang masih berstatus pending'
    };
  }

  // Filter status suspended
  if (p.includes('suspend') || p.includes('blokir') || p.includes('nonaktif')) {
    return {
      sql: "SELECT name, email, credit_card, salary, status FROM demo_customers WHERE status = 'suspended'",
      explanation: 'Menampilkan data pelanggan yang terkena suspensi'
    };
  }

  // Urutan terbaru
  if (p.includes('terbaru') || p.includes('terakhir') || p.includes('baru')) {
    return {
      sql: 'SELECT name, email, status, created_at FROM demo_customers ORDER BY created_at DESC',
      explanation: 'Menampilkan pelanggan dengan urutan pendaftaran paling baru'
    };
  }

  // Email / Kontak
  if (p.includes('email') || p.includes('kontak')) {
    return {
      sql: 'SELECT name, email, status FROM demo_customers',
      explanation: 'Menampilkan nama dan email pelanggan'
    };
  }

  // Default query
  return {
    sql: 'SELECT * FROM demo_customers',
    explanation: 'Mengambil seluruh data pelanggan dari tabel demo_customers'
  };
}

async function runNaturalLanguageQuery() {
  console.log(`\n===============================================================`);
  console.log(`🤖 SHIFR SHIELD NATURAL LANGUAGE AGENT`);
  console.log(`===============================================================`);
  console.log(`🗣️  Pertanyaan Anda (Bahasa Manusia):`);
  console.log(`   \x1b[33m"${naturalQuestion}"\x1b[0m\n`);

  const { sql, explanation } = translateNaturalLanguageToSql(naturalQuestion);
  console.log(`🧠 AI Translator:`);
  console.log(`   💡 Maksud: ${explanation}`);
  console.log(`   ⚙️  Query SQL Dihasilkan: \x1b[36m${sql}\x1b[0m\n`);

  console.log(`🛡️  Mengirim ke Shifr Shield Gateway (SSE http://localhost:3000/sse)...`);

  const transport = new SSEClientTransport(
    new URL(`http://localhost:3000/sse?token=${token}`)
  );

  const client = new Client(
    { name: 'natural-language-agent', version: '1.0.0' },
    { capabilities: {} }
  );

  await client.connect(transport);

  try {
    const result = await client.callTool({
      name: 'run_readonly_query',
      arguments: { query: sql },
    });

    const outputText = (result.content[0] as any).text;

    if (result.isError) {
      console.log(`\n\x1b[31m⛔ [SHIFR SHIELD MEMBLOKIR QUERY INI DENGAN AMAN]\x1b[0m`);
      console.log(outputText);
      console.log(`\n🛡️  Penjelasan: Gateway mendeteksi bahwa perintah ini berpotensi membahayakan integritas database.`);
    } else {
      console.log(`\n\x1b[32m✅ [GATEWAY BERHASIL MEMPROSES DENGAN PROTEKSI DATA SENSITIF]\x1b[0m`);
      const parsed = JSON.parse(outputText);
      console.log(`⏱️  Latency: ${parsed.execution_duration_ms} ms | Baris: ${parsed.rows_returned}`);

      console.log(`\n📋 Jawaban / Data Hasil Query:`);
      console.table(parsed.data);

      console.log(`\n🔒 Status Keamanan Shifr Shield:`);
      console.log(`   - Data Masking: Otomatis aktif (kolom credit_card & salary di-[REDACTED], email di-mask).`);
      console.log(`   - Limit Injection: Batas aman disisipkan otomatis.`);
      console.log(`   - Audit Logging: Tersimpan di database kontrol.`);
    }
  } catch (err: any) {
    console.error(`\x1b[31mError:\x1b[0m`, err.message);
  }

  await client.close();
  console.log(`\n👉 Lihat catatan audit lengkap di: http://localhost:8000/audit-logs\n`);
  process.exit(0);
}

runNaturalLanguageQuery().catch((err) => {
  console.error('Koneksi gagal:', err.message);
  process.exit(1);
});

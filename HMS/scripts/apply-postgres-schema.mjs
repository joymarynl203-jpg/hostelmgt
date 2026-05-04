/**
 * Apply HMS/database/schema.postgresql.sql without the psql CLI (Windows-friendly).
 *
 * Usage (from repo root or from this folder):
 *   cd HMS\scripts
 *   npm install
 *   node apply-postgres-schema.mjs "postgresql://USER:PASS@HOST:5432/DBNAME?sslmode=require"
 *
 * Or set env HMS_DATABASE_URL / DATABASE_URL and run:
 *   node apply-postgres-schema.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import pg from 'pg';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

let conn =
    process.argv[2] ||
    process.env.HMS_DATABASE_URL ||
    process.env.DATABASE_URL ||
    '';

if (!conn.trim()) {
    console.error('Missing connection string.');
    console.error('Pass as first argument, or set HMS_DATABASE_URL / DATABASE_URL.');
    process.exit(1);
}

conn = conn.trim();
if (!/sslmode=/i.test(conn) && /\.render\.com/i.test(conn)) {
    conn += (conn.includes('?') ? '&' : '?') + 'sslmode=require';
}

const schemaPath = path.join(__dirname, '..', 'database', 'schema.postgresql.sql');
const raw = fs.readFileSync(schemaPath, 'utf8');

// Drop full-line SQL comments (-- ...)
const withoutLineComments = raw
    .split('\n')
    .filter((line) => !/^\s*--/.test(line))
    .join('\n');

const statements = withoutLineComments
    .split(';')
    .map((s) => s.trim())
    .filter((s) => s.length > 0);

const ssl =
    /\.render\.com/i.test(conn) || /sslmode=require/i.test(conn)
        ? { rejectUnauthorized: false }
        : undefined;

const client = new pg.Client({ connectionString: conn, ssl });

async function main() {
    await client.connect();
    console.log('Connected. Applying', statements.length, 'statements from schema.postgresql.sql ...');
    for (let i = 0; i < statements.length; i++) {
        const sql = statements[i] + ';';
        try {
            await client.query(sql);
            console.log('  OK', i + 1, '/', statements.length);
        } catch (e) {
            // Idempotent re-runs: table/index already there (e.g. hms_sessions from the live app)
            const code = e && e.code;
            const msg = (e && e.message) || '';
            if (code === '42P07' || code === '42710' || /already exists/i.test(msg)) {
                console.log('  SKIP', i + 1, '/', statements.length, '(' + code + ' already exists)');
                continue;
            }
            throw e;
        }
    }
    const r = await client.query('SELECT COUNT(*)::int AS c FROM users');
    console.log('Done. users row count:', r.rows[0].c);
    await client.end();
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});

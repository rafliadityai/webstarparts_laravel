<?php
// =========================================================
// admin/backup.php
// Download backup database SQL - Phase 7
// =========================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin();

function backup_sql_value(PDO $pdo, $value): string
{
    if ($value === null) return 'NULL';
    return $pdo->quote((string) $value);
}

function backup_table_name(string $name): string
{
    return str_replace('`', '``', $name);
}

if (get_string('download', '') === '1' && hash_equals(csrf_token(), get_string('csrf', ''))) {
    admin_audit_log('database_backup_downloaded', 'database', null, 'Backup database SQL didownload melalui Admin Security Center.');

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $lines = [];
    $lines[] = '-- Star Parts Motor database backup';
    $lines[] = '-- Generated: ' . date('Y-m-d H:i:s');
    $lines[] = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";';
    $lines[] = 'SET time_zone = "+00:00";';
    $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
    $lines[] = '';

    foreach ($tables as $table) {
        $table = (string) $table;
        $safeTable = backup_table_name($table);
        $createRow = $pdo->query('SHOW CREATE TABLE `' . $safeTable . '`')->fetch(PDO::FETCH_NUM);
        $createSql = (string) ($createRow[1] ?? '');
        if ($createSql === '') continue;
        $lines[] = '-- Table `' . $table . '`';
        $lines[] = 'DROP TABLE IF EXISTS `' . $safeTable . '`;';
        $lines[] = $createSql . ';';

        $columnsStmt = $pdo->query('SHOW COLUMNS FROM `' . $safeTable . '`');
        $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = [];
        foreach ($columns as $column) $columnNames[] = '`' . backup_table_name((string) $column['Field']) . '`';

        $rowsStmt = $pdo->query('SELECT * FROM `' . $safeTable . '`');
        $batch = [];
        while ($row = $rowsStmt->fetch(PDO::FETCH_ASSOC)) {
            $values = [];
            foreach ($columnNames as $col) {
                $key = trim($col, '`');
                $values[] = backup_sql_value($pdo, $row[$key] ?? null);
            }
            $batch[] = '(' . implode(', ', $values) . ')';
            if (count($batch) >= 200) {
                $lines[] = 'INSERT INTO `' . $safeTable . '` (' . implode(', ', $columnNames) . ') VALUES';
                $lines[] = implode(",\n", $batch) . ';';
                $batch = [];
            }
        }
        if ($batch) {
            $lines[] = 'INSERT INTO `' . $safeTable . '` (' . implode(', ', $columnNames) . ') VALUES';
            $lines[] = implode(",\n", $batch) . ';';
        }
        $lines[] = '';
    }

    $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
    $sql = implode("\n", $lines) . "\n";

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="starparts_db_backup_' . date('Ymd_His') . '.sql"');
    header('Content-Length: ' . strlen($sql));
    header('Cache-Control: private, no-store, max-age=0');
    echo $sql;
    exit;
}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Backup Database</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><style>
*{box-sizing:border-box}body{margin:0;background:#f5f7fb;font-family:Inter,Arial,sans-serif;color:#111827}.wrap{max-width:760px;margin:0 auto;padding:50px 20px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 10px 30px rgba(15,23,42,.06);padding:28px}.card h1{margin:0 0 8px}.card p{color:#6b7280;line-height:1.7}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.btn{display:inline-flex;gap:8px;align-items:center;padding:12px 16px;border-radius:10px;text-decoration:none;font-weight:700;border:1px solid #e5e7eb}.primary{background:#111827;color:#fff;border-color:#111827}.note{margin-top:20px;padding:14px;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:13px;line-height:1.6}
</style></head><body><div class="wrap"><div class="card"><h1><i class="fa-solid fa-database"></i> Backup Database</h1><p>Backup akan mengekspor struktur dan data seluruh tabel <strong><?= e(DB_NAME); ?></strong> menjadi file SQL. File ini termasuk data akun admin, sehingga simpan di lokasi yang aman dan jangan ditaruh di folder publik.</p><div class="actions"><a class="btn primary" href="<?= e(base_url('admin/backup.php?download=1&csrf=' . rawurlencode(csrf_token()))); ?>"><i class="fa-solid fa-download"></i> Download Backup SQL</a><a class="btn" href="<?= e(base_url('admin/security.php')); ?>"><i class="fa-solid fa-shield-halved"></i> Kembali ke Security</a><a class="btn" href="<?= e(base_url('admin/dashboard.php')); ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></div><div class="note"><strong>Penting:</strong> backup ini tidak mencakup file gambar/video di folder <code>uploads/</code>. Untuk backup penuh website, salin juga folder project dan folder uploads.</div></div></div></body></html>

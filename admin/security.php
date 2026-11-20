<?php
// =========================================================
// admin/security.php
// Security Center & profil admin - Phase 7
// =========================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin();

$pageTitle = 'Security Center';
$admin = current_admin() ?? [];
$success = '';
$error = '';

if (get_string('password', '') === 'updated') {
    $success = 'Password admin berhasil diperbarui.';
}

if (is_post()) {
    if (!verify_csrf()) {
        $error = 'Permintaan tidak valid. Silakan refresh halaman dan coba kembali.';
    } elseif (post_string('action') === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $adminId = (int) ($admin['id'] ?? 0);

        try {
            $stmt = $pdo->prepare('SELECT password FROM admin_users WHERE id = ? AND status = 1 LIMIT 1');
            $stmt->execute([$adminId]);
            $hash = (string) ($stmt->fetchColumn() ?: '');

            if ($hash === '' || !password_verify($currentPassword, $hash)) {
                $error = 'Password saat ini tidak benar.';
            } elseif (!admin_password_is_strong($newPassword)) {
                $error = 'Password baru minimal 10 karakter dan harus mengandung huruf besar, huruf kecil, serta angka.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Konfirmasi password baru tidak sama.';
            } elseif (password_verify($newPassword, $hash)) {
                $error = 'Password baru harus berbeda dari password saat ini.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE admin_users SET password = ?, password_changed_at = NOW() WHERE id = ?');
                $update->execute([$newHash, $adminId]);
                admin_audit_log('password_changed', 'admin_user', $adminId, 'Password akun admin diperbarui oleh pemilik akun.');
                redirect(base_url('admin/security.php?password=updated'));
            }
        } catch (Throwable $e) {
            error_log('Star Parts security password error: ' . $e->getMessage());
            $error = 'Terjadi kendala saat menyimpan password. Silakan coba kembali.';
        }
    }
}

$auditLogs = [];
try {
    $auditStmt = $pdo->query(
        "SELECT l.id, l.action, l.target_type, l.target_id, l.details, l.ip_address, l.created_at,
                COALESCE(u.full_name, 'System / akun yang sudah dihapus') AS admin_name
         FROM admin_audit_logs l
         LEFT JOIN admin_users u ON u.id = l.admin_id
         ORDER BY l.id DESC
         LIMIT 15"
    );
    $auditLogs = $auditStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $auditLogs = [];
}

$totalAdmins = 0;
$totalAudit = 0;
try { $totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn(); } catch (Throwable $e) {}
try { $totalAudit = (int) $pdo->query("SELECT COUNT(*) FROM admin_audit_logs")->fetchColumn(); } catch (Throwable $e) {}

$siteUrl = trim((string) get_setting('site_url', ''));
$uploadMax = (string) ini_get('upload_max_filesize');
$postMax = (string) ini_get('post_max_size');

function security_format_bytes_or_text(string $value): string
{
    return $value !== '' ? $value : 'Tidak tersedia';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?= e($pageTitle); ?> | <?= e((string) get_setting('site_name', SITE_NAME)); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root{--bg:#f5f7fb;--card:#fff;--line:#e5e7eb;--text:#111827;--muted:#6b7280;--primary:#111827;--success:#047857;--danger:#b91c1c}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:Inter,Arial,sans-serif;color:var(--text)}
        .wrap{max-width:1180px;margin:0 auto;padding:28px 20px 48px}.top{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:22px}.title h1{margin:0 0 5px;font-size:28px}.title p{margin:0;color:var(--muted)}
        .nav{display:flex;flex-wrap:wrap;gap:8px}.nav a{padding:10px 13px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--text);text-decoration:none;font-size:13px}.nav a:hover{border-color:#cbd5e1}
        .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.stat,.panel{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 7px 24px rgba(15,23,42,.04)}.stat{padding:17px}.stat i{font-size:17px}.stat strong{display:block;font-size:24px;margin-top:8px}.stat span{color:var(--muted);font-size:12px}
        .cols{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px}.panel-head{padding:18px 20px;border-bottom:1px solid var(--line)}.panel-head h2{margin:0;font-size:18px}.panel-head p{margin:6px 0 0;color:var(--muted);font-size:13px}.panel-body{padding:20px}
        .field{margin-bottom:14px}.field label{display:block;font-size:13px;font-weight:700;margin-bottom:7px}.field input{width:100%;padding:12px 13px;border:1px solid #d1d5db;border-radius:10px;font:inherit}.help{font-size:12px;color:var(--muted);margin-top:6px}.btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:10px;padding:11px 15px;cursor:pointer;font-weight:700}.btn-primary{background:var(--primary);color:#fff}.btn-soft{background:#f3f4f6;color:#111827;text-decoration:none}.alert{padding:13px 15px;border-radius:10px;margin-bottom:16px;font-size:13px}.alert-success{background:#ecfdf5;color:var(--success);border:1px solid #a7f3d0}.alert-danger{background:#fef2f2;color:var(--danger);border:1px solid #fecaca}
        .facts{display:grid;grid-template-columns:1fr 1fr;gap:12px}.fact{border:1px solid var(--line);border-radius:12px;padding:13px}.fact span{display:block;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.04em}.fact strong{display:block;margin-top:5px;font-size:14px;word-break:break-word}
        .table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse;font-size:12px}.table th,.table td{padding:11px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}.table th{background:#f8fafc;font-size:11px;text-transform:uppercase;color:var(--muted)}.muted{color:var(--muted)}
        @media(max-width:900px){.grid{grid-template-columns:repeat(2,1fr)}.cols{grid-template-columns:1fr}}@media(max-width:600px){.wrap{padding:18px 12px}.top{align-items:flex-start;flex-direction:column}.grid{grid-template-columns:1fr 1fr}.facts{grid-template-columns:1fr}.title h1{font-size:23px}}
    </style>
</head>
<body>
<?php render_admin_sidebar_assets(); ?>
<?php render_admin_sidebar('security.php'); ?>
<div class="wrap">
    <div class="top">
        <div class="title">
            <h1><i class="fa-solid fa-shield-halved"></i> Security Center</h1>
            <p>Kelola keamanan akun dan pantau aktivitas penting panel admin.</p>
        </div>
        <div class="nav">
            <a href="<?= e(base_url('admin/dashboard.php')); ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            <a href="<?= e(base_url('admin/content.php')); ?>"><i class="fa-solid fa-pen-ruler"></i> Content Center</a>
            <a href="<?= e(base_url('admin/banners.php')); ?>"><i class="fa-solid fa-images"></i> Banner</a>
            <a href="<?= e(base_url('admin/products.php')); ?>"><i class="fa-solid fa-box-open"></i> Produk</a>
            <a href="<?= e(base_url('admin/categories.php')); ?>"><i class="fa-solid fa-layer-group"></i> Kategori</a>
            <a href="<?= e(base_url('admin/brands.php')); ?>"><i class="fa-solid fa-tags"></i> Brand</a>
            <a href="<?= e(base_url('admin/posts.php')); ?>"><i class="fa-solid fa-newspaper"></i> Berita</a>
            <a href="<?= e(base_url('admin/media.php')); ?>"><i class="fa-solid fa-photo-film"></i> Media</a>
            <a href="<?= e(base_url('admin/locations.php')); ?>"><i class="fa-solid fa-location-dot"></i> Lokasi</a>
            <a href="<?= e(base_url('admin/messages.php')); ?>"><i class="fa-solid fa-envelope"></i> Pesan</a>
            <a href="<?= e(base_url('admin/pages.php')); ?>"><i class="fa-solid fa-file-lines"></i> Halaman</a>
            <a href="<?= e(base_url('admin/careers.php')); ?>"><i class="fa-solid fa-briefcase"></i> Lowongan Karir</a>
            <a href="<?= e(base_url('admin/menus.php')); ?>"><i class="fa-solid fa-bars"></i> Menu</a>
            <a href="<?= e(base_url('admin/settings.php')); ?>"><i class="fa-solid fa-gear"></i> Pengaturan</a>
            <a href="<?= e(base_url('admin/testimonials.php')); ?>"><i class="fa-solid fa-comment-dots"></i> Testimoni</a>
            <?php if (strtolower((string) ($admin['role'] ?? '')) === 'superadmin'): ?>
                <a href="<?= e(base_url('admin/users.php')); ?>"><i class="fa-solid fa-users-gear"></i> Admin Users</a>
            <?php endif; ?>
            <a href="<?= e(base_url('admin/backup.php')); ?>"><i class="fa-solid fa-database"></i> Backup Database</a>
            <a href="<?= e(base_url('admin/logout.php')); ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <?php if ($success !== ''): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= e($success); ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($error); ?></div><?php endif; ?>

    <div class="grid">
        <div class="stat"><i class="fa-solid fa-user-shield"></i><strong><?= (int) $totalAdmins; ?></strong><span>Total akun admin</span></div>
        <div class="stat"><i class="fa-solid fa-clock"></i><strong>8 jam</strong><span>Idle session timeout</span></div>
        <div class="stat"><i class="fa-solid fa-lock"></i><strong>5 / 15m</strong><span>Login failed limit</span></div>
        <div class="stat"><i class="fa-solid fa-list-check"></i><strong><?= (int) $totalAudit; ?></strong><span>Total audit log</span></div>
    </div>

    <div class="cols">
        <section class="panel">
            <div class="panel-head"><h2>Ganti Password</h2><p>Password baru minimal 10 karakter, huruf besar, huruf kecil, dan angka.</p></div>
            <div class="panel-body">
                <form method="post" autocomplete="off">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="field"><label>Password Saat Ini</label><input type="password" name="current_password" required autocomplete="current-password"></div>
                    <div class="field"><label>Password Baru</label><input type="password" name="new_password" required minlength="10" autocomplete="new-password"></div>
                    <div class="field"><label>Konfirmasi Password Baru</label><input type="password" name="confirm_password" required minlength="10" autocomplete="new-password"></div>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-key"></i> Simpan Password</button>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Status Sistem</h2><p>Informasi environment untuk membantu pengecekan saat development maupun hosting.</p></div>
            <div class="panel-body">
                <div class="facts">
                    <div class="fact"><span>Environment</span><strong><?= e(APP_ENV); ?></strong></div>
                    <div class="fact"><span>PHP</span><strong><?= e(PHP_VERSION); ?></strong></div>
                    <div class="fact"><span>Database</span><strong><?= e(DB_NAME); ?></strong></div>
                    <div class="fact"><span>Site URL</span><strong><?= e($siteUrl !== '' ? $siteUrl : BASE_URL); ?></strong></div>
                    <div class="fact"><span>Upload Limit</span><strong><?= e(security_format_bytes_or_text($uploadMax)); ?></strong></div>
                    <div class="fact"><span>POST Limit</span><strong><?= e(security_format_bytes_or_text($postMax)); ?></strong></div>
                    <div class="fact"><span>Session Cookie</span><strong>HTTPOnly + SameSite Lax</strong></div>
                    <div class="fact"><span>Upload Protection</span><strong>PHP execution blocked</strong></div>
                </div>
            </div>
        </section>
    </div>

    <section class="panel" style="margin-top:18px">
        <div class="panel-head"><h2>Aktivitas Admin Terbaru</h2><p>Audit log membantu mengetahui login dan perubahan keamanan penting.</p></div>
        <div class="panel-body">
            <?php if (!$auditLogs): ?>
                <div class="muted">Belum ada audit log atau tabel Phase 7 belum dijalankan.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table"><thead><tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Target</th><th>IP</th><th>Detail</th></tr></thead><tbody>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr><td><?= e((string) $log['created_at']); ?></td><td><?= e((string) $log['admin_name']); ?></td><td><?= e((string) $log['action']); ?></td><td><?= e(trim((string) ($log['target_type'] ?? '') . ' #' . (string) ($log['target_id'] ?? ''))); ?></td><td><?= e((string) ($log['ip_address'] ?? '-')); ?></td><td><?= e((string) ($log['details'] ?? '')); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
</body>
</html>

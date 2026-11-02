<?php
// =========================================================
// admin/users.php
// Manajemen akun admin - Phase 7
// =========================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin_role(['superadmin']);

$pageTitle = 'Admin Users';
$admin = current_admin() ?? [];
$error = '';
$success = '';
$editId = get_int('edit', 0);
$editUser = null;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, username, full_name, email, role, status, last_login, password_changed_at FROM admin_users WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (is_post()) {
    if (!verify_csrf()) {
        $error = 'Permintaan tidak valid. Silakan refresh halaman dan coba kembali.';
    } else {
        $action = post_string('action');
        $id = (int) ($_POST['id'] ?? 0);

        try {
            if ($action === 'save_user') {
                $username = trim(post_string('username'));
                $fullName = trim(post_string('full_name'));
                $email = trim(post_string('email'));
                $role = strtolower(post_string('role'));
                $status = isset($_POST['status']) ? 1 : 0;
                $password = (string) ($_POST['password'] ?? '');

                if (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
                    $error = 'Username harus 3-100 karakter dan hanya boleh berisi huruf, angka, titik, underscore, atau strip.';
                } elseif ($fullName === '') {
                    $error = 'Nama lengkap wajib diisi.';
                } elseif (!in_array($role, ['superadmin', 'admin', 'editor'], true)) {
                    $error = 'Role admin tidak valid.';
                } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Format email tidak valid.';
                } elseif ($id <= 0 && !admin_password_is_strong($password)) {
                    $error = 'Password akun baru minimal 10 karakter dan harus mengandung huruf besar, huruf kecil, serta angka.';
                } elseif ($id === (int) ($admin['id'] ?? 0) && ($role !== 'superadmin' || $status !== 1)) {
                    $error = 'Akun superadmin yang sedang digunakan tidak boleh dinonaktifkan atau diturunkan rolenya dari sini.';
                } else {
                    $dup = $pdo->prepare('SELECT id FROM admin_users WHERE username = ? AND id <> ? LIMIT 1');
                    $dup->execute([$username, $id]);
                    if ($dup->fetchColumn()) {
                        $error = 'Username tersebut sudah digunakan.';
                    } elseif ($id > 0) {
                        $sql = 'UPDATE admin_users SET username = ?, full_name = ?, email = ?, role = ?, status = ?';
                        $params = [$username, $fullName, $email !== '' ? $email : null, $role, $status];
                        if ($password !== '') {
                            if (!admin_password_is_strong($password)) {
                                $error = 'Password baru minimal 10 karakter dan harus mengandung huruf besar, huruf kecil, serta angka.';
                            } else {
                                $sql .= ', password = ?, password_changed_at = NOW()';
                                $params[] = password_hash($password, PASSWORD_DEFAULT);
                            }
                        }
                        if ($error === '') {
                            $sql .= ' WHERE id = ?';
                            $params[] = $id;
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                            admin_audit_log('admin_user_updated', 'admin_user', $id, 'Profil/role/status akun admin diperbarui.');
                            redirect(base_url('admin/users.php?saved=1'));
                        }
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password, password_changed_at, full_name, email, role, status) VALUES (?, ?, NOW(), ?, ?, ?, ?)');
                        $stmt->execute([$username, $hash, $fullName, $email !== '' ? $email : null, $role, $status]);
                        $newId = (int) $pdo->lastInsertId();
                        admin_audit_log('admin_user_created', 'admin_user', $newId, 'Akun admin baru dibuat.');
                        redirect(base_url('admin/users.php?saved=1'));
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('Star Parts admin user error: ' . $e->getMessage());
            $error = 'Database gagal memproses perubahan akun admin.';
        } catch (Throwable $e) {
            error_log('Star Parts admin user general error: ' . $e->getMessage());
            $error = 'Terjadi kendala saat memproses akun admin.';
        }
    }
}

if (get_string('saved', '') === '1') {
    $success = 'Data akun admin berhasil disimpan.';
}

$users = $pdo->query('SELECT id, username, full_name, email, role, status, last_login, password_changed_at, created_at FROM admin_users ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

$formUser = $editUser ?: [
    'id' => 0,
    'username' => '',
    'full_name' => '',
    'email' => '',
    'role' => 'admin',
    'status' => 1,
    'last_login' => null,
    'password_changed_at' => null
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= e($pageTitle); ?> | <?= e((string)get_setting('site_name', SITE_NAME)); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--bg:#f5f7fb;--card:#fff;--line:#e5e7eb;--text:#111827;--muted:#6b7280;--primary:#111827;--danger:#b91c1c}*{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:Inter,Arial,sans-serif;color:var(--text)}.wrap{max-width:1180px;margin:0 auto;padding:28px 20px 48px}.top{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:22px}.title h1{margin:0 0 5px;font-size:28px}.title p{margin:0;color:var(--muted)}.nav{display:flex;flex-wrap:wrap;gap:8px}.nav a,.btn{padding:10px 13px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--text);text-decoration:none;font-size:13px}.grid{display:grid;grid-template-columns:360px 1fr;gap:18px}.panel{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 7px 24px rgba(15,23,42,.04)}.panel-head{padding:18px 20px;border-bottom:1px solid var(--line)}.panel-head h2{margin:0;font-size:18px}.panel-body{padding:20px}.field{margin-bottom:13px}.field label{display:block;font-size:13px;font-weight:700;margin-bottom:6px}.field input,.field select{width:100%;padding:11px 12px;border:1px solid #d1d5db;border-radius:10px;font:inherit;background:#fff}.check{display:flex;gap:8px;align-items:center;font-size:13px;margin-bottom:15px}.btn-primary{background:var(--primary);color:#fff;border-color:var(--primary);cursor:pointer}.btn-secondary{background:#f3f4f6}.alert{padding:13px 15px;border-radius:10px;margin-bottom:16px;font-size:13px}.success{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}.danger{background:#fef2f2;color:var(--danger);border:1px solid #fecaca}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse;font-size:12px}.table th,.table td{padding:11px;border-bottom:1px solid var(--line);text-align:left;white-space:nowrap}.table th{background:#f8fafc;color:var(--muted);font-size:11px;text-transform:uppercase}.badge{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:10px;font-weight:700}.active{background:#dcfce7;color:#166534}.inactive{background:#f3f4f6;color:#6b7280}.role{background:#eef2ff;color:#3730a3}.muted{color:var(--muted)}@media(max-width:900px){.grid{grid-template-columns:1fr}.top{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body><div class="wrap">
<div class="top"><div class="title"><h1><i class="fa-solid fa-users-gear"></i> Admin Users</h1><p>Kelola akun administrator. Hanya superadmin yang dapat mengakses halaman ini.</p></div><div class="nav"><a href="<?= e(base_url('admin/dashboard.php')); ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a><a href="<?= e(base_url('admin/security.php')); ?>"><i class="fa-solid fa-shield-halved"></i> Security</a><a href="<?= e(base_url('admin/logout.php')); ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div></div>
<?php if ($success): ?><div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($error); ?></div><?php endif; ?>
<div class="grid">
<section class="panel"><div class="panel-head"><h2><?= $editUser ? 'Edit Admin' : 'Tambah Admin'; ?></h2></div><div class="panel-body"><form method="post" autocomplete="off"><?= csrf_input(); ?><input type="hidden" name="action" value="save_user"><input type="hidden" name="id" value="<?= (int) $formUser['id']; ?>">
<div class="field"><label>Username</label><input name="username" value="<?= e((string)$formUser['username']); ?>" maxlength="100" required pattern="[A-Za-z0-9._-]{3,100}"></div>
<div class="field"><label>Nama Lengkap</label><input name="full_name" value="<?= e((string)$formUser['full_name']); ?>" maxlength="150" required></div>
<div class="field"><label>Email</label><input type="email" name="email" value="<?= e((string)($formUser['email'] ?? '')); ?>" maxlength="150"></div>
<div class="field"><label>Role</label><select name="role"><option value="superadmin" <?= ($formUser['role'] ?? '') === 'superadmin' ? 'selected' : ''; ?>>Superadmin</option><option value="admin" <?= ($formUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option><option value="editor" <?= ($formUser['role'] ?? '') === 'editor' ? 'selected' : ''; ?>>Editor</option></select></div>
<div class="field"><label>Password <?= $editUser ? '(opsional, isi untuk reset)' : ''; ?></label><input type="password" name="password" <?= $editUser ? '' : 'required'; ?> minlength="10" autocomplete="new-password"></div>
<label class="check"><input type="checkbox" name="status" value="1" <?= (int)($formUser['status'] ?? 1) === 1 ? 'checked' : ''; ?>> Akun aktif</label>
<button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
<?php if ($editUser): ?><a class="btn" href="<?= e(base_url('admin/users.php')); ?>">Batal</a><?php endif; ?>
</form></div></section>
<section class="panel"><div class="panel-head"><h2>Daftar Admin</h2></div><div class="panel-body"><div class="table-wrap"><table class="table"><thead><tr><th>ID</th><th>Username</th><th>Nama</th><th>Role</th><th>Status</th><th>Last Login</th><th>Action</th></tr></thead><tbody>
<?php foreach($users as $user): ?><tr><td><?= (int)$user['id']; ?></td><td><strong><?= e((string)$user['username']); ?></strong><br><span class="muted"><?= e((string)($user['email'] ?? '-')); ?></span></td><td><?= e((string)$user['full_name']); ?></td><td><span class="badge role"><?= e((string)$user['role']); ?></span></td><td><span class="badge <?= (int)$user['status'] === 1 ? 'active' : 'inactive'; ?>"><?= (int)$user['status'] === 1 ? 'Aktif' : 'Nonaktif'; ?></span></td><td><?= e((string)($user['last_login'] ?: '-')); ?></td><td><a class="btn btn-secondary" href="<?= e(base_url('admin/users.php?edit='.(int)$user['id'])); ?>"><i class="fa-solid fa-pen"></i> Edit</a></td></tr><?php endforeach; ?>
</tbody></table></div></div></section>
</div></div></body></html>

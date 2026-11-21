<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin();

$pageTitle = 'Lowongan Karir';
$admin = current_admin();
$siteName = trim((string) get_setting('site_name', 'PT Star Parts Motor'));
$listUrl = base_url('admin/careers.php');

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS career_jobs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            poster VARCHAR(255) NULL,
            excerpt TEXT NULL,
            description MEDIUMTEXT NULL,
            application_url VARCHAR(500) NULL,
            application_label VARCHAR(100) NOT NULL DEFAULT 'Lamar Sekarang',
            status ENUM('draft','published') NOT NULL DEFAULT 'draft',
            sort_order INT NOT NULL DEFAULT 0,
            published_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_career_jobs_slug (slug),
            KEY idx_career_jobs_status (status),
            KEY idx_career_jobs_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (Throwable $e) {
    set_flash('error', 'Tabel lowongan karir belum dapat dibuat: ' . $e->getMessage());
}

function career_job_slug(PDO $pdo, string $title, int $excludeId = 0): string
{
    $base = slugify($title);
    if ($base === '') {
        $base = 'lowongan';
    }
    $slug = $base;
    $number = 2;
    while (true) {
        $sql = "SELECT id FROM career_jobs WHERE slug = ?";
        $params = [$slug];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $number++;
    }
}

function career_delete_poster(?string $poster): void
{
    $poster = trim((string) $poster);
    if ($poster === '' || !str_starts_with($poster, 'uploads/')) {
        return;
    }
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $poster);
    $uploads = realpath(dirname(__DIR__) . '/uploads');
    $real = is_file($path) ? realpath($path) : false;
    if ($uploads && $real && str_starts_with($real, rtrim($uploads, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
        delete_image_variants($real);
        @unlink($real);
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'editor_upload') {
    header('Content-Type: application/json; charset=utf-8');
    if (!is_post() || empty($_FILES['file']) || !verify_csrf()) {
        http_response_code(400);
        echo json_encode(['error' => 'Permintaan upload tidak valid.']);
        exit;
    }
    $upload = upload_image($_FILES['file'], 'careers');
    if (empty($upload['success'])) {
        http_response_code(400);
        echo json_encode(['error' => $upload['message'] ?? 'Upload gambar gagal.']);
        exit;
    }
    echo json_encode(['location' => (string) ($upload['url'] ?? media_url((string) $upload['path']))]);
    exit;
}

$flashMessage = get_flash();

if (is_post()) {
    if (!verify_csrf()) {
        set_flash('error', 'Token keamanan tidak valid.');
        redirect($listUrl);
    }

    $action = post_string('action');

    if ($action === 'delete') {
        $id = post_int('id');
        $stmt = $pdo->prepare("SELECT poster FROM career_jobs WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($old) {
            career_delete_poster($old['poster'] ?? null);
            $pdo->prepare("DELETE FROM career_jobs WHERE id = ?")->execute([$id]);
            set_flash('success', 'Lowongan berhasil dihapus.');
        } else {
            set_flash('error', 'Lowongan tidak ditemukan.');
        }
        redirect($listUrl);
    }

    if ($action === 'save') {
        $id = post_int('id');
        $title = trim(post_string('title'));
        $excerpt = trim(post_string('excerpt'));
        $description = trim(post_string('description'));
        $applicationUrl = trim(post_string('application_url'));
        $applicationLabel = trim(post_string('application_label', 'Lamar Sekarang')) ?: 'Lamar Sekarang';
        $status = post_string('status', 'draft');
        $status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';
        $sortOrder = post_int('sort_order');

        if ($title === '') {
            set_flash('error', 'Judul lowongan wajib diisi.');
            redirect($listUrl . ($id ? '?edit=' . $id : '') . '#career-form');
        }
        if ($applicationUrl !== '' && !filter_var($applicationUrl, FILTER_VALIDATE_URL)) {
            set_flash('error', 'URL tombol Lamar harus berupa alamat https/http yang valid.');
            redirect($listUrl . ($id ? '?edit=' . $id : '') . '#career-form');
        }

        $oldPoster = '';
        if ($id > 0) {
            $oldStmt = $pdo->prepare("SELECT * FROM career_jobs WHERE id = ? LIMIT 1");
            $oldStmt->execute([$id]);
            $old = $oldStmt->fetch(PDO::FETCH_ASSOC);
            if (!$old) {
                set_flash('error', 'Lowongan tidak ditemukan.');
                redirect($listUrl);
            }
            $oldPoster = trim((string) ($old['poster'] ?? ''));
        }

        $poster = $oldPoster;
        if (isset($_POST['remove_poster'])) {
            career_delete_poster($oldPoster);
            $poster = '';
        } elseif (isset($_FILES['poster']) && ($_FILES['poster']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = upload_image($_FILES['poster'], 'careers');
            if (empty($upload['success'])) {
                set_flash('error', $upload['message'] ?? 'Upload poster gagal.');
                redirect($listUrl . ($id ? '?edit=' . $id : '') . '#career-form');
            }
            career_delete_poster($oldPoster);
            $poster = (string) $upload['path'];
        } elseif (($selectedPoster = selected_media_path('poster')) !== '') {
            career_delete_poster($oldPoster);
            $poster = $selectedPoster;
        }

        $slug = career_job_slug($pdo, $title, $id);
        $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

        if ($id > 0) {
            $stmt = $pdo->prepare(
                "UPDATE career_jobs
                 SET title=?, slug=?, poster=?, excerpt=?, description=?, application_url=?,
                     application_label=?, status=?, sort_order=?,
                     published_at=CASE WHEN ? = 'published' THEN COALESCE(published_at, NOW()) ELSE NULL END,
                     updated_at=NOW()
                 WHERE id=?"
            );
            $stmt->execute([$title, $slug, $poster, $excerpt, $description, $applicationUrl,
                            $applicationLabel, $status, $sortOrder,
                            $status, $id]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO career_jobs
                 (title, slug, poster, excerpt, description, application_url, application_label,
                  status, sort_order, published_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([$title, $slug, $poster, $excerpt, $description, $applicationUrl,
                            $applicationLabel, $status, $sortOrder, $publishedAt]);
        }
        set_flash('success', 'Lowongan karir berhasil disimpan.');
        redirect($listUrl);
    }
}

$editJob = null;
$editId = get_int('edit');
if ($editId <= 0) {
    $editId = get_int('id');
}
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM career_jobs WHERE id = ? LIMIT 1");
    $stmt->execute([$editId]);
    $editJob = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$jobs = $pdo->query(
    "SELECT * FROM career_jobs ORDER BY sort_order ASC, COALESCE(published_at, created_at) DESC, id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle); ?> | <?= e($siteName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root{--red:#d90000;--border:#e7e7ec;--muted:#71717a;--bg:#f5f6f8}
        *{box-sizing:border-box}body{margin:0;font-family:Inter,Arial,sans-serif;background:var(--bg);color:#18181b}
        a{text-decoration:none;color:inherit}.app{display:flex;min-height:100vh}.side{width:250px;position:fixed;inset:0 auto 0 0;background:#111;color:#fff;padding:20px 14px}
        .brand{padding:8px 10px 20px;border-bottom:1px solid rgba(255,255,255,.08);font-weight:800}.nav-label{font-size:10px;letter-spacing:1.5px;color:#777;padding:18px 10px 7px}.nav a{display:flex;gap:10px;align-items:center;padding:10px 12px;border-radius:9px;color:#bbb;font-size:13px;margin:3px 0}.nav a.active,.nav a:hover{background:rgba(217,0,0,.18);color:#fff}.nav i{width:18px}
        .main{margin-left:250px;width:calc(100% - 250px);min-width:0}.top{height:70px;background:#fff;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 24px}.top strong{font-size:17px}.top a{border:1px solid var(--border);border-radius:9px;padding:8px 10px;font-size:12px;font-weight:700}
        main{max-width:1500px;margin:auto;padding:24px}.flash{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}.flash.success{background:#ecf8ef;color:#14763d}.flash.error{background:#fff0f0;color:#a70000}.layout{display:grid;grid-template-columns:minmax(0,1fr) 390px;gap:18px}
        .panel{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 10px 30px rgba(10,10,10,.05);overflow:hidden}.panel-head{padding:16px 18px;border-bottom:1px solid var(--border)}.panel-head h2{margin:0;font-size:16px}.panel-head p{margin:5px 0 0;color:var(--muted);font-size:11px}.panel-body{padding:18px}
        .grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:14px}.col-12{grid-column:span 12}.col-8{grid-column:span 8}.col-4{grid-column:span 4}.form-group label{display:block;font-size:12px;font-weight:800;margin-bottom:7px}.form-control{width:100%;border:1px solid #dddde4;border-radius:9px;padding:10px 11px;font:inherit;font-size:13px}.form-control:focus{outline:none;border-color:var(--red);box-shadow:0 0 0 3px rgba(217,0,0,.08)}textarea.form-control{min-height:90px}.form-help{font-size:10px;color:var(--muted);margin-top:6px}
        .actions{display:flex;gap:8px;margin-top:16px}.btn{border:0;border-radius:9px;padding:10px 13px;font-size:12px;font-weight:800;cursor:pointer}.btn-primary{background:var(--red);color:#fff}.btn-light{background:#f2f2f5;color:#333}.preview{width:100%;max-height:180px;object-fit:cover;border-radius:9px;margin-bottom:8px}
        .job-list{display:grid;gap:10px}.job-item{display:grid;grid-template-columns:72px minmax(0,1fr) auto;gap:11px;align-items:center;border:1px solid var(--border);border-radius:11px;padding:9px}.job-thumb{width:72px;height:52px;border-radius:8px;object-fit:cover;background:#eee}.job-item h3{font-size:13px;margin:0 0 4px}.job-item p{font-size:10px;color:var(--muted);margin:0}.job-tools{display:flex;gap:5px}.icon-btn{border:1px solid var(--border);background:#fff;width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}.delete-btn{color:#b00000}
        @media(max-width:1100px){.layout{grid-template-columns:1fr}.side{display:none}.main{margin:0;width:100%}}
        @media(max-width:650px){.col-8,.col-4{grid-column:span 12}.job-item{grid-template-columns:58px minmax(0,1fr)}.job-tools{grid-column:span 2}.job-thumb{width:58px;height:44px}}
    </style>
</head>
<body>
<?php render_admin_sidebar_assets(); ?>
<?php render_admin_sidebar('careers.php'); ?>
<div class="app">
    <aside class="side">
        <div class="brand">STAR PARTS <small>MOTOR</small></div>
        <div class="nav-label">MAIN MENU</div>
        <nav class="nav">
            <a href="<?= e(base_url('admin/dashboard.php')); ?>"><i class="fa-solid fa-gauge-high"></i>Dashboard</a>
            <a href="<?= e(base_url('admin/content.php')); ?>"><i class="fa-solid fa-pen-ruler"></i>Content Center</a>
            <a href="<?= e(base_url('admin/banners.php')); ?>"><i class="fa-solid fa-images"></i>Banner</a>
            <a href="<?= e(base_url('admin/products.php')); ?>"><i class="fa-solid fa-box-open"></i>Produk</a>
            <a href="<?= e(base_url('admin/categories.php')); ?>"><i class="fa-solid fa-layer-group"></i>Kategori</a>
            <a href="<?= e(base_url('admin/brands.php')); ?>"><i class="fa-solid fa-tags"></i>Brand</a>
            <a class="active" href="<?= e($listUrl); ?>"><i class="fa-solid fa-briefcase"></i>Lowongan Karir</a>
            <a href="<?= e(base_url('admin/posts.php')); ?>"><i class="fa-solid fa-newspaper"></i>Berita</a>
            <a href="<?= e(base_url('admin/media.php')); ?>"><i class="fa-solid fa-photo-film"></i>Media</a>
            <a href="<?= e(base_url('admin/locations.php')); ?>"><i class="fa-solid fa-location-dot"></i>Lokasi</a>
            <a href="<?= e(base_url('admin/messages.php')); ?>"><i class="fa-solid fa-envelope"></i>Pesan</a>
            <a href="<?= e(base_url('admin/pages.php')); ?>"><i class="fa-solid fa-file-lines"></i>Halaman</a>
            <a href="<?= e(base_url('admin/menus.php')); ?>"><i class="fa-solid fa-bars"></i>Menu Website</a>
            <a href="<?= e(base_url('admin/settings.php')); ?>"><i class="fa-solid fa-gear"></i>Pengaturan</a>
            <a href="<?= e(base_url('admin/security.php')); ?>"><i class="fa-solid fa-shield-halved"></i>Security</a>
            <a href="<?= e(base_url('admin/testimonials.php')); ?>"><i class="fa-solid fa-comment-dots"></i>Testimoni</a>
        </nav>
    </aside>
    <div class="main">
        <header class="top"><strong>Lowongan Karir</strong><a href="<?= e(base_url('career.php')); ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Halaman</a></header>
        <main>
            <?php if (is_array($flashMessage) && !empty($flashMessage['message'])): ?>
                <div class="flash <?= e($flashMessage['type'] === 'error' ? 'error' : 'success'); ?>"><?= e($flashMessage['message']); ?></div>
            <?php endif; ?>
            <div class="layout">
                <section class="panel" id="career-form">
                    <div class="panel-head"><h2><?= $editJob ? 'Edit Lowongan' : 'Tambah Lowongan'; ?></h2><p>Tambahkan poster, deskripsi lengkap, dan tombol lamar.</p></div>
                    <form class="panel-body" method="post" enctype="multipart/form-data">
                        <?= csrf_input(); ?>
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= (int) ($editJob['id'] ?? 0); ?>">
                        <div class="grid">
                            <div class="form-group col-8"><label>Judul Posisi *</label><input class="form-control" name="title" required value="<?= e((string) ($editJob['title'] ?? '')); ?>" placeholder="Contoh: Sales Executive"></div>
                            <div class="form-group col-4"><label>Status</label><select class="form-control" name="status"><option value="published" <?= (($editJob['status'] ?? 'draft') === 'published') ? 'selected' : ''; ?>>Published</option><option value="draft" <?= (($editJob['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Draft</option></select></div>
                            <div class="form-group col-8"><label>Ringkasan Singkat</label><textarea class="form-control" name="excerpt" placeholder="Ringkasan tampil di kartu lowongan"><?= e((string) ($editJob['excerpt'] ?? '')); ?></textarea></div>
                            <div class="form-group col-4">
                                <label>Poster</label>
                                <?php $poster = trim((string) ($editJob['poster'] ?? '')); ?>
                                <?php if ($poster !== ''): ?><img class="preview" src="<?= e(media_url($poster)); ?>" alt="Poster lowongan"><label><input type="checkbox" name="remove_poster"> Hapus poster</label><?php endif; ?>
                                <input class="form-control" type="file" name="poster" accept="image/jpeg,image/png,image/webp,image/gif">
                            </div>
                            <div class="form-group col-12"><label>Deskripsi Pekerjaan</label><textarea class="form-control content-editor" name="description"><?= e((string) ($editJob['description'] ?? '')); ?></textarea><div class="form-help">Gunakan toolbar untuk teks tebal, link, daftar, dan foto di dalam deskripsi.</div></div>
                            <div class="form-group col-8"><label>URL Tombol Lamar</label><input class="form-control" type="url" name="application_url" value="<?= e((string) ($editJob['application_url'] ?? '')); ?>" placeholder="https://forms.gle/... atau https://wa.me/..."></div>
                            <div class="form-group col-4"><label>Label Tombol</label><input class="form-control" name="application_label" value="<?= e((string) ($editJob['application_label'] ?? 'Lamar Sekarang')); ?>"></div>
                            <div class="form-group col-12"><label>Urutan</label><input class="form-control" type="number" name="sort_order" value="<?= (int) ($editJob['sort_order'] ?? 0); ?>"></div>
                        </div>
                        <div class="actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan Lowongan</button><?php if ($editJob): ?><a class="btn btn-light" href="<?= e($listUrl); ?>">Batal</a><?php endif; ?></div>
                    </form>
                </section>

                <section class="panel">
                    <div class="panel-head"><h2>Daftar Lowongan</h2><p><?= count($jobs); ?> lowongan tersimpan</p></div>
                    <div class="panel-body job-list">
                        <?php foreach ($jobs as $job): ?>
                            <?php $poster = trim((string) ($job['poster'] ?? '')); ?>
                            <article class="job-item">
                                <?php if ($poster !== ''): ?><img class="job-thumb" src="<?= e(media_url($poster)); ?>" alt=""><?php else: ?><div class="job-thumb"></div><?php endif; ?>
                                <div><h3><?= e((string) $job['title']); ?></h3><p><?= $job['status'] === 'published' ? 'Published' : 'Draft'; ?> · Urutan <?= (int) $job['sort_order']; ?></p></div>
                                <div class="job-tools">
                                    <a class="icon-btn" href="<?= e($listUrl . '?id=' . (int) $job['id']); ?>#career-form" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    <form method="post" onsubmit="return confirm('Hapus lowongan ini?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $job['id']; ?>"><button class="icon-btn delete-btn" type="submit" title="Hapus"><i class="fa-solid fa-trash"></i></button></form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        <?php if (!$jobs): ?><p class="form-help">Belum ada lowongan.</p><?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector:'textarea.content-editor',
            min_height:360,
            plugins:'lists link image autolink charmap preview fullscreen table wordcount',
            toolbar:'undo redo | blocks | bold italic underline | forecolor backcolor | link image | alignleft aligncenter alignright | numlist bullist | removeformat',
            automatic_uploads:true,
            images_upload_url:'careers.php?action=editor_upload',
            images_upload_handler:function(blobInfo,progress){
                return new Promise(function(resolve,reject){
                    var xhr=new XMLHttpRequest(), form=new FormData();
                    xhr.open('POST','careers.php?action=editor_upload');
                    xhr.upload.onprogress=function(e){if(e.lengthComputable)progress(e.loaded/e.total*100);};
                    xhr.onload=function(){try{var json=JSON.parse(xhr.responseText);if(xhr.status>=200&&xhr.status<300&&json.location){resolve(json.location);}else{reject(json.error||'Upload gambar gagal.');}}catch(e){reject('Upload gambar gagal.');}};
                    xhr.onerror=function(){reject('Upload gambar gagal.');};
                    form.append('file',blobInfo.blob(),blobInfo.filename());
                    form.append('csrf_token',document.querySelector('input[name="csrf_token"]').value);
                    xhr.send(form);
                });
            },
            setup:function(editor){editor.on('change keyup',function(){editor.save();});}
        });
    }
    var form=document.querySelector('form.panel-body');
    if(form){form.addEventListener('submit',function(){if(typeof tinymce!=='undefined')tinymce.triggerSave();});}
});
</script>
</body>
</html>

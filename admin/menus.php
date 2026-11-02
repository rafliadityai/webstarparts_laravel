<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$pageTitle = 'Menu Website';
$admin = current_admin();
$siteName = trim((string)get_setting('site_name', 'PT Star Parts Motor'));
$listUrl = base_url('admin/menus.php');
$message = get_flash();

function menu_get(PDO $pdo, int $id): ?array {
    if ($id <= 0) return null;
    $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}
function menu_descendants(PDO $pdo, int $id, array &$seen = []): array {
    if ($id <= 0 || isset($seen[$id])) return [];
    $seen[$id] = true;
    $stmt = $pdo->prepare('SELECT id FROM menus WHERE parent_id = ?');
    $stmt->execute([$id]);
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $child) {
        $child = (int)$child;
        $out[] = $child;
        $out = array_merge($out, menu_descendants($pdo, $child, $seen));
    }
    return $out;
}
function menu_delete_branch(PDO $pdo, int $id): void {
    $seen = [];
    $children = menu_descendants($pdo, $id, $seen);
    foreach (array_reverse($children) as $child) {
        $stmt = $pdo->prepare('DELETE FROM menus WHERE id = ?');
        $stmt->execute([$child]);
    }
    $stmt = $pdo->prepare('DELETE FROM menus WHERE id = ?');
    $stmt->execute([$id]);
}

$action = strtolower(get_string('action', 'list'));
$editId = get_int('id', 0);

if (is_post()) {
    if (!verify_csrf()) {
        set_flash('error', 'Permintaan tidak valid. Silakan refresh halaman.');
        redirect($listUrl);
    }
    try {
        $formAction = strtolower(post_string('form_action'));
        $id = (int)post_string('id', '0');
        if ($formAction === 'save') {
            $title = trim(post_string('title'));
            $slug = trim(post_string('slug'));
            $menuType = post_string('menu_type', 'url');
            $menuUrl = trim(post_string('menu_url'));
            $parentId = (int)post_string('parent_id', '0');
            $sortOrder = (int)post_string('sort_order', '0');
            $isActive = post_string('is_active', '1') === '1' ? 1 : 0;
            $target = post_string('target', '_self');
            if ($title === '') throw new RuntimeException('Nama menu wajib diisi.');
            if (!in_array($menuType, ['url','page','category'], true)) $menuType = 'url';
            if (!in_array($target, ['_self','_blank'], true)) $target = '_self';
            if ($id > 0 && $parentId === $id) $parentId = 0;
            if ($id > 0 && $parentId > 0) {
                $desc = menu_descendants($pdo, $id);
                if (in_array($parentId, $desc, true)) throw new RuntimeException('Parent menu tidak boleh merupakan anak dari menu yang sama.');
            }
            if ($menuType !== 'url') {
                if ($menuType === 'page' && $slug !== '') {
                    $pageMap = [
                        'home' => 'index.php',
                        'about-us' => 'about.php',
                        'product' => 'products.php',
                        'news' => 'news.php',
                        'career' => 'career.php',
                        'contact' => 'contact.php',
                        'privacy' => 'privacy.php',
                        'terms' => 'terms.php',
                    ];
                    $menuUrl = $pageMap[$slug] ?? $slug;
                }
                if ($menuType === 'category' && $slug !== '') $menuUrl = 'products.php?category=' . rawurlencode($slug);
            }
            if ($slug === '') $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE menus SET parent_id = ?, title = ?, slug = ?, menu_url = ?, menu_type = ?, sort_order = ?, is_active = ?, target = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$parentId > 0 ? $parentId : null, $title, $slug, $menuUrl, $menuType, $sortOrder, $isActive, $target, $id]);
                set_flash('success', 'Menu berhasil diperbarui.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO menus (parent_id,title,slug,menu_url,menu_type,sort_order,is_active,target,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
                $stmt->execute([$parentId > 0 ? $parentId : null, $title, $slug, $menuUrl, $menuType, $sortOrder, $isActive, $target]);
                set_flash('success', 'Menu berhasil ditambahkan.');
            }
        } elseif ($formAction === 'delete') {
            if ($id <= 0) throw new RuntimeException('Menu tidak ditemukan.');
            menu_delete_branch($pdo, $id);
            set_flash('success', 'Menu dan submenu terkait berhasil dihapus.');
        } elseif ($formAction === 'toggle') {
            $row = menu_get($pdo, $id);
            if (!$row) throw new RuntimeException('Menu tidak ditemukan.');
            $stmt = $pdo->prepare('UPDATE menus SET is_active = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([(int)$row['is_active'] === 1 ? 0 : 1, $id]);
            set_flash('success', 'Status menu diperbarui.');
        }
    } catch (Throwable $e) {
        set_flash('error', 'Perubahan menu gagal: ' . $e->getMessage());
    }
    redirect($listUrl);
}

$edit = ($action === 'edit') ? menu_get($pdo, $editId) : null;
$allMenus = [];
try {
    $allMenus = $pdo->query('SELECT * FROM menus ORDER BY COALESCE(parent_id,0), sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $allMenus = []; }
$rootMenus = [];
$children = [];
foreach ($allMenus as $row) {
    $parent = (int)($row['parent_id'] ?? 0);
    if ($parent === 0) $rootMenus[] = $row; else $children[$parent][] = $row;
}
$categories = get_categories(true);
$pages = [];
try { $pages = $pdo->query("SELECT id,title,slug,status FROM pages ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><meta name="robots" content="noindex,nofollow"><title><?= e($pageTitle); ?> | <?= e($siteName); ?></title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" referrerpolicy="no-referrer"><style>
:root{--red:#d90000;--black:#121212;--bg:#f5f6f8;--text:#191919;--muted:#707070;--line:#e7e7ea;--white:#fff;--shadow:0 8px 28px rgba(0,0,0,.06)}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,Arial,sans-serif}a{text-decoration:none;color:inherit}.wrap{min-height:100vh;display:flex}.side{position:fixed;left:0;top:0;bottom:0;width:250px;background:#111;color:#fff;padding:22px 15px;overflow:auto}.brand{display:flex;gap:10px;align-items:center;padding:8px 10px 22px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:18px}.brand strong{font-size:15px}.brand small{display:block;color:#aaa;font-size:9px;letter-spacing:1.5px;margin-top:3px}.nav-label{font-size:10px;letter-spacing:1.6px;color:#777;padding:8px 10px}.nav{display:grid;gap:4px}.nav a{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:10px;color:#bbb;font-size:13px;font-weight:700}.nav a:hover,.nav a.active{background:rgba(217,0,0,.18);color:#fff}.nav i{width:18px;text-align:center}.main{margin-left:250px;width:calc(100% - 250px)}.top{height:72px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 26px;position:sticky;top:0;z-index:10}.top strong{font-size:17px}.top span{display:block;color:var(--muted);font-size:11px;margin-top:3px}.actions{display:flex;gap:8px}.actions a{border:1px solid var(--line);border-radius:10px;padding:9px 12px;font-size:12px;font-weight:800}.content{padding:25px 26px 50px;max-width:1450px}.flash{padding:12px 14px;border-radius:12px;margin-bottom:18px;font-size:12px;font-weight:700}.flash.success{background:#eaf8ef;border:1px solid #cdebd8;color:#14763d}.flash.error{background:#fff1f1;border:1px solid #f2c8c8;color:#9d0000}.layout{display:grid;grid-template-columns:minmax(0,1fr) 410px;gap:18px}.panel{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);overflow:hidden}.ph{padding:18px 20px;border-bottom:1px solid var(--line)}.ph h2{margin:0;font-size:17px}.ph p{margin:6px 0 0;font-size:11px;color:var(--muted)}.pb{padding:20px}.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:14px}.c12{grid-column:span 12}.c6{grid-column:span 6}.c4{grid-column:span 4}.label{display:block;font-size:11px;font-weight:800;margin-bottom:7px}.input,.select{width:100%;border:1px solid #e1e1e5;border-radius:10px;padding:10px 11px;background:#fff;font:inherit;font-size:12px;outline:none}.input:focus,.select:focus{border-color:rgba(217,0,0,.5);box-shadow:0 0 0 3px rgba(217,0,0,.06)}.help{font-size:10px;color:#888;margin-top:6px;line-height:1.45}.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:0;border-radius:10px;padding:10px 13px;font-size:12px;font-weight:800;cursor:pointer}.btn-red{background:var(--red);color:#fff}.btn-light{background:#f5f5f6;border:1px solid var(--line)}.form-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}.tree{display:grid;gap:10px}.item{border:1px solid var(--line);border-radius:12px;padding:13px 14px;background:#fff}.item.child{margin-left:25px;background:#fcfcfd}.item-head{display:flex;align-items:center;justify-content:space-between;gap:10px}.item-name{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:800}.badge{font-size:9px;font-weight:900;padding:4px 7px;border-radius:999px;background:#f1f1f3;color:#707078}.badge.red{background:#fff0f0;color:#b10000}.meta{font-size:10px;color:#888;margin-top:7px}.row-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.mini{display:inline-flex;gap:6px;align-items:center;border:1px solid var(--line);border-radius:8px;padding:7px 9px;font-size:10px;font-weight:800;background:#fff}.mini.red{color:#b00000}.empty{padding:22px;border:1px dashed #d8d8dd;border-radius:12px;color:#888;font-size:12px;text-align:center}@media(max-width:1000px){.layout{grid-template-columns:1fr}.side{width:220px}.main{margin-left:220px;width:calc(100% - 220px)}}@media(max-width:760px){.side{position:relative;width:100%;height:auto}.main{margin-left:0;width:100%}.wrap{display:block}.layout{grid-template-columns:1fr}.top{position:static}.content{padding:18px}.c6,.c4{grid-column:span 12}}
</style></head><body>
<div class="wrap"><aside class="side"><div class="brand"><div><strong>STAR PARTS</strong><small>MOTOR ADMIN</small></div></div><div class="nav-label">WEBSITE</div><nav class="nav"><a href="<?= e(base_url('admin/dashboard.php')); ?>"><i class="fa-solid fa-gauge-high"></i>Dashboard</a><a href="<?= e(base_url('admin/content.php')); ?>"><i class="fa-solid fa-pen-ruler"></i>Content Center</a><a class="active" href="<?= e($listUrl); ?>"><i class="fa-solid fa-bars"></i>Menu Website</a><a href="<?= e(base_url('admin/pages.php')); ?>"><i class="fa-regular fa-file-lines"></i>Halaman</a><a href="<?= e(base_url('admin/settings.php')); ?>"><i class="fa-solid fa-gear"></i>Pengaturan</a></nav><div class="nav-label" style="margin-top:16px">MODUL</div><nav class="nav"><a href="<?= e(base_url('admin/products.php')); ?>"><i class="fa-solid fa-box-open"></i>Produk</a><a href="<?= e(base_url('admin/posts.php')); ?>"><i class="fa-solid fa-newspaper"></i>Berita</a><a href="<?= e(base_url('admin/banners.php')); ?>"><i class="fa-regular fa-images"></i>Banner</a><a href="<?= e(base_url('admin/media.php')); ?>"><i class="fa-solid fa-photo-film"></i>Media</a></nav></aside>
<div class="main"><header class="top"><div><strong><?= e($pageTitle); ?></strong><span>Atur struktur navigasi website Star Parts Motor.</span></div><div class="actions"><a href="<?= e(site_url('index.php')); ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-up-right-from-square"></i> Lihat Website</a><a href="<?= e(base_url('admin/logout.php')); ?>"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a></div></header><main class="content">
<?php if (is_array($message) && !empty($message['message'])): ?><div class="flash <?= ($message['type'] ?? '') === 'success' ? 'success' : 'error'; ?>"><?= e((string)$message['message']); ?></div><?php endif; ?>
<div class="layout"><section class="panel"><div class="ph"><h2>Struktur Menu</h2><p>Menu aktif akan dibaca oleh navbar website. Submenu mengikuti parent menu yang dipilih.</p></div><div class="pb"><div class="tree">
<?php if (!$rootMenus): ?><div class="empty">Belum ada menu. Tambahkan menu pertama dari panel di sebelah kanan.</div><?php endif; ?>
<?php foreach ($rootMenus as $m): ?>
<div class="item"><div class="item-head"><div class="item-name"><i class="fa-solid fa-grip-lines-vertical"></i><?= e($m['title']); ?> <span class="badge <?= (int)$m['is_active']===1?'':'red'; ?>"><?= (int)$m['is_active']===1?'AKTIF':'NONAKTIF'; ?></span></div><span class="badge">#<?= (int)$m['sort_order']; ?></span></div><div class="meta"><?= e((string)$m['menu_url']); ?> · target <?= e((string)$m['target']); ?></div><div class="row-actions"><a class="mini" href="<?= e($listUrl.'?action=edit&id='.(int)$m['id']); ?>"><i class="fa-solid fa-pen"></i>Edit</a><form method="post" style="display:inline"><?= csrf_input(); ?><input type="hidden" name="form_action" value="toggle"><input type="hidden" name="id" value="<?= (int)$m['id']; ?>"><button class="mini" type="submit"><i class="fa-solid fa-power-off"></i><?= (int)$m['is_active']===1?'Nonaktifkan':'Aktifkan'; ?></button></form><form method="post" style="display:inline" onsubmit="return confirm('Hapus menu ini beserta submenu-nya?');"><?= csrf_input(); ?><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id']; ?>"><button class="mini red" type="submit"><i class="fa-solid fa-trash"></i>Hapus</button></form></div></div>
<?php foreach (($children[(int)$m['id']] ?? []) as $c): ?><div class="item child"><div class="item-head"><div class="item-name"><i class="fa-solid fa-angle-right"></i><?= e($c['title']); ?> <span class="badge <?= (int)$c['is_active']===1?'':'red'; ?>"><?= (int)$c['is_active']===1?'AKTIF':'NONAKTIF'; ?></span></div><span class="badge">#<?= (int)$c['sort_order']; ?></span></div><div class="meta"><?= e((string)$c['menu_url']); ?> · target <?= e((string)$c['target']); ?></div><div class="row-actions"><a class="mini" href="<?= e($listUrl.'?action=edit&id='.(int)$c['id']); ?>"><i class="fa-solid fa-pen"></i>Edit</a><form method="post" style="display:inline"><?= csrf_input(); ?><input type="hidden" name="form_action" value="toggle"><input type="hidden" name="id" value="<?= (int)$c['id']; ?>"><button class="mini" type="submit"><i class="fa-solid fa-power-off"></i><?= (int)$c['is_active']===1?'Nonaktifkan':'Aktifkan'; ?></button></form><form method="post" style="display:inline" onsubmit="return confirm('Hapus submenu ini?');"><?= csrf_input(); ?><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id']; ?>"><button class="mini red" type="submit"><i class="fa-solid fa-trash"></i>Hapus</button></form></div></div><?php endforeach; ?>
<?php endforeach; ?></div></div></section>
<section class="panel"><div class="ph"><h2><?= $edit ? 'Edit Menu' : 'Tambah Menu'; ?></h2><p><?= $edit ? 'Perbarui konfigurasi menu yang dipilih.' : 'Tambahkan menu baru tanpa menyentuh database secara manual.'; ?></p></div><div class="pb"><form method="post"><?= csrf_input(); ?><input type="hidden" name="form_action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0); ?>"><div class="grid"><div class="c12"><label class="label">Nama Menu</label><input class="input" name="title" value="<?= e((string)($edit['title'] ?? '')); ?>" required></div><div class="c6"><label class="label">Jenis</label><select class="select" id="menu_type" name="menu_type"><option value="url" <?= (($edit['menu_type'] ?? 'url')==='url'?'selected':''); ?>>URL</option><option value="page" <?= (($edit['menu_type'] ?? '')==='page'?'selected':''); ?>>Halaman CMS</option><option value="category" <?= (($edit['menu_type'] ?? '')==='category'?'selected':''); ?>>Kategori Produk</option></select></div><div class="c6"><label class="label">Parent Menu</label><select class="select" name="parent_id"><option value="0">Menu Utama</option><?php foreach ($allMenus as $m): if ((int)($edit['id'] ?? 0)===(int)$m['id']) continue; ?><option value="<?= (int)$m['id']; ?>" <?= ((int)($edit['parent_id'] ?? 0)===(int)$m['id']?'selected':''); ?>><?= e((string)$m['title']); ?></option><?php endforeach; ?></select></div><div class="c12" id="url_group"><label class="label">URL Manual</label><input class="input" name="menu_url" value="<?= e((string)($edit['menu_url'] ?? '')); ?>"><div class="help">Contoh: contact.php, https://partner.example.com/, atau /produk/...</div></div><div class="c12" id="source_group" style="display:none"><label class="label">Sumber Halaman/Kategori</label><select class="select" id="slug" name="slug"><option value="">Pilih sumber...</option><?php foreach ($pages as $p): ?><option data-kind="page" value="<?= e((string)$p['slug']); ?>" <?= (($edit['slug'] ?? '')===$p['slug']?'selected':''); ?>>Halaman: <?= e((string)$p['title']); ?></option><?php endforeach; ?><?php foreach ($categories as $c): ?><option data-kind="category" value="<?= e((string)$c['slug']); ?>" <?= (($edit['slug'] ?? '')===$c['slug']?'selected':''); ?>>Kategori: <?= e((string)$c['name']); ?></option><?php endforeach; ?></select></div><div class="c4"><label class="label">Sort Order</label><input class="input" type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0); ?>"></div><div class="c4"><label class="label">Status</label><select class="select" name="is_active"><option value="1" <?= ((int)($edit['is_active'] ?? 1)===1?'selected':''); ?>>Aktif</option><option value="0" <?= ((int)($edit['is_active'] ?? 1)===0?'selected':''); ?>>Nonaktif</option></select></div><div class="c4"><label class="label">Target</label><select class="select" name="target"><option value="_self" <?= (($edit['target'] ?? '_self')==='_self'?'selected':''); ?>>Tab yang sama</option><option value="_blank" <?= (($edit['target'] ?? '')==='_blank'?'selected':''); ?>>Tab baru</option></select></div></div><div class="form-actions"><button class="btn btn-red" type="submit"><i class="fa-solid fa-floppy-disk"></i>Simpan Menu</button><?php if ($edit): ?><a class="btn btn-light" href="<?= e($listUrl); ?>"><i class="fa-solid fa-xmark"></i>Batal Edit</a><?php endif; ?></div></form></div></section></div></main></div></div>
<script>
const type=document.getElementById('menu_type'), urlGroup=document.getElementById('url_group'), sourceGroup=document.getElementById('source_group');
function sync(){const v=type.value; const needs=v!=='url'; urlGroup.style.display=needs?'none':'block'; sourceGroup.style.display=needs?'block':'none';}
type.addEventListener('change',sync); sync();
</script></body></html>

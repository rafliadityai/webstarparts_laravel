<?php
/** Phase 11: editable FDR product-detail content. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/fdr_catalog.php';
require_admin();

$admin = current_admin();
$adminName = (string)($admin['full_name'] ?? $_SESSION['admin_name'] ?? 'Administrator');
$hasDetailsTable = fdr_details_table_exists();
$error = '';
$success = '';

if (empty($_SESSION['fdr_details_csrf'])) {
    $_SESSION['fdr_details_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['fdr_details_csrf'];

$products = [];
try {
    $products = $pdo->query("SELECT p.id,p.name,p.slug,p.product_url,p.short_description,p.description,c.name AS category_name,c.slug AS category_slug
        FROM products p LEFT JOIN product_categories c ON c.id=p.category_id
        WHERE (LOWER(COALESCE(c.slug,'')) IN ('fdr-tire','fdr-ultimate','fdr-champion') OR LOWER(p.name) LIKE '%fdr%')
        ORDER BY p.is_active DESC, c.sort_order ASC, p.sort_order ASC, p.name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'Daftar produk tidak dapat dibaca. Periksa konfigurasi database terlebih dahulu.';
}

$selectedId = max(0, (int)($_POST['product_id'] ?? $_GET['id'] ?? ($products[0]['id'] ?? 0)));
$selectedProduct = null;
foreach ($products as $item) {
    if ((int)$item['id'] === $selectedId) { $selectedProduct = $item; break; }
}

$defaults = [
    'tire_type' => 'other',
    'listing_description' => '',
    'hero_description' => '',
    'benefits_text' => '',
    'sizes_text' => '',
    'performance_text' => '',
    'technologies_text' => '',
    'logo_path' => 'uploads/general/fdr-tire-logo-red.png',
    'virtual_tire_url' => 'https://bengkelku.online',
];
$detail = $defaults;
$buyUrl = (string)($selectedProduct['product_url'] ?? '');
if ($selectedProduct && $hasDetailsTable) {
    $detail = array_merge($defaults, fdr_get_product_detail($selectedId));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasDetailsTable) {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrfToken, $postedToken)) {
        $error = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba kembali.';
    } elseif (!$selectedProduct) {
        $error = 'Produk yang dipilih tidak ditemukan.';
    } else {
        $allowedTypes = ['inner','tubetype','tubeless','other'];
        $tireType = strtolower(trim((string)($_POST['tire_type'] ?? 'other')));
        if (!in_array($tireType, $allowedTypes, true)) $tireType = 'other';

        $listingDescription = trim((string)($_POST['listing_description'] ?? ''));
        $heroDescription = trim((string)($_POST['hero_description'] ?? ''));
        $benefitsText = trim((string)($_POST['benefits_text'] ?? ''));
        $sizesText = trim((string)($_POST['sizes_text'] ?? ''));
        $performanceText = trim((string)($_POST['performance_text'] ?? ''));
        $technologiesText = trim((string)($_POST['technologies_text'] ?? ''));
        $logoPath = trim((string)($_POST['logo_path'] ?? 'uploads/general/fdr-tire-logo-red.png'));
        $virtualUrl = fdr_normalize_http_url((string)($_POST['virtual_tire_url'] ?? 'https://bengkelku.online'));
        $buyUrl = fdr_normalize_http_url((string)($_POST['product_url'] ?? ''));

        if ($logoPath === '') $logoPath = 'uploads/general/fdr-tire-logo-red.png';
        if (str_contains($logoPath, '..') || str_contains($logoPath, "\0")) {
            $error = 'Lokasi logo tidak valid.';
        } elseif (trim((string)($_POST['virtual_tire_url'] ?? '')) !== '' && $virtualUrl === '') {
            $error = 'URL Ganti Ban Virtual harus berupa alamat http:// atau https:// yang valid.';
        } elseif (trim((string)($_POST['product_url'] ?? '')) !== '' && $buyUrl === '') {
            $error = 'URL Buy Now harus berupa alamat http:// atau https:// yang valid.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO fdr_product_details
                    (product_id,tire_type,listing_description,hero_description,benefits_text,sizes_text,performance_text,technologies_text,logo_path,virtual_tire_url)
                    VALUES (?,?,?,?,?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE tire_type=VALUES(tire_type),listing_description=VALUES(listing_description),hero_description=VALUES(hero_description),benefits_text=VALUES(benefits_text),sizes_text=VALUES(sizes_text),performance_text=VALUES(performance_text),technologies_text=VALUES(technologies_text),logo_path=VALUES(logo_path),virtual_tire_url=VALUES(virtual_tire_url)");
                $stmt->execute([
                    $selectedId,
                    $tireType,
                    $listingDescription !== '' ? $listingDescription : null,
                    $heroDescription !== '' ? $heroDescription : null,
                    $benefitsText !== '' ? $benefitsText : null,
                    $sizesText !== '' ? $sizesText : null,
                    $performanceText !== '' ? $performanceText : null,
                    $technologiesText !== '' ? $technologiesText : null,
                    $logoPath,
                    $virtualUrl !== '' ? $virtualUrl : 'https://bengkelku.online',
                ]);
                $urlStmt = $pdo->prepare('UPDATE products SET product_url = ? WHERE id = ?');
                $urlStmt->execute([$buyUrl !== '' ? $buyUrl : null, $selectedId]);
                $pdo->commit();
                $success = 'Konten detail produk berhasil disimpan.';
                $selectedProduct['product_url'] = $buyUrl;
                $detail = array_merge($defaults, [
                    'tire_type' => $tireType,
                    'listing_description' => $listingDescription,
                    'hero_description' => $heroDescription,
                    'benefits_text' => $benefitsText,
                    'sizes_text' => $sizesText,
                    'performance_text' => $performanceText,
                    'technologies_text' => $technologiesText,
                    'logo_path' => $logoPath,
                    'virtual_tire_url' => $virtualUrl !== '' ? $virtualUrl : 'https://bengkelku.online',
                ]);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Gagal menyimpan konten. Pastikan SQL Phase 11 sudah dijalankan dan kolom product_url tersedia.';
            }
        }
    }
}

$line = fdr_line_presentation((string)($selectedProduct['category_slug'] ?? 'fdr-tire'));
$base = rtrim((string)BASE_URL, '/') . '/';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Konten Detail Ban FDR | Admin Star Parts Motor</title>
<link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--admin-red:#d90000;--admin-ink:#20242a;--admin-muted:#6b7280;--admin-line:#e6e8ec;--admin-bg:#f5f7fa}
body{margin:0;background:var(--admin-bg);color:var(--admin-ink);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
.fdr-admin-top{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px clamp(18px,4vw,54px);background:#111;color:#fff;border-top:5px solid var(--admin-red)}
.fdr-admin-top strong{font-size:18px}.fdr-admin-top span{display:block;margin-top:4px;color:#b9bec6;font-size:12px}.fdr-admin-links{display:flex;gap:10px;flex-wrap:wrap}.fdr-admin-links a{display:inline-flex;align-items:center;gap:8px;padding:11px 14px;background:#292d33;color:#fff;border-radius:10px;font-size:12px;font-weight:700}.fdr-admin-main{max-width:1240px;margin:28px auto;padding:0 18px 50px}.fdr-admin-title{margin:0 0 8px;font-size:28px}.fdr-admin-intro{margin:0 0 22px;max-width:820px;color:var(--admin-muted);line-height:1.7}.fdr-admin-panel{background:#fff;border:1px solid var(--admin-line);border-radius:16px;padding:24px;box-shadow:0 10px 30px #1d27300a;margin-bottom:18px}.fdr-admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.fdr-field{display:flex;flex-direction:column;gap:7px}.fdr-field.full{grid-column:1/-1}.fdr-field label{font-size:12px;font-weight:800}.fdr-field small{font-size:11px;color:var(--admin-muted);line-height:1.6}.fdr-field input,.fdr-field select,.fdr-field textarea{width:100%;box-sizing:border-box;border:1px solid #d8dce2;border-radius:10px;padding:12px 13px;background:#fff;color:#20242a;font:inherit;font-size:13px;outline:none}.fdr-field input:focus,.fdr-field select:focus,.fdr-field textarea:focus{border-color:var(--admin-red);box-shadow:0 0 0 3px #d9000012}.fdr-field textarea{min-height:110px;resize:vertical;line-height:1.6}.fdr-field textarea.large{min-height:150px}.fdr-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.fdr-btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;border:0;border-radius:10px;padding:12px 17px;background:var(--admin-red);color:white;font-weight:800;font-size:12px;cursor:pointer}.fdr-btn.secondary{background:#edf0f4;color:#222}.fdr-alert{margin:0 0 18px;padding:13px 16px;border-radius:10px;font-size:13px}.fdr-alert.error{background:#fff1f1;color:#9d1010;border:1px solid #ffd1d1}.fdr-alert.success{background:#eefbf2;color:#176c35;border:1px solid #c7efd4}.fdr-help{padding:14px 16px;border-radius:12px;background:#f7f8fa;color:#606874;font-size:12px;line-height:1.8}.fdr-line-preview{display:flex;align-items:center;gap:12px;margin:0 0 18px;padding:14px;border-radius:12px;color:#fff;background:<?= e($line['color']); ?>}.fdr-line-preview img{width:104px;height:auto;max-height:30px;object-fit:contain}.fdr-line-preview strong{font-size:13px}.fdr-product-preview{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.fdr-product-preview img{width:70px;height:85px;object-fit:contain;background:#f5f6f7;border-radius:10px}.fdr-product-preview strong{display:block}.fdr-product-preview a{color:var(--admin-red);font-size:12px;font-weight:700}@media(max-width:700px){.fdr-admin-top{align-items:flex-start;flex-direction:column}.fdr-admin-grid{grid-template-columns:1fr}.fdr-field.full{grid-column:auto}.fdr-admin-panel{padding:16px}.fdr-admin-title{font-size:23px}}
</style>
</head>
<body>
<?php render_admin_sidebar_assets(); ?>
<?php render_admin_sidebar('fdr-details.php'); ?>
<header class="fdr-admin-top"><div><strong>STAR PARTS MOTOR · ADMIN</strong><span>Konten produk FDR · login sebagai <?= e($adminName); ?></span></div><nav class="fdr-admin-links"><a href="<?= e(base_url('admin/products.php')); ?>"><i class="fa-solid fa-box-open"></i> Manajemen Produk</a><a href="<?= e(base_url('admin/dashboard.php')); ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a><a href="<?= e(base_url('index.php')); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Website</a></nav></header>
<main class="fdr-admin-main">
<h1 class="fdr-admin-title">Konten Detail Ban FDR</h1>
<p class="fdr-admin-intro">Edit teks yang muncul di kartu katalog dan halaman detail ban. Jika sebuah kolom dikosongkan, website akan menggunakan deskripsi/spesifikasi produk yang sudah tersimpan. Warna latar halaman detail otomatis mengikuti kategori: FDR Tire merah, FDR Ultimate hitam, FDR Champion kuning emas.</p>
<?php if ($error !== ''): ?><div class="fdr-alert error"><?= e($error); ?></div><?php endif; ?>
<?php if ($success !== ''): ?><div class="fdr-alert success"><?= e($success); ?></div><?php endif; ?>
<?php if (!$hasDetailsTable): ?>
<div class="fdr-admin-panel"><h2>Database belum diperbarui</h2><p>Jalankan file <code>database/phase11_fdr_product_experience.sql</code> melalui phpMyAdmin pada database Star Parts Motor, lalu muat ulang halaman ini.</p></div>
<?php else: ?>
<div class="fdr-admin-panel">
<form method="get" action="<?= e(base_url('admin/fdr-details.php')); ?>" class="fdr-product-preview">
<div class="fdr-field" style="flex:1;min-width:240px"><label for="id">Pilih Produk</label><select name="id" id="id" onchange="this.form.submit()">
<?php foreach ($products as $item): ?><option value="<?= (int)$item['id']; ?>" <?= (int)$item['id']===$selectedId?'selected':''; ?>><?= e($item['name'] . ' · ' . ($item['category_name'] ?? 'Tanpa kategori')); ?></option><?php endforeach; ?>
</select></div>
<noscript><button class="fdr-btn secondary" type="submit">Buka Produk</button></noscript>
<?php if ($selectedProduct): ?><div style="display:flex;align-items:center;gap:7px"><a href="<?= e(product_url((string)$selectedProduct['slug'])); ?>" target="_blank" rel="noopener">Lihat detail website ↗</a></div><?php endif; ?>
</form>
</div>
<?php if ($selectedProduct): ?>
<div class="fdr-line-preview"><img src="<?= e(media_url('uploads/general/fdr-tire-logo-white.png')); ?>" alt="FDR logo"><strong><?= e($line['title']); ?> · Warna detail otomatis</strong></div>
<div class="fdr-admin-panel">
<div class="fdr-product-preview" style="margin-bottom:20px"><?php if (!empty($selectedProduct['image'])): ?><img src="<?= e(media_url((string)$selectedProduct['image'])); ?>" alt=""><?php endif; ?><div><strong><?= e((string)$selectedProduct['name']); ?></strong><span style="display:block;color:#6b7280;font-size:12px;margin-top:5px"><?= e((string)($selectedProduct['category_name'] ?? '')); ?> · slug: <?= e((string)$selectedProduct['slug']); ?></span></div></div>
<form method="post" action="<?= e(base_url('admin/fdr-details.php?id=' . $selectedId)); ?>">
<input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>"><input type="hidden" name="product_id" value="<?= $selectedId; ?>">
<div class="fdr-admin-grid">
<div class="fdr-field"><label for="tire_type">Jenis untuk submenu Browse</label><select name="tire_type" id="tire_type"><option value="inner" <?= $detail['tire_type']==='inner'?'selected':''; ?>>Inner Tire</option><option value="tubetype" <?= $detail['tire_type']==='tubetype'?'selected':''; ?>>Tubetype Tire</option><option value="tubeless" <?= $detail['tire_type']==='tubeless'?'selected':''; ?>>Tubeless Tire</option><option value="other" <?= $detail['tire_type']==='other'?'selected':''; ?>>Other / belum ditentukan</option></select><small>Pengaturan ini menentukan di submenu Browse mana produk muncul.</small></div>
<div class="fdr-field"><label for="logo_path">Lokasi logo pada kartu katalog</label><input type="text" name="logo_path" id="logo_path" value="<?= e((string)$detail['logo_path']); ?>" placeholder="uploads/general/fdr-tire-logo-red.png"><small>Gunakan lokasi file relatif dari folder website atau URL HTTPS.</small></div>
<div class="fdr-field full"><label for="listing_description">Deskripsi pada kartu katalog</label><textarea name="listing_description" id="listing_description" maxlength="1000" placeholder="Deskripsi singkat yang tampil di bawah nama ban pada daftar produk."><?= e((string)$detail['listing_description']); ?></textarea><small>Kosongkan agar memakai Deskripsi Singkat pada menu Manajemen Produk.</small></div>
<div class="fdr-field full"><label for="hero_description">Pengantar di halaman detail</label><textarea name="hero_description" id="hero_description" maxlength="3000" placeholder="Kalimat pengantar produk di sisi detail."><?= e((string)$detail['hero_description']); ?></textarea><small>Kosongkan agar memakai Deskripsi Singkat yang sudah ada.</small></div>
<div class="fdr-field full"><label for="benefits_text">Keunggulan</label><textarea class="large" name="benefits_text" id="benefits_text" placeholder="Soft compound | Untuk daya cengkeram maksimal saat balapan&#10;Ban kering | Cocok untuk digunakan di trek kering"><?= e((string)$detail['benefits_text']); ?></textarea><small>Satu keunggulan per baris. Format: <code>Judul | Keterangan</code>. Bila kosong, bagian “Keunggulan” dari Deskripsi Produk lama akan ditampilkan.</small></div>
<div class="fdr-field full"><label for="sizes_text">Size &amp; Rims / ukuran dan harga</label><textarea class="large" name="sizes_text" id="sizes_text" placeholder="60/80-17 TT | Rp 391.000 | Rp 371.450 | TT&#10;100/90-12 TL | Rp 300.000 | Rp 270.000 | TL"><?= e((string)$detail['sizes_text']); ?></textarea><small>Satu ukuran per baris. Format: <code>Ukuran | Harga normal | Harga promo | TL/TT (opsional)</code>. Gunakan harga resmi yang telah dikonfirmasi.</small></div>
<div class="fdr-field full"><label for="performance_text">Performance Ratings</label><textarea name="performance_text" id="performance_text" placeholder="Wet Grip | 3&#10;Mileage | 4&#10;Handling | 4"><?= e((string)$detail['performance_text']); ?></textarea><small>Satu baris per indikator. Format: <code>Nama indikator | Nilai 1–5</code>. Jika belum ada nilai resmi, kosongkan agar tidak menampilkan nilai buatan.</small></div>
<div class="fdr-field full"><label for="technologies_text">Technology / Teknologi</label><textarea class="large" name="technologies_text" id="technologies_text" placeholder="S-Ride | Desain konstruksi mempertimbangkan kenyamanan kendali ban."><?= e((string)$detail['technologies_text']); ?></textarea><small>Satu teknologi per baris. Format: <code>Nama teknologi | Penjelasan</code>. Jika kosong, konten Technology dari deskripsi lama dipakai.</small></div>
<div class="fdr-field full"><label for="product_url">Link tujuan tombol Buy Now</label><input type="url" name="product_url" id="product_url" value="<?= e($buyUrl); ?>" placeholder="https://bengkelku.online/detail-produk"><small>Isi dengan URL halaman detail produk yang tepat di <strong>bengkelku.online</strong>. Jika URL detail spesifik belum diisi, tombol sementara mengarah ke bengkelku.online; isi URL produk yang tepat agar langsung membuka halaman detailnya.</small></div>
<div class="fdr-field full"><label for="virtual_tire_url">Link tombol Ganti Ban Virtual</label><input type="url" name="virtual_tire_url" id="virtual_tire_url" value="<?= e((string)$detail['virtual_tire_url']); ?>" placeholder="https://bengkelku.online"><small>Default mengarah ke https://bengkelku.online. Dapat diubah bila tujuan virtual tire memiliki URL khusus.</small></div>
</div>
<div class="fdr-actions"><button type="submit" class="fdr-btn"><i class="fa-solid fa-floppy-disk"></i> Simpan Konten Produk</button><a class="fdr-btn secondary" href="<?= e(base_url('admin/products.php?action=edit&id=' . $selectedId)); ?>"><i class="fa-solid fa-pen-to-square"></i> Edit data produk &amp; gambar</a><a class="fdr-btn secondary" href="<?= e(product_url((string)$selectedProduct['slug'])); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-eye"></i> Preview halaman</a></div>
</form>
</div>
<?php endif; ?>
<div class="fdr-help"><strong>Tips:</strong> foto produk diatur dari <em>Manajemen Produk → Edit → Foto Utama Produk</em>. Nama produk, Deskripsi Produk, SEO, serta link Buy Now juga tersimpan pada data produk utama. Teks di halaman ini hanya mengatur konten tambahan katalog FDR.</div>
<?php endif; ?>
</main>
</body></html>

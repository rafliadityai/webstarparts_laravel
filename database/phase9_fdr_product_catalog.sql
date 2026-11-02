-- Star Parts Motor Phase 9: FDR product catalog seed (27 models).
-- Run after the website's existing database setup and migrations (Phases 1-8).
-- Safe to rerun: category rows are matched by slug/name; product rows by slug.
-- Source list: Rekap_Ban_FDR.xlsx. Existing TXT descriptions are used where available.

START TRANSACTION;

INSERT INTO product_categories (name, slug, description, image, sort_order, is_active)
SELECT 'FDR Tire', 'fdr-tire', 'Lini ban FDR untuk pemakaian harian, sport touring, dan berbagai kebutuhan berkendara.', 'uploads/general/display-ban-fdr.webp', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE slug = 'fdr-tire' OR LOWER(TRIM(name)) = LOWER('FDR Tire'));
UPDATE product_categories SET name = 'FDR Tire', slug = 'fdr-tire', description = 'Lini ban FDR untuk pemakaian harian, sport touring, dan berbagai kebutuhan berkendara.', sort_order = 3, is_active = 1 WHERE slug = 'fdr-tire' OR LOWER(TRIM(name)) = LOWER('FDR Tire');

INSERT INTO product_categories (name, slug, description, image, sort_order, is_active)
SELECT 'FDR Ultimate', 'fdr-ultimate', 'Lini FDR Ultimate dengan fokus kompon dan performa untuk grip serta touring.', 'uploads/general/display-ban-fdr.webp', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE slug = 'fdr-ultimate' OR LOWER(TRIM(name)) = LOWER('FDR Ultimate'));
UPDATE product_categories SET name = 'FDR Ultimate', slug = 'fdr-ultimate', description = 'Lini FDR Ultimate dengan fokus kompon dan performa untuk grip serta touring.', sort_order = 2, is_active = 1 WHERE slug = 'fdr-ultimate' OR LOWER(TRIM(name)) = LOWER('FDR Ultimate');

INSERT INTO product_categories (name, slug, description, image, sort_order, is_active)
SELECT 'FDR Champion', 'fdr-champion', 'Lini ban FDR Champion untuk kebutuhan balap dan performa lintasan.', 'uploads/general/display-ban-fdr.webp', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE slug = 'fdr-champion' OR LOWER(TRIM(name)) = LOWER('FDR Champion'));
UPDATE product_categories SET name = 'FDR Champion', slug = 'fdr-champion', description = 'Lini ban FDR Champion untuk kebutuhan balap dan performa lintasan.', sort_order = 1, is_active = 1 WHERE slug = 'fdr-champion' OR LOWER(TRIM(name)) = LOWER('FDR Champion');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1),
    name = 'Draxstar',
    short_description = 'Khusus drag race',
    description = '<p>Ban motor khusus untuk drag race</p><h3>Keunggulan</h3><p><strong>Soft compound</strong><br>Untuk daya cengkeram maksimal saat balapan</p><p><strong>Ban kering</strong><br>Cocok untuk digunakan di trek kering</p><h3>Size &amp; Rims</h3><ul><li>60/80-17 TTRp 391.000 Rp 371.450</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><h3>Technology</h3><p><strong>SCS</strong><br>Teknologi rantai karbon pada material ban FDR membuat daya cengkeram ban maksimal dan menjaga kestabilan temperatur sehingga performa ban tetap berada pada puncaknya.</p><p>Kategori: FDR Champion. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/Draxstar.png',
    meta_title = 'Ban FDR Draxstar | PT Star Parts Motor',
    meta_description = 'Draxstar: Khusus drag race. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Draxstar',
    is_featured = 0,
    is_active = 1,
    sort_order = 1
WHERE slug = 'draxstar';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1), 'Draxstar', 'draxstar', 'Khusus drag race', '<p>Ban motor khusus untuk drag race</p><h3>Keunggulan</h3><p><strong>Soft compound</strong><br>Untuk daya cengkeram maksimal saat balapan</p><p><strong>Ban kering</strong><br>Cocok untuk digunakan di trek kering</p><h3>Size &amp; Rims</h3><ul><li>60/80-17 TTRp 391.000 Rp 371.450</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><h3>Technology</h3><p><strong>SCS</strong><br>Teknologi rantai karbon pada material ban FDR membuat daya cengkeram ban maksimal dan menjaga kestabilan temperatur sehingga performa ban tetap berada pada puncaknya.</p><p>Kategori: FDR Champion. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/Draxstar.png', '[]', NULL, 'Ban FDR Draxstar | PT Star Parts Motor', 'Draxstar: Khusus drag race. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Draxstar', 0, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'draxstar');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1),
    name = 'Sport MP96',
    short_description = 'Ban balap semi-slick',
    description = '<p><strong>Ban balap semi-slick.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/SPORT MP96.png',
    meta_title = 'Ban FDR Sport MP96 | PT Star Parts Motor',
    meta_description = 'Sport MP96: Ban balap semi-slick. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Sport MP96',
    is_featured = 0,
    is_active = 1,
    sort_order = 2
WHERE slug = 'sport-mp96';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1), 'Sport MP96', 'sport-mp96', 'Ban balap semi-slick', '<p><strong>Ban balap semi-slick.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/SPORT MP96.png', '[]', NULL, 'Ban FDR Sport MP96 | PT Star Parts Motor', 'Sport MP96: Ban balap semi-slick. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Sport MP96', 0, 1, 2
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sport-mp96');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1),
    name = 'Sport MP27',
    short_description = 'Ban balap sirkuit kering',
    description = '<p><strong>Ban balap sirkuit kering.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/SPORT MP27.png',
    meta_title = 'Ban FDR Sport MP27 | PT Star Parts Motor',
    meta_description = 'Sport MP27: Ban balap sirkuit kering. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Sport MP27',
    is_featured = 0,
    is_active = 1,
    sort_order = 3
WHERE slug = 'sport-mp27';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1), 'Sport MP27', 'sport-mp27', 'Ban balap sirkuit kering', '<p><strong>Ban balap sirkuit kering.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/SPORT MP27.png', '[]', NULL, 'Ban FDR Sport MP27 | PT Star Parts Motor', 'Sport MP27: Ban balap sirkuit kering. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Sport MP27', 0, 1, 3
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sport-mp27');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1),
    name = 'Sport MP57',
    short_description = 'Ban balap sirkuit kering & dingin',
    description = '<p><strong>Ban balap sirkuit kering &amp; dingin.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/SPORT MP57.png',
    meta_title = 'Ban FDR Sport MP57 | PT Star Parts Motor',
    meta_description = 'Sport MP57: Ban balap sirkuit kering & dingin. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Sport MP57',
    is_featured = 0,
    is_active = 1,
    sort_order = 4
WHERE slug = 'sport-mp57';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1), 'Sport MP57', 'sport-mp57', 'Ban balap sirkuit kering & dingin', '<p><strong>Ban balap sirkuit kering &amp; dingin.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/SPORT MP57.png', '[]', NULL, 'Ban FDR Sport MP57 | PT Star Parts Motor', 'Sport MP57: Ban balap sirkuit kering & dingin. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Sport MP57', 0, 1, 4
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sport-mp57');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1),
    name = 'Sport MP76',
    short_description = 'Ban balap sirkuit kering/basah',
    description = '<p><strong>Ban balap sirkuit kering/basah.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/SPORT MP76.png',
    meta_title = 'Ban FDR Sport MP76 | PT Star Parts Motor',
    meta_description = 'Sport MP76: Ban balap sirkuit kering/basah. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Sport MP76',
    is_featured = 0,
    is_active = 1,
    sort_order = 5
WHERE slug = 'sport-mp76';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1), 'Sport MP76', 'sport-mp76', 'Ban balap sirkuit kering/basah', '<p><strong>Ban balap sirkuit kering/basah.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/SPORT MP76.png', '[]', NULL, 'Ban FDR Sport MP76 | PT Star Parts Motor', 'Sport MP76: Ban balap sirkuit kering/basah. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Sport MP76', 0, 1, 5
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sport-mp76');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1),
    name = 'Sport XR',
    short_description = 'Ban balap trek basah/hujan',
    description = '<p><strong>Ban balap trek basah/hujan.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/SPORT XR.png',
    meta_title = 'Ban FDR Sport XR | PT Star Parts Motor',
    meta_description = 'Sport XR: Ban balap trek basah/hujan. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Sport XR',
    is_featured = 0,
    is_active = 1,
    sort_order = 6
WHERE slug = 'sport-xr';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-champion' LIMIT 1), 'Sport XR', 'sport-xr', 'Ban balap trek basah/hujan', '<p><strong>Ban balap trek basah/hujan.</strong></p><p>Kategori: FDR Champion. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/SPORT XR.png', '[]', NULL, 'Ban FDR Sport XR | PT Star Parts Motor', 'Sport XR: Ban balap trek basah/hujan. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Sport XR', 0, 1, 6
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sport-xr');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1),
    name = 'Blaze',
    short_description = 'Kompon intermediate untuk grip maksimal',
    description = '<p><strong>Kompon intermediate untuk grip maksimal.</strong></p><p>Kategori: FDR Ultimate. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/BLAZE.png',
    meta_title = 'Ban FDR Blaze | PT Star Parts Motor',
    meta_description = 'Blaze: Kompon intermediate untuk grip maksimal. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Blaze',
    is_featured = 0,
    is_active = 1,
    sort_order = 7
WHERE slug = 'blaze';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1), 'Blaze', 'blaze', 'Kompon intermediate untuk grip maksimal', '<p><strong>Kompon intermediate untuk grip maksimal.</strong></p><p>Kategori: FDR Ultimate. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/BLAZE.png', '[]', NULL, 'Ban FDR Blaze | PT Star Parts Motor', 'Blaze: Kompon intermediate untuk grip maksimal. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Blaze', 0, 1, 7
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'blaze');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1),
    name = 'Blaze MP Tourer',
    short_description = 'Ultimate Gen-2 untuk touring',
    description = '<p>Ultimate Gen-2, konstruksi &amp; kompon baru untuk daya cengkeram maksimal</p><h3>Keunggulan</h3><p><strong>Soft compound Gen-2</strong><br>Kompon Gen-2 dengan new synthetic rubber &amp; carbon black untuk tingkatkan grip, hardness, cornering, durability &amp; wearlife</p><p><strong>New Construction</strong><br>Profil ban lebih bulat untuk meningkatkan performa cornering ketika berkendara</p><p><strong>Touring tire</strong><br>Didesain dengan kemampuan jelajah tinggi, cocok untuk sport touring</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p><strong>Multistep pattern</strong><br>meningkatkan kestabilan dan daya cengkram</p><h3>Size &amp; Rims</h3><ul><li>120/70-15 TLRp 710.000 Rp 639.000</li><li>150/70-14 TLRp 1.010.000 Rp 909.000</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><h3>Technology</h3><p><strong>ABLS</strong><br>Desain dan material sistem penguncian bead wire dirancang agar mampu menjaga posisi ban tetap di pelek sehingga kestabilan motor terjaga.</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/Blaze MP Tourer.png',
    meta_title = 'Ban FDR Blaze MP Tourer | PT Star Parts Motor',
    meta_description = 'Blaze MP Tourer: Ultimate Gen-2 untuk touring. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Blaze MP Tourer',
    is_featured = 1,
    is_active = 1,
    sort_order = 8
WHERE slug = 'blaze-mp-tourer';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1), 'Blaze MP Tourer', 'blaze-mp-tourer', 'Ultimate Gen-2 untuk touring', '<p>Ultimate Gen-2, konstruksi &amp; kompon baru untuk daya cengkeram maksimal</p><h3>Keunggulan</h3><p><strong>Soft compound Gen-2</strong><br>Kompon Gen-2 dengan new synthetic rubber &amp; carbon black untuk tingkatkan grip, hardness, cornering, durability &amp; wearlife</p><p><strong>New Construction</strong><br>Profil ban lebih bulat untuk meningkatkan performa cornering ketika berkendara</p><p><strong>Touring tire</strong><br>Didesain dengan kemampuan jelajah tinggi, cocok untuk sport touring</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p><strong>Multistep pattern</strong><br>meningkatkan kestabilan dan daya cengkram</p><h3>Size &amp; Rims</h3><ul><li>120/70-15 TLRp 710.000 Rp 639.000</li><li>150/70-14 TLRp 1.010.000 Rp 909.000</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><h3>Technology</h3><p><strong>ABLS</strong><br>Desain dan material sistem penguncian bead wire dirancang agar mampu menjaga posisi ban tetap di pelek sehingga kestabilan motor terjaga.</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/Blaze MP Tourer.png', '[]', NULL, 'Ban FDR Blaze MP Tourer | PT Star Parts Motor', 'Blaze MP Tourer: Ultimate Gen-2 untuk touring. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Blaze MP Tourer', 1, 1, 8
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'blaze-mp-tourer');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1),
    name = 'Blaze MP Road',
    short_description = 'Ultimate Gen-2 untuk harian/jalan raya',
    description = '<p>Ultimate Gen-2, konstruksi &amp; kompon baru untuk daya cengkeram maksimal</p><h3>Keunggulan</h3><p><strong>Soft compound Gen-2</strong><br>Kompon Gen-2 dengan new synthetic rubber &amp; carbon black untuk tingkatkan grip, hardness, cornering, durability &amp; wearlife</p><p><strong>New Construction</strong><br>Profil ban lebih bulat untuk meningkatkan performa cornering ketika berkendara</p><p><strong>Touring tire</strong><br>Didesain dengan kemampuan jelajah tinggi, cocok untuk sport touring</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p><strong>Multistep pattern</strong><br>meningkatkan kestabilan dan daya cengkram</p><h3>Size &amp; Rims</h3><ul><li>120/70-13 TLRp 449.000 Rp 404.100</li><li>140/70-13 TLRp 581.000 Rp 522.900</li><li>110/70-14 TLRp 431.000 Rp 387.900</li><li>130/70-13 TLRp 536.000 Rp 482.400</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><h3>Technology</h3><p><strong>ABLS</strong><br>Desain dan material sistem penguncian bead wire dirancang agar mampu menjaga posisi ban tetap di pelek sehingga kestabilan motor terjaga.</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/Blaze MP Road.png',
    meta_title = 'Ban FDR Blaze MP Road | PT Star Parts Motor',
    meta_description = 'Blaze MP Road: Ultimate Gen-2 untuk harian/jalan raya. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Blaze MP Road',
    is_featured = 1,
    is_active = 1,
    sort_order = 9
WHERE slug = 'blaze-mp-road';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1), 'Blaze MP Road', 'blaze-mp-road', 'Ultimate Gen-2 untuk harian/jalan raya', '<p>Ultimate Gen-2, konstruksi &amp; kompon baru untuk daya cengkeram maksimal</p><h3>Keunggulan</h3><p><strong>Soft compound Gen-2</strong><br>Kompon Gen-2 dengan new synthetic rubber &amp; carbon black untuk tingkatkan grip, hardness, cornering, durability &amp; wearlife</p><p><strong>New Construction</strong><br>Profil ban lebih bulat untuk meningkatkan performa cornering ketika berkendara</p><p><strong>Touring tire</strong><br>Didesain dengan kemampuan jelajah tinggi, cocok untuk sport touring</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p><strong>Multistep pattern</strong><br>meningkatkan kestabilan dan daya cengkram</p><h3>Size &amp; Rims</h3><ul><li>120/70-13 TLRp 449.000 Rp 404.100</li><li>140/70-13 TLRp 581.000 Rp 522.900</li><li>110/70-14 TLRp 431.000 Rp 387.900</li><li>130/70-13 TLRp 536.000 Rp 482.400</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><h3>Technology</h3><p><strong>ABLS</strong><br>Desain dan material sistem penguncian bead wire dirancang agar mampu menjaga posisi ban tetap di pelek sehingga kestabilan motor terjaga.</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/Blaze MP Road.png', '[]', NULL, 'Ban FDR Blaze MP Road | PT Star Parts Motor', 'Blaze MP Road: Ultimate Gen-2 untuk harian/jalan raya. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Blaze MP Road', 1, 1, 9
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'blaze-mp-road');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1),
    name = 'Velogrip',
    short_description = 'Ban intermediate compound sporty',
    description = '<p>Ban intermediate compound dengan kembangan simple dan sporty</p><h3>Keunggulan</h3><p><strong>Intermediate compound</strong><br>Memberikan daya cengkeram yang lebih baik untuk penggunaan harian yang menginginkan performa lebih</p><p><strong>Touring tire</strong><br>Didesain dengan kemampuan jelajah tinggi, cocok untuk sport touring</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p><strong>Multistep pattern</strong><br>meningkatkan kestabilan dan daya cengkram</p><h3>Size &amp; Rims</h3><ul><li>100/90-12 TLRp 312.000 Rp 280.800</li><li>110/90-12 TLRp 384.000 Rp 345.600</li><li>110/70-13 TLRp 386.000 Rp 347.400</li><li>130/70-13 TLRp 491.000 Rp 441.900</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/Velogrip.png',
    meta_title = 'Ban FDR Velogrip | PT Star Parts Motor',
    meta_description = 'Velogrip: Ban intermediate compound sporty. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Velogrip',
    is_featured = 1,
    is_active = 1,
    sort_order = 10
WHERE slug = 'velogrip';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1), 'Velogrip', 'velogrip', 'Ban intermediate compound sporty', '<p>Ban intermediate compound dengan kembangan simple dan sporty</p><h3>Keunggulan</h3><p><strong>Intermediate compound</strong><br>Memberikan daya cengkeram yang lebih baik untuk penggunaan harian yang menginginkan performa lebih</p><p><strong>Touring tire</strong><br>Didesain dengan kemampuan jelajah tinggi, cocok untuk sport touring</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p><strong>Multistep pattern</strong><br>meningkatkan kestabilan dan daya cengkram</p><h3>Size &amp; Rims</h3><ul><li>100/90-12 TLRp 312.000 Rp 280.800</li><li>110/90-12 TLRp 384.000 Rp 345.600</li><li>110/70-13 TLRp 386.000 Rp 347.400</li><li>130/70-13 TLRp 491.000 Rp 441.900</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/Velogrip.png', '[]', NULL, 'Ban FDR Velogrip | PT Star Parts Motor', 'Velogrip: Ban intermediate compound sporty. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Velogrip', 1, 1, 10
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'velogrip');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1),
    name = 'Hover',
    short_description = 'Ban dual purpose (aspal & tanah)',
    description = '<p>Ban dual purpose yang tetap grip di jalan aspal</p><h3>Keunggulan</h3><p><strong>Intermediate compound</strong><br>Memberikan daya cengkeram yang lebih baik untuk penggunaan harian yang menginginkan performa lebih</p><p><strong>Off road style</strong><br>Tangguh hadapi medan tanah, bebatuan dan tetap nyaman di jalan aspal terkelupas</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p><strong>Multistep pattern</strong><br>meningkatkan kestabilan dan daya cengkram</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/Hover.png',
    meta_title = 'Ban FDR Hover | PT Star Parts Motor',
    meta_description = 'Hover: Ban dual purpose (aspal & tanah). Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Hover',
    is_featured = 0,
    is_active = 1,
    sort_order = 11
WHERE slug = 'hover';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1), 'Hover', 'hover', 'Ban dual purpose (aspal & tanah)', '<p>Ban dual purpose yang tetap grip di jalan aspal</p><h3>Keunggulan</h3><p><strong>Intermediate compound</strong><br>Memberikan daya cengkeram yang lebih baik untuk penggunaan harian yang menginginkan performa lebih</p><p><strong>Off road style</strong><br>Tangguh hadapi medan tanah, bebatuan dan tetap nyaman di jalan aspal terkelupas</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p><strong>Multistep pattern</strong><br>meningkatkan kestabilan dan daya cengkram</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/Hover.png', '[]', NULL, 'Ban FDR Hover | PT Star Parts Motor', 'Hover: Ban dual purpose (aspal & tanah). Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Hover', 0, 1, 11
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'hover');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1),
    name = 'Maxtreme',
    short_description = 'Ban supermoto high performance',
    description = '<p>Ban high performance cocok untuk motor dengan tampilan supermoto</p><h3>Keunggulan</h3><p><strong>Intermediate compound</strong><br>Memberikan daya cengkeram yang lebih baik untuk penggunaan harian yang menginginkan performa lebih</p><p><strong>Stabil</strong><br>Rasio ban 80 atau 70 memberikan kestabilan lebih saat bermanuver</p><p><strong>Touring tire</strong><br>Didesain dengan kemampuan jelajah tinggi, cocok untuk sport touring</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/Maxtreme.png',
    meta_title = 'Ban FDR Maxtreme | PT Star Parts Motor',
    meta_description = 'Maxtreme: Ban supermoto high performance. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Maxtreme',
    is_featured = 0,
    is_active = 1,
    sort_order = 12
WHERE slug = 'maxtreme';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-ultimate' LIMIT 1), 'Maxtreme', 'maxtreme', 'Ban supermoto high performance', '<p>Ban high performance cocok untuk motor dengan tampilan supermoto</p><h3>Keunggulan</h3><p><strong>Intermediate compound</strong><br>Memberikan daya cengkeram yang lebih baik untuk penggunaan harian yang menginginkan performa lebih</p><p><strong>Stabil</strong><br>Rasio ban 80 atau 70 memberikan kestabilan lebih saat bermanuver</p><p><strong>Touring tire</strong><br>Didesain dengan kemampuan jelajah tinggi, cocok untuk sport touring</p><p><strong>Progresif TWI</strong><br>Indikator keausan ban progresif yang  memberi peringatan dini</p><p>Kategori: FDR Ultimate. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/Maxtreme.png', '[]', NULL, 'Ban FDR Maxtreme | PT Star Parts Motor', 'Maxtreme: Ban supermoto high performance. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Maxtreme', 0, 1, 12
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'maxtreme');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'City Go',
    short_description = 'Ban gaya retro modern (ring 12)',
    description = '<p>Ban ukuran rim 12 inchi dengan gaya retro modern yang cocok untuk motor matic</p><h3>Keunggulan</h3><p><strong>Regular compund</strong><br>Kompon ban dirancang khusus untuk punya daya cengkeram maksimal untuk penggunaan harian</p><p><strong>Nyaman</strong><br>Rasio ban 90 memberikan kenyamanan saat berkendara</p><p><strong>City ride</strong><br>Cocok untuk penggunaan di jalan beraspal</p><h3>Size &amp; Rims</h3><ul><li>100/90-12 TLRp 300.000 Rp 270.000</li><li>110/90-12 TLRp 356.000 Rp 320.400</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><h3>Technology</h3><p><strong>S-Ride</strong><br>Desain konstruksi mempertimbangkan kenyamanan kendali ban sehingga motor lebih nyaman dikendarai.</p><p>Kategori: FDR Tire. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/City Go.png',
    meta_title = 'Ban FDR City Go | PT Star Parts Motor',
    meta_description = 'City Go: Ban gaya retro modern (ring 12). Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR City Go',
    is_featured = 0,
    is_active = 1,
    sort_order = 13
WHERE slug = 'city-go';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'City Go', 'city-go', 'Ban gaya retro modern (ring 12)', '<p>Ban ukuran rim 12 inchi dengan gaya retro modern yang cocok untuk motor matic</p><h3>Keunggulan</h3><p><strong>Regular compund</strong><br>Kompon ban dirancang khusus untuk punya daya cengkeram maksimal untuk penggunaan harian</p><p><strong>Nyaman</strong><br>Rasio ban 90 memberikan kenyamanan saat berkendara</p><p><strong>City ride</strong><br>Cocok untuk penggunaan di jalan beraspal</p><h3>Size &amp; Rims</h3><ul><li>100/90-12 TLRp 300.000 Rp 270.000</li><li>110/90-12 TLRp 356.000 Rp 320.400</li></ul><h3>Performance Ratings</h3><p>Penilaian diperoleh dari tes-tes yang dilakukan oleh Departmen Kualitas PT. SRI Ratings are based on tests conducted by PT. SRI Quality Department</p><h3>Technology</h3><p><strong>S-Ride</strong><br>Desain konstruksi mempertimbangkan kenyamanan kendali ban sehingga motor lebih nyaman dikendarai.</p><p>Kategori: FDR Tire. Untuk konfirmasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/City Go.png', '[]', NULL, 'Ban FDR City Go | PT Star Parts Motor', 'City Go: Ban gaya retro modern (ring 12). Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR City Go', 0, 1, 13
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'city-go');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Sport Zevo',
    short_description = 'Ban sport touring harian',
    description = '<p><strong>Ban sport touring harian.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'uploads/general/display-ban-fdr.webp',
    meta_title = 'Ban FDR Sport Zevo | PT Star Parts Motor',
    meta_description = 'Sport Zevo: Ban sport touring harian. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Sport Zevo',
    is_featured = 0,
    is_active = 1,
    sort_order = 14
WHERE slug = 'sport-zevo';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Sport Zevo', 'sport-zevo', 'Ban sport touring harian', '<p><strong>Ban sport touring harian.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'uploads/general/display-ban-fdr.webp', '[]', NULL, 'Ban FDR Sport Zevo | PT Star Parts Motor', 'Sport Zevo: Ban sport touring harian. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Sport Zevo', 0, 1, 14
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sport-zevo');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Flemino',
    short_description = 'Ban matic ukuran standar',
    description = '<p><strong>Ban matic ukuran standar.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'uploads/general/display-ban-fdr.webp',
    meta_title = 'Ban FDR Flemino | PT Star Parts Motor',
    meta_description = 'Flemino: Ban matic ukuran standar. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Flemino',
    is_featured = 0,
    is_active = 1,
    sort_order = 15
WHERE slug = 'flemino';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Flemino', 'flemino', 'Ban matic ukuran standar', '<p><strong>Ban matic ukuran standar.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'uploads/general/display-ban-fdr.webp', '[]', NULL, 'Ban FDR Flemino | PT Star Parts Motor', 'Flemino: Ban matic ukuran standar. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Flemino', 0, 1, 15
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'flemino');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'City Force',
    short_description = 'Kembangan zig-zag untuk stabilitas',
    description = '<p><strong>Kembangan zig-zag untuk stabilitas.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'uploads/general/display-ban-fdr.webp',
    meta_title = 'Ban FDR City Force | PT Star Parts Motor',
    meta_description = 'City Force: Kembangan zig-zag untuk stabilitas. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR City Force',
    is_featured = 0,
    is_active = 1,
    sort_order = 16
WHERE slug = 'city-force';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'City Force', 'city-force', 'Kembangan zig-zag untuk stabilitas', '<p><strong>Kembangan zig-zag untuk stabilitas.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'uploads/general/display-ban-fdr.webp', '[]', NULL, 'Ban FDR City Force | PT Star Parts Motor', 'City Force: Kembangan zig-zag untuk stabilitas. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR City Force', 0, 1, 16
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'city-force');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Genzi PRO',
    short_description = 'Pattern tegas dan tajam',
    description = '<p><strong>Pattern tegas dan tajam.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/Genzi PRO.png',
    meta_title = 'Ban FDR Genzi PRO | PT Star Parts Motor',
    meta_description = 'Genzi PRO: Pattern tegas dan tajam. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Genzi PRO',
    is_featured = 0,
    is_active = 1,
    sort_order = 17
WHERE slug = 'genzi-pro';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Genzi PRO', 'genzi-pro', 'Pattern tegas dan tajam', '<p><strong>Pattern tegas dan tajam.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/Genzi PRO.png', '[]', NULL, 'Ban FDR Genzi PRO | PT Star Parts Motor', 'Genzi PRO: Pattern tegas dan tajam. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Genzi PRO', 0, 1, 17
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'genzi-pro');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Flemmo PRO',
    short_description = 'Tampilan lidah api multistep',
    description = '<p><strong>Tampilan lidah api multistep.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/Flemmo PRO.png',
    meta_title = 'Ban FDR Flemmo PRO | PT Star Parts Motor',
    meta_description = 'Flemmo PRO: Tampilan lidah api multistep. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Flemmo PRO',
    is_featured = 0,
    is_active = 1,
    sort_order = 18
WHERE slug = 'flemmo-pro';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Flemmo PRO', 'flemmo-pro', 'Tampilan lidah api multistep', '<p><strong>Tampilan lidah api multistep.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/Flemmo PRO.png', '[]', NULL, 'Ban FDR Flemmo PRO | PT Star Parts Motor', 'Flemmo PRO: Tampilan lidah api multistep. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Flemmo PRO', 0, 1, 18
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'flemmo-pro');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Facio',
    short_description = 'Ban dengan siluet batik',
    description = '<p><strong>Ban dengan siluet batik.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/FACIO.png',
    meta_title = 'Ban FDR Facio | PT Star Parts Motor',
    meta_description = 'Facio: Ban dengan siluet batik. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Facio',
    is_featured = 0,
    is_active = 1,
    sort_order = 19
WHERE slug = 'facio';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Facio', 'facio', 'Ban dengan siluet batik', '<p><strong>Ban dengan siluet batik.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/FACIO.png', '[]', NULL, 'Ban FDR Facio | PT Star Parts Motor', 'Facio: Ban dengan siluet batik. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Facio', 0, 1, 19
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'facio');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Spartax',
    short_description = 'Ban tangguh berbagai medan',
    description = '<p><strong>Ban tangguh berbagai medan.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/SPARTAX.png',
    meta_title = 'Ban FDR Spartax | PT Star Parts Motor',
    meta_description = 'Spartax: Ban tangguh berbagai medan. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Spartax',
    is_featured = 0,
    is_active = 1,
    sort_order = 20
WHERE slug = 'spartax';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Spartax', 'spartax', 'Ban tangguh berbagai medan', '<p><strong>Ban tangguh berbagai medan.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/SPARTAX.png', '[]', NULL, 'Ban FDR Spartax | PT Star Parts Motor', 'Spartax: Ban tangguh berbagai medan. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Spartax', 0, 1, 20
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'spartax');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Genzi',
    short_description = 'Karakter tajam & lincah',
    description = '<p><strong>Karakter tajam &amp; lincah.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/GENZI.png',
    meta_title = 'Ban FDR Genzi | PT Star Parts Motor',
    meta_description = 'Genzi: Karakter tajam & lincah. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Genzi',
    is_featured = 0,
    is_active = 1,
    sort_order = 21
WHERE slug = 'genzi';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Genzi', 'genzi', 'Karakter tajam & lincah', '<p><strong>Karakter tajam &amp; lincah.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/GENZI.png', '[]', NULL, 'Ban FDR Genzi | PT Star Parts Motor', 'Genzi: Karakter tajam & lincah. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Genzi', 0, 1, 21
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'genzi');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Flemmo',
    short_description = 'Harian jalan basah dan kering',
    description = '<p><strong>Harian jalan basah dan kering.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/FLEMMO.png',
    meta_title = 'Ban FDR Flemmo | PT Star Parts Motor',
    meta_description = 'Flemmo: Harian jalan basah dan kering. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Flemmo',
    is_featured = 0,
    is_active = 1,
    sort_order = 22
WHERE slug = 'flemmo';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Flemmo', 'flemmo', 'Harian jalan basah dan kering', '<p><strong>Harian jalan basah dan kering.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/FLEMMO.png', '[]', NULL, 'Ban FDR Flemmo | PT Star Parts Motor', 'Flemmo: Harian jalan basah dan kering. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Flemmo', 0, 1, 22
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'flemmo');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Sport XR Evo',
    short_description = 'Sport touring harian',
    description = '<p><strong>Sport touring harian.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/SPORT XR EVO.png',
    meta_title = 'Ban FDR Sport XR Evo | PT Star Parts Motor',
    meta_description = 'Sport XR Evo: Sport touring harian. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Sport XR Evo',
    is_featured = 1,
    is_active = 1,
    sort_order = 23
WHERE slug = 'sport-xr-evo';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Sport XR Evo', 'sport-xr-evo', 'Sport touring harian', '<p><strong>Sport touring harian.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/SPORT XR EVO.png', '[]', NULL, 'Ban FDR Sport XR Evo | PT Star Parts Motor', 'Sport XR Evo: Sport touring harian. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Sport XR Evo', 1, 1, 23
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sport-xr-evo');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Sport XT',
    short_description = 'Sport jelajah tinggi',
    description = '<p><strong>Sport jelajah tinggi.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/SPORT XT.png',
    meta_title = 'Ban FDR Sport XT | PT Star Parts Motor',
    meta_description = 'Sport XT: Sport jelajah tinggi. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Sport XT',
    is_featured = 0,
    is_active = 1,
    sort_order = 24
WHERE slug = 'sport-xt';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Sport XT', 'sport-xt', 'Sport jelajah tinggi', '<p><strong>Sport jelajah tinggi.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/SPORT XT.png', '[]', NULL, 'Ban FDR Sport XT | PT Star Parts Motor', 'Sport XT: Sport jelajah tinggi. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Sport XT', 0, 1, 24
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'sport-xt');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'City Power',
    short_description = 'Motor sport tanpa ubah pelek',
    description = '<p><strong>Motor sport tanpa ubah pelek.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/CITY POWER.png',
    meta_title = 'Ban FDR City Power | PT Star Parts Motor',
    meta_description = 'City Power: Motor sport tanpa ubah pelek. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR City Power',
    is_featured = 0,
    is_active = 1,
    sort_order = 25
WHERE slug = 'city-power';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'City Power', 'city-power', 'Motor sport tanpa ubah pelek', '<p><strong>Motor sport tanpa ubah pelek.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/CITY POWER.png', '[]', NULL, 'Ban FDR City Power | PT Star Parts Motor', 'City Power: Motor sport tanpa ubah pelek. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR City Power', 0, 1, 25
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'city-power');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Drax',
    short_description = 'Tampilan drag untuk harian',
    description = '<p><strong>Tampilan drag untuk harian.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/DRAX.png',
    meta_title = 'Ban FDR Drax | PT Star Parts Motor',
    meta_description = 'Drax: Tampilan drag untuk harian. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Drax',
    is_featured = 0,
    is_active = 1,
    sort_order = 26
WHERE slug = 'drax';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Drax', 'drax', 'Tampilan drag untuk harian', '<p><strong>Tampilan drag untuk harian.</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/DRAX.png', '[]', NULL, 'Ban FDR Drax | PT Star Parts Motor', 'Drax: Tampilan drag untuk harian. Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Drax', 0, 1, 26
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'drax');

UPDATE products SET
    category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    name = 'Cossa Pro',
    short_description = 'Segala permukaan jalan (aspal, tanah, batu)',
    description = '<p><strong>Segala permukaan jalan (aspal, tanah, batu).</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>',
    image = 'produk/COSSA PRO.png',
    meta_title = 'Ban FDR Cossa Pro | PT Star Parts Motor',
    meta_description = 'Cossa Pro: Segala permukaan jalan (aspal, tanah, batu). Informasi produk ban FDR dan katalog dari PT Star Parts Motor.',
    image_alt = 'Ban motor FDR Cossa Pro',
    is_featured = 0,
    is_active = 1,
    sort_order = 27
WHERE slug = 'cossa-pro';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1), 'Cossa Pro', 'cossa-pro', 'Segala permukaan jalan (aspal, tanah, batu)', '<p><strong>Segala permukaan jalan (aspal, tanah, batu).</strong></p><p>Kategori: FDR Tire. Untuk informasi ukuran dan kecocokan dengan kendaraan, silakan hubungi tim PT Star Parts Motor.</p>', NULL, 'produk/COSSA PRO.png', '[]', NULL, 'Ban FDR Cossa Pro | PT Star Parts Motor', 'Cossa Pro: Segala permukaan jalan (aspal, tanah, batu). Informasi produk ban FDR dan katalog dari PT Star Parts Motor.', 'Ban motor FDR Cossa Pro', 0, 1, 27
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'cossa-pro');

-- Keep the active FDR family aligned with the provided 27-product list. This hides, but does not delete, extra FDR entries.
UPDATE products p INNER JOIN product_categories c ON c.id = p.category_id SET p.is_active = 0 WHERE (LOWER(c.name) LIKE '%fdr%' OR LOWER(c.slug) LIKE '%fdr%' OR LOWER(p.name) LIKE '%fdr%') AND p.slug NOT IN ('draxstar', 'sport-mp96', 'sport-mp27', 'sport-mp57', 'sport-mp76', 'sport-xr', 'blaze', 'blaze-mp-tourer', 'blaze-mp-road', 'velogrip', 'hover', 'maxtreme', 'city-go', 'sport-zevo', 'flemino', 'city-force', 'genzi-pro', 'flemmo-pro', 'facio', 'spartax', 'genzi', 'flemmo', 'sport-xr-evo', 'sport-xt', 'city-power', 'drax', 'cossa-pro');

COMMIT;

-- Notes:
-- The supplied workbook has no dedicated photo file for Sport Zevo, Flemino, and City Force.
-- Those three entries use the general FDR tyre display image until model-specific photos are supplied.
-- Product names and short descriptions follow Rekap_Ban_FDR.xlsx, including the spelling 'Flemino'.
-- Products outside the listed FDR set that matched an FDR category/name are marked inactive, not deleted.

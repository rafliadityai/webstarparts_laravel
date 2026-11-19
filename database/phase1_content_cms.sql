-- =========================================================
-- Star Parts Motor - Phase 1 Content CMS Migration
-- Aman dijalankan setelah database starparts_db dibuat.
-- =========================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;

-- Tambahkan field CMS/SEO yang sudah disiapkan oleh form admin pages.
SET @db := DATABASE();

-- Sort order already exists as a form field in the current admin UI.
SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pages ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER status',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'pages'
      AND column_name = 'sort_order'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pages ADD COLUMN excerpt TEXT NULL AFTER content',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'pages'
      AND column_name = 'excerpt'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pages ADD COLUMN meta_title VARCHAR(255) NULL AFTER featured_image',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'pages'
      AND column_name = 'meta_title'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE pages ADD COLUMN meta_description VARCHAR(500) NULL AFTER meta_title',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'pages'
      AND column_name = 'meta_description'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- Media metadata untuk alt text dan ukuran sumber gambar.
SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE media ADD COLUMN alt_text VARCHAR(500) NULL AFTER title',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'media'
      AND column_name = 'alt_text'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE media ADD COLUMN image_width INT UNSIGNED NULL AFTER file_size',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'media'
      AND column_name = 'image_width'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE media ADD COLUMN image_height INT UNSIGNED NULL AFTER image_width',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'media'
      AND column_name = 'image_height'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Default content/SEO keys used by the new Content Center.
INSERT INTO site_settings (setting_key, setting_value, setting_type)
VALUES
('home_page_title', 'PT Star Parts Motor | Distributor Produk Otomotif', 'text'),
('home_page_description', 'PT Star Parts Motor sebagai partner distribusi produk otomotif berkualitas untuk kebutuhan bisnis dan kendaraan.', 'textarea'),
('home_og_image', '', 'image'),
('products_page_title', 'Produk | PT Star Parts Motor', 'text'),
('products_page_description', 'Jelajahi katalog produk otomotif Star Parts Motor dari berbagai brand dan kategori.', 'textarea'),
('products_og_image', '', 'image'),
('news_og_image', '', 'image'),
('contact_page_title', 'Hubungi Kami | PT Star Parts Motor', 'text'),
('contact_page_description', 'Hubungi PT Star Parts Motor untuk informasi produk, distribusi, dan peluang kerja sama.', 'textarea'),
('contact_image', '', 'image'),
('privacy_page_title', 'Privacy Policy | PT Star Parts Motor', 'text'),
('privacy_page_description', 'Kebijakan privasi dan penggunaan data pada website PT Star Parts Motor.', 'textarea'),
('terms_page_title', 'Terms & Conditions | PT Star Parts Motor', 'text'),
('terms_page_description', 'Syarat dan ketentuan penggunaan website PT Star Parts Motor.', 'textarea')
ON DUPLICATE KEY UPDATE
    setting_key = setting_key;

COMMIT;
webstarparts
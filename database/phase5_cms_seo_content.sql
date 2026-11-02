SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;

-- SEO per produk + ALT text foto utama.
SET @sql = IF(
  NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='meta_title'
  ),
  'ALTER TABLE products ADD COLUMN meta_title VARCHAR(255) NULL AFTER product_url',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='meta_description'
  ),
  'ALTER TABLE products ADD COLUMN meta_description VARCHAR(500) NULL AFTER meta_title',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='image_alt'
  ),
  'ALTER TABLE products ADD COLUMN image_alt VARCHAR(255) NULL AFTER meta_description',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ALT text banner hero.
SET @sql = IF(
  NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='banners' AND COLUMN_NAME='image_alt'
  ),
  'ALTER TABLE banners ADD COLUMN image_alt VARCHAR(255) NULL AFTER image',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- SEO per artikel + ALT text thumbnail.
SET @sql = IF(
  NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='posts' AND COLUMN_NAME='meta_title'
  ),
  'ALTER TABLE posts ADD COLUMN meta_title VARCHAR(255) NULL AFTER sort_order',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='posts' AND COLUMN_NAME='meta_description'
  ),
  'ALTER TABLE posts ADD COLUMN meta_description VARCHAR(500) NULL AFTER meta_title',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='posts' AND COLUMN_NAME='featured_image_alt'
  ),
  'ALTER TABLE posts ADD COLUMN featured_image_alt VARCHAR(255) NULL AFTER meta_description',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Default internal focus terms; tetap opsional dan bukan meta keywords untuk ranking.
INSERT INTO site_settings(setting_key,setting_value,setting_type)
VALUES
('site_author','PT Star Parts Motor','text'),
('content_editor_note','Gunakan konten yang jelas, orisinal, dan membantu calon pelanggan.','text')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

COMMIT;

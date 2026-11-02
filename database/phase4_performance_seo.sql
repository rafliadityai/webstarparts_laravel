SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;

INSERT INTO site_settings(setting_key,setting_value,setting_type) VALUES
('image_webp_quality','82','number'),
('image_variant_widths','480,768,1200,1600,1920','text')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

COMMIT;

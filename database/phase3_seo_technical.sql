SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;

INSERT INTO site_settings(setting_key,setting_value,setting_type) VALUES
('site_url','','url'),
('google_site_verification','','text')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

COMMIT;

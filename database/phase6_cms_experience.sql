SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;

INSERT INTO site_settings (setting_key, setting_value, setting_type)
VALUES
('cta_title_prefix','Siap Menjadi','text'),
('cta_title_highlight','Mitra Star Parts Motor?','text'),
('cta_text','Mari bangun kerja sama jangka panjang dan tumbuh bersama dalam industri otomotif.','textarea'),
('cta_whatsapp_text','Hubungi via WhatsApp','text'),
('cta_button_text','Hubungi Kami','text'),
('cta_button_url','contact.php','url'),
('site_author','PT Star Parts Motor','text'),
('footer_text','© PT Star Parts Motor','text')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);

COMMIT;

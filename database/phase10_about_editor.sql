-- Star Parts Motor Phase 10: complete About page editable content.
-- No ALTER TABLE: this only inserts missing/blank site settings.
-- Run after the existing CMS database and Phases 1-9 are installed.

START TRANSACTION;

INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES
('about_page_title', 'Tentang Kami | PT Star Parts Motor', 'text'),
('about_page_description', 'Kenali PT Star Parts Motor sebagai partner distribusi produk otomotif untuk pelanggan, retailer, dealer, dan mitra bisnis.', 'textarea'),
('about_page_heading', 'Membangun Distribusi Otomotif yang Lebih Baik', 'text'),
('about_page_intro', 'Kenali PT Star Parts Motor dan komitmen kami dalam mendukung kebutuhan distribusi serta kerja sama di industri otomotif.', 'textarea')
ON DUPLICATE KEY UPDATE
    setting_type = VALUES(setting_type),
    setting_value = IF(TRIM(COALESCE(site_settings.setting_value, '')) = '', VALUES(setting_value), site_settings.setting_value);

COMMIT;

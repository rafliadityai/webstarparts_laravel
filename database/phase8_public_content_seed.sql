-- Star Parts Motor Phase 8: baseline public website content.
-- Safe to re-run: non-empty site_settings values are preserved.
-- Run only after selecting the existing starparts_db database.

START TRANSACTION;

INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES
('site_name', 'PT Star Parts Motor', 'text'),
('site_tagline', 'Operational Excellence', 'text'),
('site_description', 'PT Star Parts Motor menghadirkan informasi produk otomotif dan layanan distribusi untuk kebutuhan retail, dealer, serta mitra bisnis.', 'textarea'),
('site_logo', 'uploads/general/star-parts-motor-logo.png', 'image'),
('site_favicon', 'uploads/general/star-parts-motor-favicon.png', 'image'),
('meta_title', 'PT Star Parts Motor | Solusi Distribusi Produk Otomotif', 'text'),
('meta_description', 'Jelajahi informasi produk otomotif, materi katalog FDR 2026, dan kanal komunikasi PT Star Parts Motor.', 'textarea'),
('home_page_title', 'PT Star Parts Motor | Solusi Distribusi Produk Otomotif', 'text'),
('home_page_description', 'Jelajahi informasi produk otomotif, materi katalog FDR 2026, dan peluang komunikasi serta kerja sama dengan PT Star Parts Motor.', 'textarea'),
('home_og_image', 'uploads/general/gedung-star-parts-motor.webp', 'image'),
('hero_title', 'Solusi Distribusi Produk Otomotif yang Andal', 'text'),
('hero_subtitle', 'Jelajahi pilihan produk ban dan kebutuhan otomotif dari brand pilihan. Tim kami siap membantu informasi produk dan peluang kerja sama.', 'textarea'),
('hero_button_text', 'Jelajahi Produk', 'text'),
('hero_button_url', 'products.php', 'text'),
('about_title', 'Mitra Distribusi Otomotif untuk Kebutuhan Bisnis Anda', 'text'),
('about_content', '<p>PT Star Parts Motor berfokus pada penyediaan informasi produk otomotif dan dukungan distribusi bagi pelanggan, retailer, dealer, serta mitra bisnis.</p><p>Melalui katalog produk dan kanal komunikasi yang tersedia, kami membantu calon pelanggan mendapatkan informasi awal sebelum melakukan pemesanan atau menjajaki kerja sama.</p>', 'textarea'),
('about_image', 'uploads/general/gedung-star-parts-motor.webp', 'image'),
('about_history_title', 'Membangun Distribusi yang Berkelanjutan', 'text'),
('about_history_content', '<p>Kami terus membangun informasi produk yang mudah dijelajahi dan komunikasi yang jelas untuk mendukung kebutuhan otomotif pelanggan serta mitra bisnis.</p>', 'textarea'),
('about_commitment_title', 'Komitmen Kami', 'text'),
('about_commitment_content', '<p>Kami berupaya menyampaikan informasi produk secara jelas, membantu pertanyaan pelanggan, dan membuka komunikasi untuk kebutuhan distribusi serta kerja sama.</p>', 'textarea'),
('products_page_title', 'Katalog Produk | PT Star Parts Motor', 'text'),
('products_page_description', 'Jelajahi katalog produk otomotif dan materi katalog FDR 2026 dari PT Star Parts Motor.', 'textarea'),
('products_og_image', 'uploads/general/katalog-fdr-2026-preview.webp', 'image')
ON DUPLICATE KEY UPDATE
    setting_type = IF(TRIM(COALESCE(setting_value, '')) = '', VALUES(setting_type), setting_type),
    setting_value = IF(TRIM(COALESCE(setting_value, '')) = '', VALUES(setting_value), setting_value);

COMMIT;

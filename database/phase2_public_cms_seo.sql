SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;

INSERT INTO site_settings(setting_key,setting_value,setting_type) VALUES
('site_author','PT Star Parts Motor','text'),
('meta_title','PT Star Parts Motor | Distributor Produk Otomotif','text'),
('meta_description','PT Star Parts Motor sebagai partner distribusi produk otomotif berkualitas untuk kebutuhan bisnis dan kendaraan.','textarea'),
('meta_keywords','ban motor, sparepart motor, distributor otomotif, Star Parts Motor','text'),
('home_page_title','PT Star Parts Motor | Distributor Produk Otomotif','text'),
('home_page_description','PT Star Parts Motor sebagai partner distribusi produk otomotif berkualitas untuk kebutuhan bisnis dan kendaraan.','textarea'),
('products_page_title','Produk | PT Star Parts Motor','text'),
('products_page_description','Katalog produk Star Parts Motor untuk kebutuhan otomotif dan distribusi.','textarea'),
('news_page_title','Berita & Aktivitas | PT Star Parts Motor','text'),
('news_page_description','Berita, informasi produk, aktivitas dan perkembangan terbaru PT Star Parts Motor.','textarea'),
('about_page_title','Tentang Kami | PT Star Parts Motor','text'),
('about_page_description','Mengenal PT Star Parts Motor sebagai partner distribusi produk otomotif.','textarea'),
('contact_page_title','PT Star Parts Motor | Hubungi Kami','text'),
('contact_page_description','Hubungi PT Star Parts Motor untuk informasi produk, distribusi dan peluang kerja sama.','textarea'),
('career_page_title','Karir | PT Star Parts Motor','text'),
('career_page_description','Peluang karir dan informasi bergabung bersama PT Star Parts Motor.','textarea'),
('privacy_page_title','Privacy Policy | PT Star Parts Motor','text'),
('privacy_page_description','Kebijakan privasi dan penggunaan data pada website PT Star Parts Motor.','textarea'),
('terms_page_title','Terms & Conditions | PT Star Parts Motor','text'),
('terms_page_description','Syarat dan ketentuan penggunaan website PT Star Parts Motor.','textarea'),
('home_og_image','','image'),
('products_og_image','','image'),
('news_og_image','','image'),
('contact_image','','image'),
('about_history_title','Membangun Distribusi yang Berkelanjutan','text'),
('about_history_content','','html'),
('about_commitment_title','Komitmen Kami','text'),
('about_commitment_content','','html')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

COMMIT;

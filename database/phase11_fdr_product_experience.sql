-- Star Parts Motor Phase 11: FDR tire listing and product detail experience.
-- Run this after the existing database setup and Phase 9 (and Phase 10 if used).
-- This migration preserves existing text and product URLs; it only adds per-product presentation fields.

CREATE TABLE IF NOT EXISTS fdr_product_details (
    product_id INT NOT NULL,
    tire_type ENUM('inner','tubetype','tubeless','other') NOT NULL DEFAULT 'other',
    listing_description TEXT NULL,
    hero_description TEXT NULL,
    benefits_text MEDIUMTEXT NULL,
    sizes_text MEDIUMTEXT NULL,
    performance_text MEDIUMTEXT NULL,
    technologies_text MEDIUMTEXT NULL,
    logo_path VARCHAR(255) NULL DEFAULT 'uploads/general/fdr-tire-logo-red.png',
    virtual_tire_url VARCHAR(500) NULL DEFAULT 'https://bengkelku.online',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id),
    KEY idx_fdr_product_details_tire_type (tire_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add the catalog row for the supplied FDR TUBE artwork so the Inner Tire submenu has a relevant item.
-- The copy is intentionally generic and can be changed in Admin > Konten Detail Ban.
UPDATE products p
SET p.category_id = (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
    p.name = 'FDR Tube',
    p.short_description = 'Ban dalam untuk penggunaan ban tipe tube.',
    p.description = '<p>Ban dalam untuk penggunaan ban tipe tube. Perbarui ukuran dan keterangan produk melalui panel admin sesuai informasi resmi yang tersedia.</p>',
    p.image = 'produk/FDR TUBE.png',
    p.image_alt = 'FDR Tube - ban dalam',
    p.is_active = 1,
    p.sort_order = 28
WHERE p.slug = 'fdr-tube';

INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'fdr-tire' LIMIT 1),
       'FDR Tube', 'fdr-tube', 'Ban dalam untuk penggunaan ban tipe tube.',
       '<p>Ban dalam untuk penggunaan ban tipe tube. Perbarui ukuran dan keterangan produk melalui panel admin sesuai informasi resmi yang tersedia.</p>',
       NULL, 'produk/FDR TUBE.png', '[]', NULL,
       'FDR Tube | PT Star Parts Motor',
       'Informasi FDR Tube dari PT Star Parts Motor. Konfirmasi spesifikasi dan ukuran melalui admin.',
       'FDR Tube - ban dalam', 0, 1, 28
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'fdr-tube');

-- Create default presentation rows for every FDR category product. Existing product descriptions remain the fallback.
INSERT INTO fdr_product_details (product_id, tire_type, logo_path, virtual_tire_url)
SELECT p.id,
       CASE
         WHEN LOWER(p.name) LIKE '%tube%' THEN 'inner'
         WHEN LOWER(CONCAT(COALESCE(p.specifications,''),' ',COALESCE(p.description,''))) REGEXP '(^|[^a-z])tt(r)?([^a-z]|$)|ttrp|tube[- ]?type' THEN 'tubetype'
         WHEN LOWER(CONCAT(COALESCE(p.specifications,''),' ',COALESCE(p.description,''))) REGEXP '(^|[^a-z])tl([^a-z]|$)|tubeless' THEN 'tubeless'
         ELSE 'other'
       END,
       'uploads/general/fdr-tire-logo-red.png',
       'https://bengkelku.online'
FROM products p
JOIN product_categories c ON c.id = p.category_id
WHERE c.slug IN ('fdr-tire','fdr-ultimate','fdr-champion')
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

-- Ensure the new tube item is classified as Inner Tire without touching any admin-entered copy.
UPDATE fdr_product_details d
JOIN products p ON p.id = d.product_id
SET d.tire_type = 'inner',
    d.logo_path = COALESCE(NULLIF(d.logo_path,''), 'uploads/general/fdr-tire-logo-red.png'),
    d.virtual_tire_url = COALESCE(NULLIF(d.virtual_tire_url,''), 'https://bengkelku.online')
WHERE p.slug = 'fdr-tube';


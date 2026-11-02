-- Star Parts Motor Phase 13: OSRAM lighting catalog
-- Idempotent seed: creates OSRAM category and product rows without overwriting admin-edited descriptions.
-- Product images are referenced from the user's public beta-career.starpartsmotor.com image URLs.
-- Run once in phpMyAdmin after the existing Starparts database and Phase 1-12 migrations are present.
START TRANSACTION;

INSERT INTO product_categories (name, slug, description, image, sort_order, is_active)
SELECT 'OSRAM', 'osram', 'Katalog produk lampu OSRAM. Deskripsi dan spesifikasi tiap produk dapat dilengkapi melalui admin.', 'https://beta-career.starpartsmotor.com/OHS70.png', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE slug = 'osram');
UPDATE product_categories SET name = 'OSRAM', sort_order = 4, is_active = 1 WHERE slug = 'osram';

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/10090W%2024V%20P43T%2010X10X1.png', image_alt = 'Lampu OSRAM 10090W 24V P43T 10X10X1', is_active = 1, sort_order = 1 WHERE slug = 'osram-10090w-24v-p43t-10x10x1';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '10090W 24V P43T 10X10X1', 'osram-10090w-24v-p43t-10x10x1', '', '', NULL, 'https://beta-career.starpartsmotor.com/10090W%2024V%20P43T%2010X10X1.png', '[]', NULL, '10090W 24V P43T 10X10X1 | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 10090W 24V P43T 10X10X1', 0, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-10090w-24v-p43t-10x10x1');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/100W%2024V%20PK22S%20FS1%20OS.png', image_alt = 'Lampu OSRAM 100W 24V PK22S FS1 OS', is_active = 1, sort_order = 2 WHERE slug = 'osram-100w-24v-pk22s-fs1-os';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '100W 24V PK22S FS1 OS', 'osram-100w-24v-pk22s-fs1-os', '', '', NULL, 'https://beta-career.starpartsmotor.com/100W%2024V%20PK22S%20FS1%20OS.png', '[]', NULL, '100W 24V PK22S FS1 OS | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 100W 24V PK22S FS1 OS', 0, 1, 2
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-100w-24v-pk22s-fs1-os');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/3232W%2012V%20P15D-25-1%2010x10x12F%20OSRAM.png', image_alt = 'Lampu OSRAM 3232W 12V P15D-25-1 10x10x12F OSRAM', is_active = 1, sort_order = 3 WHERE slug = 'osram-3232w-12v-p15d-25-1-10x10x12f-osram';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '3232W 12V P15D-25-1 10x10x12F OSRAM', 'osram-3232w-12v-p15d-25-1-10x10x12f-osram', '', '', NULL, 'https://beta-career.starpartsmotor.com/3232W%2012V%20P15D-25-1%2010x10x12F%20OSRAM.png', '[]', NULL, '3232W 12V P15D-25-1 10x10x12F OSRAM | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 3232W 12V P15D-25-1 10x10x12F OSRAM', 0, 1, 3
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-3232w-12v-p15d-25-1-10x10x12f-osram');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/3535W%20PX43T%20100X1%20EN%20OSRAM.png', image_alt = 'Lampu OSRAM 3535W PX43T 100X1 EN OSRAM', is_active = 1, sort_order = 4 WHERE slug = 'osram-3535w-px43t-100x1-en-osram';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '3535W PX43T 100X1 EN OSRAM', 'osram-3535w-px43t-100x1-en-osram', '', '', NULL, 'https://beta-career.starpartsmotor.com/3535W%20PX43T%20100X1%20EN%20OSRAM.png', '[]', NULL, '3535W PX43T 100X1 EN OSRAM | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 3535W PX43T 100X1 EN OSRAM', 0, 1, 4
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-3535w-px43t-100x1-en-osram');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/64151CLC%2055W%2012V%20PK22S%2010X10X1%202F%20OSRAM.png', image_alt = 'Lampu OSRAM 64151CLC 55W 12V PK22S 10X10X1 2F OSRAM', is_active = 1, sort_order = 5 WHERE slug = 'osram-64151clc-55w-12v-pk22s-10x10x1-2f-osram';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '64151CLC 55W 12V PK22S 10X10X1 2F OSRAM', 'osram-64151clc-55w-12v-pk22s-10x10x1-2f-osram', '', '', NULL, 'https://beta-career.starpartsmotor.com/64151CLC%2055W%2012V%20PK22S%2010X10X1%202F%20OSRAM.png', '[]', NULL, '64151CLC 55W 12V PK22S 10X10X1 2F OSRAM | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 64151CLC 55W 12V PK22S 10X10X1 2F OSRAM', 0, 1, 5
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-64151clc-55w-12v-pk22s-10x10x1-2f-osram');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/OHS70.png', image_alt = 'Lampu OSRAM OHS70', is_active = 1, sort_order = 6 WHERE slug = 'osram-ohs70';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'OHS70', 'osram-ohs70', '', '', NULL, 'https://beta-career.starpartsmotor.com/OHS70.png', '[]', NULL, 'OHS70 | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM OHS70', 0, 1, 6
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-ohs70');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/OSRAM%20HALOGEN%20STANDAR%20BA%203535W.png', image_alt = 'Lampu OSRAM OSRAM HALOGEN STANDAR BA 3535W', is_active = 1, sort_order = 7 WHERE slug = 'osram-halogen-standar-ba-3535w';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'OSRAM HALOGEN STANDAR BA 3535W', 'osram-halogen-standar-ba-3535w', '', '', NULL, 'https://beta-career.starpartsmotor.com/OSRAM%20HALOGEN%20STANDAR%20BA%203535W.png', '[]', NULL, 'OSRAM HALOGEN STANDAR BA 3535W | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM OSRAM HALOGEN STANDAR BA 3535W', 0, 1, 7
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-halogen-standar-ba-3535w');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/64155CLC%2070W%2024V%20P14%2C5S%2010X10X1%202F.png', image_alt = 'Lampu OSRAM 64155CLC 70W 24V P14,5S 10X10X1 2F', is_active = 1, sort_order = 8 WHERE slug = 'osram-64155clc-70w-24v-p14-5s-10x10x1-2f';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '64155CLC 70W 24V P14,5S 10X10X1 2F', 'osram-64155clc-70w-24v-p14-5s-10x10x1-2f', '', '', NULL, 'https://beta-career.starpartsmotor.com/64155CLC%2070W%2024V%20P14%2C5S%2010X10X1%202F.png', '[]', NULL, '64155CLC 70W 24V P14,5S 10X10X1 2F | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 64155CLC 70W 24V P14,5S 10X10X1 2F', 0, 1, 8
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-64155clc-70w-24v-p14-5s-10x10x1-2f');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/64156CLC%2070W%2024V%20PK22S%2010X10X1%202F%20OSRAM.png', image_alt = 'Lampu OSRAM 64156CLC 70W 24V PK22S 10X10X1 2F OSRAM', is_active = 1, sort_order = 9 WHERE slug = 'osram-64156clc-70w-24v-pk22s-10x10x1-2f-osram';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '64156CLC 70W 24V PK22S 10X10X1 2F OSRAM', 'osram-64156clc-70w-24v-pk22s-10x10x1-2f-osram', '', '', NULL, 'https://beta-career.starpartsmotor.com/64156CLC%2070W%2024V%20PK22S%2010X10X1%202F%20OSRAM.png', '[]', NULL, '64156CLC 70W 24V PK22S 10X10X1 2F OSRAM | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 64156CLC 70W 24V PK22S 10X10X1 2F OSRAM', 0, 1, 9
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-64156clc-70w-24v-pk22s-10x10x1-2f-osram');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/64193CLC%206055W%2012V%20P43T%2010X10X1%202F.png', image_alt = 'Lampu OSRAM 64193CLC 6055W 12V P43T 10X10X1 2F', is_active = 1, sort_order = 10 WHERE slug = 'osram-64193clc-6055w-12v-p43t-10x10x1-2f';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '64193CLC 6055W 12V P43T 10X10X1 2F', 'osram-64193clc-6055w-12v-p43t-10x10x1-2f', '', '', NULL, 'https://beta-career.starpartsmotor.com/64193CLC%206055W%2012V%20P43T%2010X10X1%202F.png', '[]', NULL, '64193CLC 6055W 12V P43T 10X10X1 2F | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 64193CLC 6055W 12V P43T 10X10X1 2F', 0, 1, 10
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-64193clc-6055w-12v-p43t-10x10x1-2f');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/64211CLC%2055W%2012V%20PGJ19-2%2010X10X1%202FOSRAM.png', image_alt = 'Lampu OSRAM 64211CLC 55W 12V PGJ19-2 10X10X1 2FOSRAM', is_active = 1, sort_order = 11 WHERE slug = 'osram-64211clc-55w-12v-pgj19-2-10x10x1-2fosram';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '64211CLC 55W 12V PGJ19-2 10X10X1 2FOSRAM', 'osram-64211clc-55w-12v-pgj19-2-10x10x1-2fosram', '', '', NULL, 'https://beta-career.starpartsmotor.com/64211CLC%2055W%2012V%20PGJ19-2%2010X10X1%202FOSRAM.png', '[]', NULL, '64211CLC 55W 12V PGJ19-2 10X10X1 2FOSRAM | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 64211CLC 55W 12V PGJ19-2 10X10X1 2FOSRAM', 0, 1, 11
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-64211clc-55w-12v-pgj19-2-10x10x1-2fosram');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/7935YE%2056W%2013%2C5VP15D-25-120XBL1%204F.png', image_alt = 'Lampu OSRAM 7935YE 56W 13,5VP15D-25-120XBL1 4F', is_active = 1, sort_order = 12 WHERE slug = 'osram-7935ye-56w-13-5vp15d-25-120xbl1-4f';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '7935YE 56W 13,5VP15D-25-120XBL1 4F', 'osram-7935ye-56w-13-5vp15d-25-120xbl1-4f', '', '', NULL, 'https://beta-career.starpartsmotor.com/7935YE%2056W%2013%2C5VP15D-25-120XBL1%204F.png', '[]', NULL, '7935YE 56W 13,5VP15D-25-120XBL1 4F | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 7935YE 56W 13,5VP15D-25-120XBL1 4F', 0, 1, 12
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-7935ye-56w-13-5vp15d-25-120xbl1-4f');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/8035BC%2056W%2013.5VP15D-25-120XBLI14FOSRAM.png', image_alt = 'Lampu OSRAM 8035BC 56W 13.5VP15D-25-120XBLI14FOSRAM', is_active = 1, sort_order = 13 WHERE slug = 'osram-8035bc-56w-13-5vp15d-25-120xbli14fosram';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '8035BC 56W 13.5VP15D-25-120XBLI14FOSRAM', 'osram-8035bc-56w-13-5vp15d-25-120xbli14fosram', '', '', NULL, 'https://beta-career.starpartsmotor.com/8035BC%2056W%2013.5VP15D-25-120XBLI14FOSRAM.png', '[]', NULL, '8035BC 56W 13.5VP15D-25-120XBLI14FOSRAM | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 8035BC 56W 13.5VP15D-25-120XBLI14FOSRAM', 0, 1, 13
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-8035bc-56w-13-5vp15d-25-120xbli14fosram');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/881%2027W%2012V%20PGJ13.png', image_alt = 'Lampu OSRAM 881 27W 12V PGJ13', is_active = 1, sort_order = 14 WHERE slug = 'osram-881-27w-12v-pgj13';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '881 27W 12V PGJ13', 'osram-881-27w-12v-pgj13', '', '', NULL, 'https://beta-career.starpartsmotor.com/881%2027W%2012V%20PGJ13.png', '[]', NULL, '881 27W 12V PGJ13 | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 881 27W 12V PGJ13', 0, 1, 14
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-881-27w-12v-pgj13');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/9006CLC%2051W%2012V%20P22D%2010X%2010X%201%202F.png', image_alt = 'Lampu OSRAM 9006CLC 51W 12V P22D 10X 10X 1 2F', is_active = 1, sort_order = 15 WHERE slug = 'osram-9006clc-51w-12v-p22d-10x-10x-1-2f';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '9006CLC 51W 12V P22D 10X 10X 1 2F', 'osram-9006clc-51w-12v-p22d-10x-10x-1-2f', '', '', NULL, 'https://beta-career.starpartsmotor.com/9006CLC%2051W%2012V%20P22D%2010X%2010X%201%202F.png', '[]', NULL, '9006CLC 51W 12V P22D 10X 10X 1 2F | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 9006CLC 51W 12V P22D 10X 10X 1 2F', 0, 1, 15
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-9006clc-51w-12v-p22d-10x-10x-1-2f');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/9012CLC%2055W%2012V%20PX22D%2010X10X1%202F%20OSRAM.png', image_alt = 'Lampu OSRAM 9012CLC 55W 12V PX22D 10X10X1 2F OSRAM', is_active = 1, sort_order = 16 WHERE slug = 'osram-9012clc-55w-12v-px22d-10x10x1-2f-osram';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), '9012CLC 55W 12V PX22D 10X10X1 2F OSRAM', 'osram-9012clc-55w-12v-px22d-10x10x1-2f-osram', '', '', NULL, 'https://beta-career.starpartsmotor.com/9012CLC%2055W%2012V%20PX22D%2010X10X1%202F%20OSRAM.png', '[]', NULL, '9012CLC 55W 12V PX22D 10X10X1 2F OSRAM | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM 9012CLC 55W 12V PX22D 10X10X1 2F OSRAM', 0, 1, 16
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-9012clc-55w-12v-px22d-10x10x1-2f-osram');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/BA%202525W.png', image_alt = 'Lampu OSRAM BA 2525W', is_active = 1, sort_order = 17 WHERE slug = 'osram-ba-2525w';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'BA 2525W', 'osram-ba-2525w', '', '', NULL, 'https://beta-career.starpartsmotor.com/BA%202525W.png', '[]', NULL, 'BA 2525W | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM BA 2525W', 0, 1, 17
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-ba-2525w');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/BA%203535W.png', image_alt = 'Lampu OSRAM BA 3535W', is_active = 1, sort_order = 18 WHERE slug = 'osram-ba-3535w';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'BA 3535W', 'osram-ba-3535w', '', '', NULL, 'https://beta-career.starpartsmotor.com/BA%203535W.png', '[]', NULL, 'BA 3535W | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM BA 3535W', 0, 1, 18
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-ba-3535w');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/H4%202525W%20P15D-25-1%20(CB).png', image_alt = 'Lampu OSRAM H4 2525W P15D-25-1 (CB)', is_active = 1, sort_order = 19 WHERE slug = 'osram-h4-2525w-p15d-25-1-cb';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'H4 2525W P15D-25-1 (CB)', 'osram-h4-2525w-p15d-25-1-cb', '', '', NULL, 'https://beta-career.starpartsmotor.com/H4%202525W%20P15D-25-1%20(CB).png', '[]', NULL, 'H4 2525W P15D-25-1 (CB) | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM H4 2525W P15D-25-1 (CB)', 0, 1, 19
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-h4-2525w-p15d-25-1-cb');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/H4%203535W%20ALS.png', image_alt = 'Lampu OSRAM H4 3535W ALS', is_active = 1, sort_order = 20 WHERE slug = 'osram-h4-3535w-als';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'H4 3535W ALS', 'osram-h4-3535w-als', '', '', NULL, 'https://beta-career.starpartsmotor.com/H4%203535W%20ALS.png', '[]', NULL, 'H4 3535W ALS | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM H4 3535W ALS', 0, 1, 20
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-h4-3535w-als');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/H4%203535W%20CB.png', image_alt = 'Lampu OSRAM H4 3535W CB', is_active = 1, sort_order = 21 WHERE slug = 'osram-h4-3535w-cb';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'H4 3535W CB', 'osram-h4-3535w-cb', '', '', NULL, 'https://beta-career.starpartsmotor.com/H4%203535W%20CB.png', '[]', NULL, 'H4 3535W CB | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM H4 3535W CB', 0, 1, 21
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-h4-3535w-cb');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/H4%203535W.png', image_alt = 'Lampu OSRAM H4 3535W', is_active = 1, sort_order = 22 WHERE slug = 'osram-h4-3535w';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'H4 3535W', 'osram-h4-3535w', '', '', NULL, 'https://beta-career.starpartsmotor.com/H4%203535W.png', '[]', NULL, 'H4 3535W | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM H4 3535W', 0, 1, 22
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-h4-3535w');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/N6835BC%2044W13.5VP15D-25-120XBLI14FNEOLX.png', image_alt = 'Lampu OSRAM N6835BC 44W13.5VP15D-25-120XBLI14FNEOLX', is_active = 1, sort_order = 23 WHERE slug = 'osram-n6835bc-44w13-5vp15d-25-120xbli14fneolx';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'N6835BC 44W13.5VP15D-25-120XBLI14FNEOLX', 'osram-n6835bc-44w13-5vp15d-25-120xbli14fneolx', '', '', NULL, 'https://beta-career.starpartsmotor.com/N6835BC%2044W13.5VP15D-25-120XBLI14FNEOLX.png', '[]', NULL, 'N6835BC 44W13.5VP15D-25-120XBLI14FNEOLX | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM N6835BC 44W13.5VP15D-25-120XBLI14FNEOLX', 0, 1, 23
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-n6835bc-44w13-5vp15d-25-120xbli14fneolx');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/P215W.png', image_alt = 'Lampu OSRAM P215W', is_active = 1, sort_order = 24 WHERE slug = 'osram-p215w';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'P215W', 'osram-p215w', '', '', NULL, 'https://beta-career.starpartsmotor.com/P215W.png', '[]', NULL, 'P215W | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM P215W', 0, 1, 24
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-p215w');

UPDATE products SET category_id = (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), image = 'https://beta-career.starpartsmotor.com/OSRAM%20HALOGEN%20STANDAR%20BA%20353W.png', image_alt = 'Lampu OSRAM OSRAM HALOGEN STANDAR BA 353W', is_active = 1, sort_order = 25 WHERE slug = 'osram-halogen-standar-ba-353w';
INSERT INTO products (category_id, name, slug, short_description, description, specifications, image, gallery, product_url, meta_title, meta_description, image_alt, is_featured, is_active, sort_order)
SELECT (SELECT id FROM product_categories WHERE slug = 'osram' LIMIT 1), 'OSRAM HALOGEN STANDAR BA 353W', 'osram-halogen-standar-ba-353w', '', '', NULL, 'https://beta-career.starpartsmotor.com/OSRAM%20HALOGEN%20STANDAR%20BA%20353W.png', '[]', NULL, 'OSRAM HALOGEN STANDAR BA 353W | OSRAM | PT Star Parts Motor', '', 'Lampu OSRAM OSRAM HALOGEN STANDAR BA 353W', 0, 1, 25
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = 'osram-halogen-standar-ba-353w');

COMMIT;

-- Note: image fields use full HTTPS URLs. The frontend supports absolute image URLs via media_url().
-- Product names, short description, description, specifications, SEO metadata, and Buy Now URL can be edited from Admin > Produk.
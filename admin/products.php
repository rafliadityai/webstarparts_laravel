<?php
// =========================================================
// admin/products.php
// Management Produk PT Star Parts Motor
// =========================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| PROTECT ADMIN
|--------------------------------------------------------------------------
*/

require_admin();


/*
|--------------------------------------------------------------------------
| BASIC DATA
|--------------------------------------------------------------------------
*/

$admin = current_admin();

$adminName =
    $admin['full_name']
    ??
    $_SESSION['admin_name']
    ??
    'Administrator';

$siteName = trim(
    (string) get_setting(
        'site_name',
        'PT Star Parts Motor'
    )
);

$siteLogo = trim(
    (string) get_setting(
        'site_logo',
        ''
    )
);

$siteLogoUrl =
    $siteLogo !== ''
        ? media_url($siteLogo)
        : '';

$listUrl = base_url(
    'admin/products.php'
);

$uploadFolder = 'products';


/*
|--------------------------------------------------------------------------
| PAGE STATE
|--------------------------------------------------------------------------
*/

$action = strtolower(
    get_string(
        'action',
        'list'
    )
);

$editId = get_int(
    'id',
    0
);

$error = '';

$flash = null;


/*
|--------------------------------------------------------------------------
| HELPER: PRODUCT BY ID
|--------------------------------------------------------------------------
*/

function admin_get_product(
    PDO $pdo,
    int $id
): ?array {

    if ($id <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT
            p.*,
            c.name AS category_name,
            c.slug AS category_slug
         FROM products p
         LEFT JOIN product_categories c
            ON c.id = p.category_id
         WHERE p.id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $id
    ]);

    $product = $stmt->fetch();

    return $product ?: null;
}


/*
|--------------------------------------------------------------------------
| HELPER: DELETE LOCAL FILE
|--------------------------------------------------------------------------
*/

function admin_delete_local_file(
    ?string $relativePath
): void {

    if (
        empty($relativePath)
    ) {
        return;
    }

    $relativePath = ltrim(
        trim($relativePath),
        '/\\'
    );

    if (
        !str_starts_with(
            $relativePath,
            'uploads/'
        )
    ) {
        return;
    }

    $projectRoot = dirname(__DIR__);

    $fullPath =
        $projectRoot
        . DIRECTORY_SEPARATOR
        . str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $relativePath
        );

    $uploadsRoot = realpath(
        $projectRoot
        . DIRECTORY_SEPARATOR
        . 'uploads'
    );

    $fileRoot =
        is_file($fullPath)
            ? realpath($fullPath)
            : false;

    if (
        $uploadsRoot === false
        ||
        $fileRoot === false
    ) {
        return;
    }

    if (
        $fileRoot !== $uploadsRoot
        &&
        !str_starts_with(
            $fileRoot,
            $uploadsRoot
            . DIRECTORY_SEPARATOR
        )
    ) {
        return;
    }

    delete_image_variants($fileRoot);
    @unlink($fileRoot);
}


/*
|--------------------------------------------------------------------------
| HELPER: PARSE GALLERY
|--------------------------------------------------------------------------
*/

function admin_parse_gallery(
    mixed $gallery
): array {

    if (
        is_array($gallery)
    ) {
        return array_values(
            array_filter(
                $gallery,
                static function ($value) {
                    return is_string($value)
                        && trim($value) !== '';
                }
            )
        );
    }

    if (
        !is_string($gallery)
        ||
        trim($gallery) === ''
    ) {
        return [];
    }

    $decoded = json_decode(
        $gallery,
        true
    );

    if (
        is_array($decoded)
    ) {
        return array_values(
            array_filter(
                $decoded,
                static function ($value) {
                    return is_string($value)
                        && trim($value) !== '';
                }
            )
        );
    }

    /*
     * Fallback untuk format lama comma-separated.
     */

    $parts = array_map(
        'trim',
        explode(
            ',',
            $gallery
        )
    );

    return array_values(
        array_filter(
            $parts,
            static function ($value) {
                return $value !== '';
            }
        )
    );
}


/*
|--------------------------------------------------------------------------
| HELPER: NORMALIZE GALLERY
|--------------------------------------------------------------------------
*/

function admin_normalize_gallery(
    array $gallery
): array {

    $result = [];

    foreach (
        $gallery as $item
    ) {

        if (
            !is_string($item)
        ) {
            continue;
        }

        $item = trim($item);

        if (
            $item === ''
        ) {
            continue;
        }

        $result[] = $item;
    }

    /*
     * Hapus duplikat, pertahankan urutan.
     */

    return array_values(
        array_unique($result)
    );
}


/*
|--------------------------------------------------------------------------
| HELPER: IMAGE MIME CHECK
|--------------------------------------------------------------------------
*/

function admin_is_valid_image_upload(
    array $file
): bool {

    if (
        !isset(
            $file['error'],
            $file['tmp_name']
        )
    ) {
        return false;
    }

    if (
        $file['error'] !== UPLOAD_ERR_OK
    ) {
        return false;
    }

    return is_uploaded_file(
        $file['tmp_name']
    );
}


/*
|--------------------------------------------------------------------------
| POST PROCESS
|--------------------------------------------------------------------------
*/

if (
    is_post()
) {

    $formAction = strtolower(
        post_string(
            'form_action'
        )
    );


    /*
     * CSRF
     */

    if (
        !verify_csrf()
    ) {

        $error =
            'Permintaan tidak valid. Silakan refresh halaman dan coba kembali.';

    } else {


        /*
         * =====================================================
         * SAVE PRODUCT
         * =====================================================
         */

        if (
            $formAction === 'save'
        ) {

            $id = (int) (
                $_POST['id']
                ??
                0
            );


            $categoryId = (int) (
                $_POST['category_id']
                ??
                0
            );


            $name = post_string(
                'name'
            );


            $slug = post_string(
                'slug'
            );


            $shortDescription =
                post_string(
                    'short_description'
                );


            $description =
                isset(
                    $_POST['description']
                )
                    ? trim(
                        (string)
                        $_POST['description']
                    )
                    : '';


            $specifications =
                isset(
                    $_POST['specifications']
                )
                    ? trim(
                        (string)
                        $_POST['specifications']
                    )
                    : '';


            $productUrl =
                post_string(
                    'product_url'
                );

            $metaTitle = trim(post_string('meta_title'));
            $metaDescription = trim(post_string('meta_description'));
            $imageAlt = trim(post_string('image_alt'));

            $sortOrder = (int) (
                $_POST['sort_order']
                ??
                0
            );


            $isFeatured =
                isset(
                    $_POST['is_featured']
                )
                    ? 1
                    : 0;


            $isActive =
                isset(
                    $_POST['is_active']
                )
                    ? 1
                    : 0;


            /*
             * Normalize category.
             */

            if (
                $categoryId <= 0
            ) {

                $categoryId = 0;
            }


            /*
             * Existing product.
             */

            $existingProduct =
                $id > 0
                    ? admin_get_product(
                        $pdo,
                        $id
                    )
                    : null;


            if (
                $id > 0
                &&
                !$existingProduct
            ) {

                $error =
                    'Produk yang ingin diedit tidak ditemukan.';

            } elseif (
                $name === ''
            ) {

                $error =
                    'Nama produk wajib diisi.';

            }


            /*
             * =================================================
             * VALIDATE CATEGORY
             * =================================================
             */

            if (
                $error === ''
                &&
                $categoryId > 0
            ) {

                $categoryStmt =
                    $pdo->prepare(
                        "SELECT id
                         FROM product_categories
                         WHERE id = ?
                         LIMIT 1"
                    );

                $categoryStmt->execute([
                    $categoryId
                ]);

                if (
                    !$categoryStmt->fetch()
                ) {

                    $error =
                        'Kategori produk tidak valid.';
                }
            }


            /*
             * =================================================
             * SLUG
             * =================================================
             */

            if (
                $error === ''
            ) {

                if (
                    $slug === ''
                ) {

                    $slug =
                        slugify(
                            $name
                        );

                } else {

                    $slug =
                        slugify(
                            $slug
                        );
                }


                /*
                 * Ensure unique slug.
                 */

                $baseSlug =
                    $slug;

                $counter = 1;


                while (true) {

                    if (
                        $id > 0
                    ) {

                        $slugStmt =
                            $pdo->prepare(
                                "SELECT id
                                 FROM products
                                 WHERE slug = ?
                                 AND id <> ?
                                 LIMIT 1"
                            );

                        $slugStmt->execute([
                            $slug,
                            $id
                        ]);

                    } else {

                        $slugStmt =
                            $pdo->prepare(
                                "SELECT id
                                 FROM products
                                 WHERE slug = ?
                                 LIMIT 1"
                            );

                        $slugStmt->execute([
                            $slug
                        ]);
                    }


                    if (
                        !$slugStmt->fetch()
                    ) {

                        break;
                    }


                    $counter++;

                    $slug =
                        $baseSlug
                        . '-'
                        . $counter;
                }
            }


            /*
             * =================================================
             * CURRENT MEDIA
             * =================================================
             */

            $imagePath =
                $existingProduct['image']
                ??
                '';


            $gallery =
                admin_parse_gallery(
                    $existingProduct['gallery']
                    ??
                    ''
                );


            $newImageUploaded = false;

            $newGalleryUploads = [];

            if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
                $imagePath = '';
            }


            /*
             * =================================================
             * MAIN IMAGE UPLOAD
             * =================================================
             */

            if (
                $error === ''
                &&
                isset(
                    $_FILES['image']
                )
                &&
                (
                    $_FILES['image']['error']
                    ??
                    UPLOAD_ERR_NO_FILE
                )
                !==
                UPLOAD_ERR_NO_FILE
            ) {

                if (
                    !admin_is_valid_image_upload(
                        $_FILES['image']
                    )
                ) {

                    $error =
                        'Foto utama produk tidak valid.';

                } else {

                    $upload =
                        upload_image(
                            $_FILES['image'],
                            $uploadFolder
                        );


                    if (
                        !$upload['success']
                    ) {

                        $error =
                            (string)
                            $upload['message'];

                    } else {

                        $imagePath =
                            (string)
                            $upload['path'];

                        $newImageUploaded =
                            true;
                    }
                }
            } elseif (($selectedImage = selected_media_path('image')) !== '') {
                $imagePath = $selectedImage;
                $newImageUploaded = true;
            }


            /*
             * =================================================
             * GALLERY UPLOAD
             * =================================================
             */

            if (
                $error === ''
                &&
                isset(
                    $_FILES['gallery']
                )
                &&
                is_array(
                    $_FILES['gallery']['name']
                    ??
                    null
                )
            ) {

                $galleryCount =
                    count(
                        $_FILES['gallery']['name']
                    );


                for (
                    $i = 0;
                    $i < $galleryCount;
                    $i++
                ) {

                    $file = [
                        'name' =>
                            $_FILES['gallery']['name'][$i]
                            ??
                            '',

                        'type' =>
                            $_FILES['gallery']['type'][$i]
                            ??
                            '',

                        'tmp_name' =>
                            $_FILES['gallery']['tmp_name'][$i]
                            ??
                            '',

                        'error' =>
                            $_FILES['gallery']['error'][$i]
                            ??
                            UPLOAD_ERR_NO_FILE,

                        'size' =>
                            $_FILES['gallery']['size'][$i]
                            ??
                            0
                    ];


                    if (
                        $file['error']
                        ===
                        UPLOAD_ERR_NO_FILE
                    ) {
                        continue;
                    }


                    if (
                        !admin_is_valid_image_upload(
                            $file
                        )
                    ) {

                        $error =
                            'Salah satu foto gallery tidak valid.';

                        break;
                    }


                    $upload =
                        upload_image(
                            $file,
                            $uploadFolder
                        );


                    if (
                        !$upload['success']
                    ) {

                        $error =
                            (string)
                            $upload['message'];

                        break;
                    }


                    $newPath =
                        (string)
                        $upload['path'];


                    $gallery[] =
                        $newPath;


                    $newGalleryUploads[] =
                        $newPath;
                }
            } elseif ($error === '' && isset($_POST['gallery_media']) && is_array($_POST['gallery_media'])) {
                foreach ($_POST['gallery_media'] as $selectedGallery) {
                    $selectedGallery = trim((string) $selectedGallery);
                    $selectedGallery = str_replace('\\', '/', $selectedGallery);
                    if (
                        $selectedGallery !== ''
                        && preg_match('~^uploads/[a-z0-9_-]+/.+\.[a-z0-9]+$~i', ltrim($selectedGallery, '/'))
                        && str_starts_with(ltrim($selectedGallery, '/'), 'uploads/')
                    ) {
                        $gallery[] = ltrim($selectedGallery, '/');
                        $newGalleryUploads[] = ltrim($selectedGallery, '/');
                    }
                }
            }


            /*
             * =================================================
             * NORMALIZE GALLERY
             * =================================================
             */

            $gallery =
                admin_normalize_gallery(
                    $gallery
                );


            /*
             * =================================================
             * LIMIT GALLERY
             * =================================================
             */

            if (
                count(
                    $gallery
                ) > 20
            ) {

                /*
                 * Hapus file upload baru yang berada
                 * di luar 20 item pertama.
                 */

                $removedGallery =
                    array_slice(
                        $gallery,
                        20
                    );


                foreach (
                    $removedGallery
                    as $removed
                ) {

                    if (
                        in_array(
                            $removed,
                            $newGalleryUploads,
                            true
                        )
                    ) {

                        admin_delete_local_file(
                            $removed
                        );

                    }
                }


                $gallery =
                    array_slice(
                        $gallery,
                        0,
                        20
                    );
            }


            /*
             * =================================================
             * REMOVE OLD GALLERY
             * =================================================
             */

            $removeGallery =
                [];


            if (
                isset(
                    $_POST['remove_gallery']
                )
                &&
                is_array(
                    $_POST['remove_gallery']
                )
            ) {

                foreach (
                    $_POST['remove_gallery']
                    as $removeItem
                ) {

                    if (
                        !is_string(
                            $removeItem
                        )
                    ) {
                        continue;
                    }


                    $removeItem =
                        trim(
                            $removeItem
                        );


                    if (
                        $removeItem === ''
                    ) {
                        continue;
                    }


                    if (
                        in_array(
                            $removeItem,
                            $gallery,
                            true
                        )
                    ) {

                        $removeGallery[] =
                            $removeItem;
                    }
                }
            }


            if (
                !empty(
                    $removeGallery
                )
            ) {

                $gallery =
                    array_values(
                        array_filter(
                            $gallery,
                            static function (
                                $item
                            ) use (
                                $removeGallery
                            ) {

                                return !in_array(
                                    $item,
                                    $removeGallery,
                                    true
                                );
                            }
                        )
                    );
            }


            /*
             * =================================================
             * SAVE DATABASE
             * =================================================
             */

            if (
                $error === ''
            ) {

                try {

                    /*
                     * Convert gallery to JSON.
                     */

                    $galleryJson =
                        !empty($gallery)
                            ? json_encode(
                                $gallery,
                                JSON_UNESCAPED_UNICODE
                                |
                                JSON_UNESCAPED_SLASHES
                            )
                            : null;


                    /*
                     * Category NULL.
                     */

                    $categoryValue =
                        $categoryId > 0
                            ? $categoryId
                            : null;


                    $productUrlValue =
                        $productUrl !== ''
                            ? $productUrl
                            : null;


                    if (
                        $id > 0
                    ) {

                        $stmt =
                            $pdo->prepare(
                                "UPDATE products
                                 SET
                                    category_id = ?,
                                    name = ?,
                                    slug = ?,
                                    short_description = ?,
                                    description = ?,
                                    specifications = ?,
                                    image = ?,
                                    gallery = ?,
                                    product_url = ?,
                                    meta_title = ?,
                                    meta_description = ?,
                                    image_alt = ?,
                                    is_featured = ?,
                                    is_active = ?,
                                    sort_order = ?
                                 WHERE id = ?"
                            );


                        $stmt->execute([
                            $categoryValue,
                            $name,
                            $slug,
                            $shortDescription !== ''
                                ? $shortDescription
                                : null,
                            $description !== ''
                                ? $description
                                : null,
                            $specifications !== ''
                                ? $specifications
                                : null,
                            $imagePath !== ''
                                ? $imagePath
                                : null,
                            $galleryJson,
                            $productUrlValue,
                            $metaTitle !== '' ? $metaTitle : null,
                            $metaDescription !== '' ? $metaDescription : null,
                            $imageAlt !== '' ? $imageAlt : null,
                            $isFeatured,
                            $isActive,
                            $sortOrder,
                            $id
                        ]);


                        /*
                         * Hapus file lama setelah DB berhasil.
                         */

                        if (
                            (
                                $newImageUploaded
                                ||
                                (
                                    isset($_POST['remove_image'])
                                    &&
                                    $_POST['remove_image'] === '1'
                                )
                            )
                            &&
                            !empty(
                                $existingProduct['image']
                            )
                            &&
                            $existingProduct['image']
                            !==
                            $imagePath
                        ) {

                            admin_delete_local_file(
                                $existingProduct['image']
                            );
                        }


                        foreach (
                            $removeGallery
                            as $removedGalleryFile
                        ) {

                            /*
                             * Hanya hapus jika memang file lokal lama.
                             * File baru tidak perlu dihapus di sini jika
                             * user memilih remove pada request yang sama,
                             * karena sudah tertangani sebelumnya.
                             */

                            if (
                                !in_array(
                                    $removedGalleryFile,
                                    $newGalleryUploads,
                                    true
                                )
                            ) {

                                admin_delete_local_file(
                                    $removedGalleryFile
                                );
                            }
                        }


                        set_flash(
                            'success',
                            'Produk berhasil diperbarui.'
                        );

                    } else {

                        $stmt =
                            $pdo->prepare(
                                "INSERT INTO products
                                (
                                    category_id,
                                    name,
                                    slug,
                                    short_description,
                                    description,
                                    specifications,
                                    image,
                                    gallery,
                                    product_url,
                                    meta_title,
                                    meta_description,
                                    image_alt,
                                    is_featured,
                                    is_active,
                                    sort_order
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?
                                )"
                            );


                        $stmt->execute([
                            $categoryValue,
                            $name,
                            $slug,
                            $shortDescription !== ''
                                ? $shortDescription
                                : null,
                            $description !== ''
                                ? $description
                                : null,
                            $specifications !== ''
                                ? $specifications
                                : null,
                            $imagePath !== ''
                                ? $imagePath
                                : null,
                            $galleryJson,
                            $productUrlValue,
                            $metaTitle !== '' ? $metaTitle : null,
                            $metaDescription !== '' ? $metaDescription : null,
                            $imageAlt !== '' ? $imageAlt : null,
                            $isFeatured,
                            $isActive,
                            $sortOrder
                        ]);


                        set_flash(
                            'success',
                            'Produk berhasil ditambahkan.'
                        );
                    }


                    redirect(
                        $listUrl
                    );


                } catch (
                    Throwable $e
                ) {

                    /*
                     * Jika DB gagal, file baru dibersihkan.
                     */

                    if (
                        $newImageUploaded
                    ) {

                        admin_delete_local_file(
                            $imagePath
                        );
                    }


                    foreach (
                        $newGalleryUploads
                        as $newGallery
                    ) {

                        admin_delete_local_file(
                            $newGallery
                        );
                    }


                    $error =
                        'Produk gagal disimpan. Silakan coba kembali.';
                }
            }


            /*
             * Jika error, tetap di form.
             */

            if (
                $error !== ''
            ) {

                $action =
                    $id > 0
                        ? 'edit'
                        : 'add';

                $editId =
                    $id;
            }
        }


        /*
         * =====================================================
         * DELETE PRODUCT
         * =====================================================
         */

        elseif (
            $formAction === 'delete'
        ) {

            $id =
                (int)
                (
                    $_POST['id']
                    ??
                    0
                );


            if (
                $id <= 0
            ) {

                $error =
                    'ID produk tidak valid.';

            } else {

                try {

                    $product =
                        admin_get_product(
                            $pdo,
                            $id
                        );


                    if (
                        !$product
                    ) {

                        $error =
                            'Produk tidak ditemukan.';

                    } else {

                        $gallery =
                            admin_parse_gallery(
                                $product['gallery']
                                ??
                                ''
                            );


                        $stmt =
                            $pdo->prepare(
                                "DELETE FROM products
                                 WHERE id = ?"
                            );


                        $stmt->execute([
                            $id
                        ]);


                        /*
                         * Hapus foto utama.
                         */

                        admin_delete_local_file(
                            $product['image']
                            ??
                            ''
                        );


                        /*
                         * Hapus gallery.
                         */

                        foreach (
                            $gallery
                            as $galleryFile
                        ) {

                            admin_delete_local_file(
                                $galleryFile
                            );
                        }


                        set_flash(
                            'success',
                            'Produk berhasil dihapus.'
                        );


                        redirect(
                            $listUrl
                        );
                    }


                } catch (
                    Throwable $e
                ) {

                    $error =
                        'Produk gagal dihapus.';
                }
            }
        }


        /*
         * =====================================================
         * TOGGLE STATUS
         * =====================================================
         */

        elseif (
            $formAction === 'toggle'
        ) {

            $id =
                (int)
                (
                    $_POST['id']
                    ??
                    0
                );


            if (
                $id <= 0
            ) {

                $error =
                    'ID produk tidak valid.';

            } else {

                try {

                    $product =
                        admin_get_product(
                            $pdo,
                            $id
                        );


                    if (
                        !$product
                    ) {

                        $error =
                            'Produk tidak ditemukan.';

                    } else {

                        $currentStatus =
                            (int)
                            (
                                $product['is_active']
                                ??
                                0
                            );


                        $newStatus =
                            $currentStatus === 1
                                ? 0
                                : 1;


                        $stmt =
                            $pdo->prepare(
                                "UPDATE products
                                 SET is_active = ?
                                 WHERE id = ?"
                            );


                        $stmt->execute([
                            $newStatus,
                            $id
                        ]);


                        set_flash(
                            'success',
                            $newStatus === 1
                                ? 'Produk diaktifkan.'
                                : 'Produk dinonaktifkan.'
                        );


                        redirect(
                            $listUrl
                        );
                    }


                } catch (
                    Throwable $e
                ) {

                    $error =
                        'Status produk gagal diubah.';
                }
            }
        }


        /*
         * =====================================================
         * TOGGLE FEATURED
         * =====================================================
         */

        elseif (
            $formAction === 'featured'
        ) {

            $id =
                (int)
                (
                    $_POST['id']
                    ??
                    0
                );


            if (
                $id <= 0
            ) {

                $error =
                    'ID produk tidak valid.';

            } else {

                try {

                    $product =
                        admin_get_product(
                            $pdo,
                            $id
                        );


                    if (
                        !$product
                    ) {

                        $error =
                            'Produk tidak ditemukan.';

                    } else {

                        $current =
                            (int)
                            (
                                $product['is_featured']
                                ??
                                0
                            );


                        $new =
                            $current === 1
                                ? 0
                                : 1;


                        $stmt =
                            $pdo->prepare(
                                "UPDATE products
                                 SET is_featured = ?
                                 WHERE id = ?"
                            );


                        $stmt->execute([
                            $new,
                            $id
                        ]);


                        set_flash(
                            'success',
                            $new === 1
                                ? 'Produk ditandai sebagai unggulan.'
                                : 'Produk dihapus dari produk unggulan.'
                        );


                        redirect(
                            $listUrl
                        );
                    }


                } catch (
                    Throwable $e
                ) {

                    $error =
                        'Status produk unggulan gagal diubah.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FLASH
|--------------------------------------------------------------------------
*/

if (
    $flash === null
) {

    $flash =
        get_flash();
}


/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];

try {

    $stmt =
        $pdo->query(
            "SELECT
                id,
                name,
                slug,
                is_active
             FROM product_categories
             WHERE is_active = 1
             ORDER BY
                sort_order ASC,
                name ASC"
        );


    $categories =
        $stmt->fetchAll();

} catch (
    Throwable $e
) {

    $categories = [];
}


/*
|--------------------------------------------------------------------------
| SEARCH / FILTER
|--------------------------------------------------------------------------
*/

$search =
    get_string(
        'search'
    );

$categoryFilter =
    get_int(
        'category_id',
        0
    );

$statusFilter =
    get_string(
        'status',
        'all'
    );

$featuredFilter =
    get_string(
        'featured',
        'all'
    );


/*
|--------------------------------------------------------------------------
| PRODUCT LIST
|--------------------------------------------------------------------------
*/

$products = [];

try {

    $sql = "
        SELECT
            p.*,
            c.name AS category_name
        FROM products p
        LEFT JOIN product_categories c
            ON c.id = p.category_id
        WHERE 1 = 1
    ";

    $params = [];


    if (
        $search !== ''
    ) {

        $sql .= "
            AND (
                p.name LIKE ?
                OR p.short_description LIKE ?
                OR p.slug LIKE ?
            )
        ";

        $like =
            '%' .
            $search .
            '%';

        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }


    if (
        $categoryFilter > 0
    ) {

        $sql .= "
            AND p.category_id = ?
        ";

        $params[] =
            $categoryFilter;
    }


    if (
        $statusFilter === 'active'
    ) {

        $sql .= "
            AND p.is_active = 1
        ";

    } elseif (
        $statusFilter === 'inactive'
    ) {

        $sql .= "
            AND p.is_active = 0
        ";
    }


    if (
        $featuredFilter === 'yes'
    ) {

        $sql .= "
            AND p.is_featured = 1
        ";

    } elseif (
        $featuredFilter === 'no'
    ) {

        $sql .= "
            AND p.is_featured = 0
        ";
    }


    $sql .= "
        ORDER BY
            p.sort_order ASC,
            p.id DESC
    ";


    $stmt =
        $pdo->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $products =
        $stmt->fetchAll();

} catch (
    Throwable $e
) {

    $products = [];
}


/*
|--------------------------------------------------------------------------
| EDIT DATA
|--------------------------------------------------------------------------
*/

$editProduct = null;

if (
    $action === 'edit'
    &&
    $editId > 0
) {

    $editProduct =
        admin_get_product(
            $pdo,
            $editId
        );


    if (
        !$editProduct
        &&
        $error === ''
    ) {

        set_flash(
            'error',
            'Produk tidak ditemukan.'
        );


        redirect(
            $listUrl
        );
    }
}


/*
|--------------------------------------------------------------------------
| FORM DATA
|--------------------------------------------------------------------------
*/

$formData = [

    'id' =>
        $editProduct['id']
        ??
        0,

    'category_id' =>
        $editProduct['category_id']
        ??
        '',

    'name' =>
        $editProduct['name']
        ??
        '',

    'slug' =>
        $editProduct['slug']
        ??
        '',

    'short_description' =>
        $editProduct['short_description']
        ??
        '',

    'description' =>
        $editProduct['description']
        ??
        '',

    'specifications' =>
        $editProduct['specifications']
        ??
        '',

    'image' =>
        $editProduct['image']
        ??
        '',

    'gallery' =>
        admin_parse_gallery(
            $editProduct['gallery']
            ??
            ''
        ),

    'product_url' =>
        $editProduct['product_url']
        ??
        '',

    'meta_title' =>
        $editProduct['meta_title']
        ??
        '',

    'meta_description' =>
        $editProduct['meta_description']
        ??
        '',

    'image_alt' =>
        $editProduct['image_alt']
        ??
        '',

    'is_featured' =>
        isset(
            $editProduct['is_featured']
        )
            ? (int)
                $editProduct['is_featured']
            : 0,

    'is_active' =>
        isset(
            $editProduct['is_active']
        )
            ? (int)
                $editProduct['is_active']
            : 1,

    'sort_order' =>
        $editProduct['sort_order']
        ??
        0
];


/*
|--------------------------------------------------------------------------
| CURRENT IMAGE URL
|--------------------------------------------------------------------------
*/

$formImageUrl =
    $formData['image'] !== ''
        ? media_url(
            $formData['image']
        )
        : '';


/*
|--------------------------------------------------------------------------
| COUNTERS
|--------------------------------------------------------------------------
*/

$totalProducts =
    count($products);

$activeProducts = 0;
$featuredProducts = 0;

foreach (
    $products as $product
) {

    if (
        (int)
        (
            $product['is_active']
            ??
            0
        )
        ===
        1
    ) {

        $activeProducts++;
    }


    if (
        (int)
        (
            $product['is_featured']
            ??
            0
        )
        ===
        1
    ) {

        $featuredProducts++;
    }
}


$isForm =
    $action === 'add'
    ||
    (
        $action === 'edit'
        &&
        $editProduct
    )
    ||
    $error !== '';

$pageTitle =
    $isForm
        ? (
            $editProduct
                ? 'Edit Produk'
                : 'Tambah Produk'
        )
        : 'Kelola Produk';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="robots"
        content="noindex,nofollow"
    >

    <title>
        <?= e(
            $pageTitle
        ); ?>
        | <?= e(
            $siteName
        ); ?>
    </title>


    <!-- ===================================================
         FONTS
    ==================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ===================================================
         ICON
    ==================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >


    <!-- ===================================================
         PRODUCT ADMIN CSS
    ==================================================== -->

    <style>

        :root {

            --red:#d90000;
            --red-dark:#b30000;
            --black:#111111;
            --black-2:#1b1b1b;
            --white:#ffffff;
            --bg:#f5f6f8;
            --text:#191919;
            --muted:#777777;
            --line:#e6e6e6;
            --green:#148343;
            --blue:#2563eb;
            --orange:#bd7100;

            --shadow:
                0 8px 28px rgba(0,0,0,.055);

            --font:
                "Poppins",
                "Inter",
                Arial,
                sans-serif;

            --font-body:
                "Inter",
                Arial,
                sans-serif;
        }


        * {
            box-sizing:border-box;
        }


        html,
        body {
            margin:0;
            padding:0;
            min-height:100%;
        }


        body {
            background:var(--bg);
            color:var(--text);
            font-family:var(--font-body);
            overflow-x:hidden;
        }


        a {
            color:inherit;
            text-decoration:none;
        }


        button,
        input,
        textarea,
        select {
            font:inherit;
        }


        button {
            cursor:pointer;
        }


        /* =================================================
           LAYOUT
        ================================================== */

        .layout {
            min-height:100vh;
        }


        .sidebar {
            position:fixed;
            left:0;
            top:0;
            bottom:0;
            z-index:1100;

            width:255px;

            display:flex;
            flex-direction:column;

            color:
                rgba(255,255,255,.70);

            background:
                linear-gradient(
                    180deg,
                    #101010,
                    #181818
                );

            border-right:
                1px solid
                rgba(255,255,255,.05);

            transition:
                transform .3s ease;
        }


        .sidebar-brand {
            min-height:82px;

            display:flex;
            align-items:center;

            padding:0 22px;

            border-bottom:
                1px solid
                rgba(255,255,255,.07);
        }


        .sidebar-brand img {
            width:auto;
            max-width:185px;
            max-height:52px;
            object-fit:contain;
        }


        .sidebar-fallback {
            display:flex;
            flex-direction:column;

            line-height:1;
        }


        .sidebar-fallback strong {
            color:white;
            font-family:var(--font);
            font-size:17px;
            font-weight:800;
        }


        .sidebar-fallback small {
            margin-top:6px;
            color:#ff3838;
            font-size:8px;
            font-weight:800;
            letter-spacing:.18em;
        }


        .sidebar-scroll {
            flex:1;
            overflow-y:auto;
            padding:18px 12px;
        }


        .sidebar-label {
            display:block;
            padding:
                0 12px 10px;

            color:
                rgba(255,255,255,.27);

            font-size:9px;
            font-weight:800;
            letter-spacing:.13em;
            text-transform:uppercase;
        }


        .sidebar-menu {
            display:flex;
            flex-direction:column;
            gap:4px;
        }


        .sidebar-link {
            min-height:46px;

            display:flex;
            align-items:center;

            gap:12px;

            padding:0 13px;

            color:
                rgba(255,255,255,.61);

            border-radius:10px;

            font-size:11px;
            font-weight:600;

            transition:
                background .2s ease,
                color .2s ease;
        }


        .sidebar-link i {
            width:18px;
            text-align:center;
        }


        .sidebar-link:hover {
            color:white;
            background:
                rgba(255,255,255,.06);
        }


        .sidebar-link.active {
            color:white;
            background:var(--red);

            box-shadow:
                0 8px 20px
                rgba(217,0,0,.18);
        }


        .sidebar-user {
            padding:12px;

            border-top:
                1px solid
                rgba(255,255,255,.07);
        }


        .sidebar-user-box {
            display:flex;
            align-items:center;
            gap:10px;

            padding:10px;

            background:
                rgba(255,255,255,.045);

            border-radius:10px;
        }


        .avatar {
            width:34px;
            height:34px;

            display:flex;
            align-items:center;
            justify-content:center;

            flex:0 0 auto;

            color:white;
            background:var(--red);

            border-radius:9px;

            font-size:11px;
            font-weight:800;
        }


        .user-text {
            min-width:0;
        }


        .user-text strong {
            display:block;
            max-width:145px;
            overflow:hidden;

            color:white;

            font-size:10px;
            font-weight:700;

            white-space:nowrap;
            text-overflow:ellipsis;
        }


        .user-text span {
            display:block;
            margin-top:2px;

            color:
                rgba(255,255,255,.34);

            font-size:8px;
        }


        .main {
            width:calc(100% - 255px);
            margin-left:255px;
            min-width:0;
        }


        /* =================================================
           TOPBAR
        ================================================== */

        .topbar {
            position:sticky;
            top:0;
            z-index:900;

            min-height:82px;

            display:flex;
            align-items:center;
            justify-content:space-between;

            gap:20px;

            padding:
                0 30px;

            background:
                rgba(255,255,255,.94);

            border-bottom:
                1px solid var(--line);

            backdrop-filter:
                blur(14px);
        }


        .topbar-left {
            display:flex;
            align-items:center;
            gap:14px;
        }


        .mobile-toggle {
            display:none;

            width:42px;
            height:42px;

            align-items:center;
            justify-content:center;

            color:var(--black);

            background:white;

            border:
                1px solid var(--line);

            border-radius:10px;
        }


        .topbar-title {
            display:flex;
            flex-direction:column;
        }


        .topbar-title span {
            color:var(--muted);
            font-size:8px;
            font-weight:700;
            letter-spacing:.10em;
        }


        .topbar-title strong {
            margin-top:3px;

            color:var(--black);
            font-family:var(--font);
            font-size:16px;
            font-weight:800;
        }


        .topbar-right {
            display:flex;
            align-items:center;
            gap:10px;
        }


        .view-site {
            min-height:40px;

            display:inline-flex;
            align-items:center;
            gap:7px;

            padding:
                0 12px;

            color:var(--black);
            background:white;

            border:
                1px solid var(--line);

            border-radius:9px;

            font-size:9px;
            font-weight:700;
        }


        .view-site:hover {
            color:var(--red);

            border-color:
                rgba(217,0,0,.25);
        }


        .user-pill {
            min-height:44px;

            display:flex;
            align-items:center;
            gap:8px;

            padding:
                4px 5px;

            background:white;

            border:
                1px solid var(--line);

            border-radius:11px;
        }


        .user-pill-name {
            display:flex;
            flex-direction:column;
            min-width:0;
        }


        .user-pill-name strong {
            max-width:120px;
            overflow:hidden;

            font-size:9px;
            font-weight:700;

            white-space:nowrap;
            text-overflow:ellipsis;
        }


        .user-pill-name span {
            color:var(--muted);
            font-size:7px;
        }


        .logout {
            width:34px;
            height:34px;

            display:flex;
            align-items:center;
            justify-content:center;

            color:#777;

            border:0;
            background:transparent;
            border-radius:8px;
        }


        .logout:hover {
            color:var(--red);
            background:
                rgba(217,0,0,.06);
        }


        /* =================================================
           CONTENT
        ================================================== */

        .content {
            padding:30px;
        }


        .page-header {
            display:flex;
            align-items:flex-end;
            justify-content:space-between;

            gap:20px;

            margin-bottom:22px;
        }


        .page-kicker {
            display:flex;
            align-items:center;
            gap:7px;

            margin-bottom:7px;

            color:var(--red);

            font-size:8px;
            font-weight:800;
            letter-spacing:.13em;
            text-transform:uppercase;
        }


        .page-kicker::before {
            content:"";

            width:20px;
            height:2px;

            background:var(--red);
            border-radius:999px;
        }


        .page-title {
            margin:0;

            color:var(--black);

            font-family:var(--font);
            font-size:26px;
            font-weight:800;
        }


        .page-description {
            margin:
                6px 0 0;

            color:var(--muted);

            font-size:10px;
        }


        .primary-button {
            min-height:43px;

            display:inline-flex;
            align-items:center;
            justify-content:center;

            gap:8px;

            padding:
                0 15px;

            color:white;
            background:var(--red);

            border:
                1px solid var(--red);

            border-radius:9px;

            font-size:9px;
            font-weight:800;

            box-shadow:
                0 9px 22px
                rgba(217,0,0,.18);

            transition:
                transform .2s ease,
                background .2s ease;
        }


        .primary-button:hover {
            transform:translateY(-2px);
            background:var(--red-dark);
        }


        /* =================================================
           ALERT
        ================================================== */

        .alert {
            display:flex;
            align-items:flex-start;

            gap:9px;

            margin-bottom:18px;

            padding:
                12px 14px;

            border-radius:10px;

            font-size:10px;
            font-weight:600;

            line-height:1.55;
        }


        .alert-success {
            color:#146d39;
            background:#ecfaf1;
            border:1px solid #c9ecd6;
        }


        .alert-error {
            color:#921212;
            background:#fff1f1;
            border:1px solid #f2caca;
        }


        /* =================================================
           MINI STATS
        ================================================== */

        .mini-stats {
            display:grid;

            grid-template-columns:
                repeat(
                    4,
                    1fr
                );

            gap:13px;

            margin-bottom:18px;
        }


        .mini-stat {
            padding:
                18px;

            background:white;

            border:
                1px solid var(--line);

            border-radius:
                13px;

            box-shadow:
                0 5px 18px
                rgba(0,0,0,.03);
        }


        .mini-stat-top {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
        }


        .mini-stat-icon {
            width:37px;
            height:37px;

            display:flex;
            align-items:center;
            justify-content:center;

            color:var(--red);

            background:
                rgba(217,0,0,.07);

            border-radius:9px;

            font-size:13px;
        }


        .mini-stat-icon.green {
            color:var(--green);
            background:
                rgba(20,131,67,.07);
        }


        .mini-stat-icon.black {
            color:var(--black);
            background:
                rgba(0,0,0,.06);
        }


        .mini-stat-icon.blue {
            color:var(--blue);
            background:
                rgba(37,99,235,.07);
        }


        .mini-stat strong {
            display:block;

            margin-top:14px;

            color:var(--black);

            font-family:var(--font);

            font-size:23px;
            font-weight:800;
        }


        .mini-stat span {
            display:block;

            margin-top:4px;

            color:var(--muted);

            font-size:8px;
            font-weight:600;
        }


        /* =================================================
           FILTER
        ================================================== */

        .filter-panel {
            margin-bottom:16px;

            padding:
                17px;

            background:white;

            border:
                1px solid var(--line);

            border-radius:
                13px;

            box-shadow:
                0 5px 18px
                rgba(0,0,0,.03);
        }


        .filter-form {
            display:grid;

            grid-template-columns:
                minmax(
                    220px,
                    1.6fr
                )
                repeat(
                    3,
                    minmax(
                        120px,
                        .7fr
                    )
                )
                auto;

            gap:
                9px;
        }


        .filter-input,
        .filter-select {
            width:100%;
            height:41px;

            padding:
                0 11px;

            color:var(--black);
            background:white;

            border:
                1px solid
                #dfdfdf;

            border-radius:8px;

            outline:none;

            font-size:9px;
        }


        .filter-input:focus,
        .filter-select:focus {
            border-color:var(--red);

            box-shadow:
                0 0 0 3px
                rgba(217,0,0,.07);
        }


        .filter-button {
            height:41px;

            display:inline-flex;
            align-items:center;
            justify-content:center;

            gap:6px;

            padding:
                0 13px;

            color:white;
            background:var(--black);

            border:
                1px solid var(--black);

            border-radius:8px;

            font-size:9px;
            font-weight:800;
        }


        .filter-button:hover {
            background:#292929;
        }


        /* =================================================
           PRODUCT TABLE PANEL
        ================================================== */

        .panel {
            padding:
                20px;

            background:white;

            border:
                1px solid var(--line);

            border-radius:
                14px;

            box-shadow:
                var(--shadow);
        }


        .panel-heading {
            display:flex;
            align-items:flex-end;
            justify-content:space-between;

            gap:15px;

            margin-bottom:16px;
        }


        .panel-heading-left h2 {
            margin:0;

            color:var(--black);

            font-family:var(--font);

            font-size:15px;
            font-weight:800;
        }


        .panel-heading-left p {
            margin:
                4px 0 0;

            color:var(--muted);

            font-size:9px;
        }


        .panel-count {
            color:#999;
            font-size:8px;
        }


        .table-wrap {
            width:100%;
            overflow-x:auto;
        }


        .product-table {
            width:100%;

            min-width:850px;

            border-collapse:
                collapse;
        }


        .product-table th {
            padding:
                11px 8px;

            color:#929292;

            background:#fafafa;

            border-bottom:
                1px solid var(--line);

            text-align:left;

            font-size:8px;
            font-weight:800;

            letter-spacing:.06em;
            text-transform:uppercase;
        }


        .product-table td {
            padding:
                11px 8px;

            border-bottom:
                1px solid #f0f0f0;

            color:var(--text);

            font-size:9px;

            vertical-align:middle;
        }


        .product-table tr:last-child td {
            border-bottom:0;
        }


        .product-cell {
            display:flex;
            align-items:center;
            gap:10px;

            min-width:0;
        }


        .product-thumb {
            position:relative;

            width:51px;
            height:51px;

            flex:0 0 auto;

            display:flex;
            align-items:center;
            justify-content:center;

            overflow:hidden;

            color:#b4b4b4;
            background:#f2f2f2;

            border-radius:9px;
        }


        .product-thumb img {
            width:100%;
            height:100%;
            object-fit:cover;
        }


        .product-cell-text {
            min-width:0;
        }


        .product-cell-text strong {
            display:block;

            max-width:270px;

            overflow:hidden;

            color:var(--black);

            font-size:10px;
            font-weight:700;

            white-space:nowrap;
            text-overflow:ellipsis;
        }


        .product-cell-text span {
            display:block;

            margin-top:2px;

            color:#999;

            font-size:7px;
        }


        .category-badge {
            display:inline-flex;

            align-items:center;

            padding:
                5px 7px;

            color:#555;

            background:#f2f2f2;

            border-radius:5px;

            font-size:7px;
            font-weight:700;
        }


        .badge {
            display:inline-flex;
            align-items:center;

            gap:4px;

            padding:
                5px 7px;

            border-radius:5px;

            font-size:7px;
            font-weight:800;
        }


        .badge-active {
            color:#116b37;
            background:#ebf8ef;
        }


        .badge-inactive {
            color:#777;
            background:#eeeeee;
        }


        .badge-featured {
            color:#9d1c1c;
            background:#fff0f0;
        }


        .badge-normal {
            color:#777;
            background:#f0f0f0;
        }


        .table-actions {
            display:flex;
            flex-wrap:wrap;
            gap:5px;
        }


        .icon-button {
            width:31px;
            height:31px;

            display:flex;
            align-items:center;
            justify-content:center;

            color:#666;

            background:white;

            border:
                1px solid
                #dfdfdf;

            border-radius:7px;

            transition:
                color .2s ease,
                border-color .2s ease,
                background .2s ease;
        }


        .icon-button:hover {
            color:var(--red);

            border-color:
                rgba(217,0,0,.25);
        }


        .icon-button.featured {
            color:#b06f00;
        }


        .icon-button.danger:hover {
            color:white;
            background:#b71f1f;
            border-color:#b71f1f;
        }


        .inline-form {
            margin:0;
            display:inline;
        }


        /* =================================================
           FORM
        ================================================== */

        .form-panel {
            margin-bottom:18px;

            padding:
                21px;

            background:white;

            border:
                1px solid var(--line);

            border-radius:
                14px;

            box-shadow:
                var(--shadow);
        }


        .form-title {
            margin:
                0 0 18px;

            color:var(--black);

            font-family:var(--font);

            font-size:15px;
            font-weight:800;
        }


        .form-grid {
            display:grid;

            grid-template-columns:
                1fr
                1fr;

            gap:
                16px;
        }


        .form-group {
            min-width:0;
            margin-bottom:2px;
        }


        .form-group.full {
            grid-column:
                1 / -1;
        }


        .form-label {
            display:block;

            margin-bottom:7px;

            color:var(--black);

            font-size:9px;
            font-weight:700;
        }


        .form-label span {
            color:#999;
            font-size:8px;
            font-weight:500;
        }


        .form-control,
        .form-select,
        .form-textarea {
            width:100%;

            color:var(--black);
            background:white;

            border:
                1px solid #dfdfdf;

            border-radius:9px;

            outline:none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;

            font-size:10px;
        }


        .form-control,
        .form-select {
            min-height:45px;
            padding:
                0 12px;
        }


        .form-textarea {
            min-height:120px;

            padding:
                11px 12px;

            resize:vertical;

            line-height:1.7;
        }


        .form-control:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color:var(--red);

            box-shadow:
                0 0 0 4px
                rgba(
                    217,
                    0,
                    0,
                    .07
                );
        }


        .help-text {
            margin-top:5px;

            color:#999;

            font-size:8px;
            line-height:1.5;
        }


        .editor-note {
            padding:
                9px 10px;

            margin-bottom:
                8px;

            color:#757575;

            background:#fafafa;

            border:
                1px solid #eeeeee;

            border-radius:7px;

            font-size:8px;
            line-height:1.5;
        }


        .media-box {
            padding:
                15px;

            background:#fafafa;

            border:
                1px dashed
                #d8d8d8;

            border-radius:11px;
        }


        .media-box input[type="file"] {
            width:100%;
            font-size:9px;
        }


        .current-image {
            margin-top:10px;

            width:
                min(
                    100%,
                    280px
                );

            background:white;

            border:
                1px solid #e9e9e9;

            border-radius:9px;

            padding:8px;
        }


        .current-image img {
            width:100%;
            aspect-ratio:16 / 10;

            object-fit:contain;

            background:#f4f4f4;

            border-radius:7px;
        }


        .current-image-name {
            margin-top:6px;

            color:#888;

            overflow:hidden;

            font-size:8px;

            white-space:nowrap;
            text-overflow:ellipsis;
        }


        .gallery-current {
            display:grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(
                        90px,
                        1fr
                    )
                );

            gap:
                9px;

            margin-top:10px;
        }


        .gallery-item {
            position:relative;

            padding:5px;

            background:white;

            border:
                1px solid #e5e5e5;

            border-radius:8px;

            overflow:hidden;
        }


        .gallery-item img {
            width:100%;
            aspect-ratio:1;

            object-fit:cover;

            border-radius:5px;
        }


        .gallery-remove {
            position:absolute;

            top:7px;
            right:7px;

            display:inline-flex;
            align-items:center;
            gap:4px;

            padding:
                4px 6px;

            color:#9a1818;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .93
                );

            border:
                1px solid
                #e9caca;

            border-radius:5px;

            font-size:7px;
            font-weight:800;
        }


        .gallery-remove input {
            width:12px;
            height:12px;

            margin:0;

            accent-color:var(--red);
        }


        .options {
            display:flex;
            align-items:center;
            flex-wrap:wrap;

            gap:
                15px;

            padding:
                15px 0 0;

            margin-top:
                3px;

            border-top:
                1px solid #eeeeee;
        }


        .check-label {
            display:inline-flex;

            align-items:center;

            gap:7px;

            color:#666;

            font-size:9px;
            font-weight:600;
        }


        .check-label input {
            width:15px;
            height:15px;

            accent-color:var(--red);
        }


        .form-actions {
            display:flex;
            align-items:center;
            justify-content:flex-end;

            gap:
                8px;

            padding-top:
                18px;

            margin-top:
                5px;

            border-top:
                1px solid #eeeeee;
        }


        .secondary-button {
            min-height:42px;

            display:inline-flex;
            align-items:center;
            justify-content:center;

            gap:7px;

            padding:
                0 14px;

            color:#555;

            background:white;

            border:
                1px solid #dedede;

            border-radius:8px;

            font-size:9px;
            font-weight:800;
        }


        .secondary-button:hover {
            color:var(--red);

            border-color:
                rgba(217,0,0,.25);
        }


        /* =================================================
           EMPTY
        ================================================== */

        .empty {
            padding:
                50px 20px;

            text-align:center;

            color:#999;

            border:
                1px dashed #dcdcdc;

            border-radius:10px;
        }


        .empty i {
            display:block;

            margin-bottom:8px;

            color:#ccc;

            font-size:28px;
        }


        .empty strong {
            display:block;

            margin-bottom:4px;

            color:#555;

            font-family:var(--font);

            font-size:12px;
        }


        .empty span {
            font-size:9px;
        }


        /* =================================================
           MOBILE OVERLAY
        ================================================== */

        .overlay {
            position:fixed;
            inset:0;
            z-index:1090;

            background:
                rgba(0,0,0,.50);

            opacity:0;
            visibility:hidden;

            transition:
                opacity .25s ease,
                visibility .25s ease;
        }


        .overlay.show {
            opacity:1;
            visibility:visible;
        }


        /* =================================================
           RESPONSIVE
        ================================================== */

        @media (max-width:1150px) {

            .sidebar {
                width:230px;
            }


            .main {
                width:
                    calc(
                        100% - 230px
                    );

                margin-left:
                    230px;
            }


            .mini-stats {
                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );
            }


            .filter-form {
                grid-template-columns:
                    1fr
                    1fr;
            }

        }


        @media (max-width:900px) {

            .sidebar {
                transform:
                    translateX(-100%);
            }


            .sidebar.open {
                transform:
                    translateX(0);
            }


            .main {
                width:100%;
                margin-left:0;
            }


            .mobile-toggle {
                display:flex;
            }


            .topbar {
                padding:
                    0 20px;
            }


            .content {
                padding:
                    20px;
            }

        }


        @media (max-width:700px) {

            .page-header {
                align-items:flex-start;
                flex-direction:column;
            }


            .primary-button {
                width:100%;
            }


            .mini-stats {
                grid-template-columns:
                    1fr;
            }


            .filter-form {
                grid-template-columns:
                    1fr;
            }


            .form-grid {
                grid-template-columns:
                    1fr;
            }


            .form-group.full {
                grid-column:auto;
            }


            .form-actions {
                flex-direction:
                    column-reverse;
            }


            .form-actions .primary-button,
            .form-actions .secondary-button {
                width:100%;
            }


            .gallery-current {
                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );
            }

        }


        @media (max-width:550px) {

            .topbar {
                min-height:70px;
                padding:
                    0 13px;
            }


            .content {
                padding:
                    13px;
            }


            .topbar-title strong {
                font-size:14px;
            }


            .view-site span,
            .user-pill-name {
                display:none;
            }


            .panel,
            .form-panel,
            .filter-panel {
                padding:
                    15px;
            }


            .page-title {
                font-size:
                    22px;
            }

        }

    </style>

</head>


<body>
<?php render_admin_sidebar_assets(); ?>
<?php render_admin_sidebar('products.php'); ?>


<div
    class="layout"
>


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >

        <div
            class="sidebar-brand"
        >

            <a
                href="<?= e(
                    base_url(
                        'admin/dashboard.php'
                    )
                ); ?>"
            >

                <?php if (
                    $siteLogoUrl !== ''
                ): ?>

                    <img
                        src="<?= e(
                            $siteLogoUrl
                        ); ?>"
                        alt="<?= e(
                            $siteName
                        ); ?>"
                    >

                <?php else: ?>

                    <div
                        class="sidebar-fallback"
                    >

                        <strong>
                            STAR PARTS
                        </strong>

                        <small>
                            MOTOR
                        </small>

                    </div>

                <?php endif; ?>

            </a>

        </div>


        <div
            class="sidebar-scroll"
        >

            <span
                class="sidebar-label"
            >
                MAIN MENU
            </span>


            <nav
                class="sidebar-menu"
            >

                <a
                    href="<?= e(
                        base_url(
                            'admin/dashboard.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-gauge-high"
                    ></i>

                    Dashboard

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/banners.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-regular fa-images"
                    ></i>

                    Banner

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/products.php'
                        )
                    ); ?>"
                    class="sidebar-link active"
                >

                    <i
                        class="fa-solid fa-box-open"
                    ></i>

                    Produk

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/fdr-details.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >
                    <i class="fa-solid fa-motorcycle"></i>
                    Konten Detail Ban
                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/categories.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-layer-group"
                    ></i>

                    Kategori

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/brands.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-tags"
                    ></i>

                    Brand

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/posts.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-newspaper"
                    ></i>

                    Berita

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/media.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-photo-film"
                    ></i>

                    Media

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/locations.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-map-location-dot"
                    ></i>

                    Lokasi

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/messages.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-envelope"
                    ></i>

                    Pesan

                </a>


                <span
                    class="sidebar-label"
                    style="margin-top:18px;"
                >
                    WEBSITE
                </span>


                <a
                    href="<?= e(
                        base_url(
                            'admin/pages.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-regular fa-file-lines"
                    ></i>

                    Halaman

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/menus.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-bars"
                    ></i>

                    Menu

                </a>


                <a
                    href="<?= e(
                        base_url(
                            'admin/settings.php'
                        )
                    ); ?>"
                    class="sidebar-link"
                >

                    <i
                        class="fa-solid fa-gear"
                    ></i>

                    Pengaturan

                </a>

                <a href="<?= e(base_url('admin/careers.php')); ?>" class="sidebar-link">
                    <i class="fa-solid fa-briefcase"></i>
                    Lowongan Karir
                </a>

                <a href="<?= e(base_url('admin/security.php')); ?>" class="sidebar-link">
                    <i class="fa-solid fa-shield-halved"></i>
                    Security
                </a>

                <a href="<?= e(base_url('admin/testimonials.php')); ?>" class="sidebar-link">
                    <i class="fa-solid fa-comment-dots"></i>
                    Testimoni
                </a>

            </nav>

        </div>


        <div
            class="sidebar-user"
        >

            <div
                class="sidebar-user-box"
            >

                <div
                    class="avatar"
                >

                    <?= e(
                        strtoupper(
                            mb_substr(
                                $adminName,
                                0,
                                1
                            )
                        )
                    ); ?>

                </div>


                <div
                    class="user-text"
                >

                    <strong>
                        <?= e(
                            $adminName
                        ); ?>
                    </strong>

                    <span>
                        <?= e(
                            $admin['role']
                            ??
                            'admin'
                        ); ?>
                    </span>

                </div>

            </div>

        </div>

    </aside>


    <!-- =================================================
         OVERLAY
    ================================================== -->

    <div
        class="overlay"
        id="sidebar-overlay"
    ></div>


    <!-- =================================================
         MAIN
    ================================================== -->

    <div
        class="main"
    >


        <!-- ===============================================
             TOPBAR
        ================================================ -->

        <header
            class="topbar"
        >

            <div
                class="topbar-left"
            >

                <button
                    type="button"
                    class="mobile-toggle"
                    id="mobile-toggle"
                    aria-label="Buka menu"
                >

                    <i
                        class="fa-solid fa-bars"
                    ></i>

                </button>


                <div
                    class="topbar-title"
                >

                    <span>
                        PANEL ADMINISTRATOR
                    </span>

                    <strong>
                        Manajemen Produk
                    </strong>

                </div>

            </div>


            <div
                class="topbar-right"
            >

                <a
                    href="<?= e(
                        base_url(
                            'index.php'
                        )
                    ); ?>"
                    class="view-site"
                    target="_blank"
                    rel="noopener noreferrer"
                >

                    <i
                        class="fa-solid fa-arrow-up-right-from-square"
                    ></i>

                    <span>
                        Lihat Website
                    </span>

                </a>


                <div
                    class="user-pill"
                >

                    <div
                        class="avatar"
                    >

                        <?= e(
                            strtoupper(
                                mb_substr(
                                    $adminName,
                                    0,
                                    1
                                )
                            )
                        ); ?>

                    </div>


                    <div
                        class="user-pill-name"
                    >

                        <strong>
                            <?= e(
                                $adminName
                            ); ?>
                        </strong>

                        <span>
                            Administrator
                        </span>

                    </div>


                    <a
                        href="<?= e(
                            base_url(
                                'admin/logout.php'
                            )
                        ); ?>"
                        class="logout"
                        title="Logout"
                    >

                        <i
                            class="fa-solid fa-right-from-bracket"
                        ></i>

                    </a>

                </div>

            </div>

        </header>


        <!-- ===============================================
             CONTENT
        ================================================ -->

        <main
            class="content"
        >


            <!-- =========================================
                 PAGE HEADER
            ========================================== -->

            <div
                class="page-header"
            >

                <div>

                    <div
                        class="page-kicker"
                    >
                        CATALOG MANAGEMENT
                    </div>


                    <h1
                        class="page-title"
                    >
                        <?= e(
                            $pageTitle
                        ); ?>
                    </h1>


                    <p
                        class="page-description"
                    >
                        Kelola katalog produk dan foto produk
                        yang tampil pada website.
                    </p>

                </div>


                <?php if (
                    !$isForm
                ): ?>

                    <a
                        href="<?= e(
                            base_url(
                                'admin/products.php?action=add'
                            )
                        ); ?>"
                        class="primary-button"
                    >

                        <i
                            class="fa-solid fa-plus"
                        ></i>

                        Tambah Produk

                    </a>

                <?php endif; ?>

            </div>


            <!-- =========================================
                 ALERT
            ========================================== -->

            <?php if (
                $flash
                &&
                !empty(
                    $flash['message']
                )
            ): ?>

                <div
                    class="alert <?= ($flash['type'] ?? '') === 'success'
                        ? 'alert-success'
                        : 'alert-error'; ?>"
                >

                    <i
                        class="fa-solid <?= ($flash['type'] ?? '') === 'success'
                            ? 'fa-circle-check'
                            : 'fa-circle-exclamation'; ?>"
                    ></i>

                    <span>
                        <?= e(
                            (string)
                            $flash['message']
                        ); ?>
                    </span>

                </div>

            <?php endif; ?>


            <?php if (
                $error !== ''
            ): ?>

                <div
                    class="alert alert-error"
                >

                    <i
                        class="fa-solid fa-circle-exclamation"
                    ></i>

                    <span>
                        <?= e(
                            $error
                        ); ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- =========================================
                 FORM
            ========================================== -->

            <?php if (
                $isForm
            ): ?>

                <section
                    class="form-panel"
                >

                    <h2
                        class="form-title"
                    >

                        <?= $editProduct
                            ? 'Edit Produk'
                            : 'Tambah Produk Baru'; ?>

                    </h2>


                    <form
                        action=""
                        method="POST"
                        enctype="multipart/form-data"
                        id="product-form"
                    >

                        <?= csrf_input(); ?>


                        <input
                            type="hidden"
                            name="form_action"
                            value="save"
                        >


                        <input
                            type="hidden"
                            name="id"
                            value="<?= e(
                                (string)
                                $formData['id']
                            ); ?>"
                        >


                        <div
                            class="form-grid"
                        >


                            <!-- =================================
                                 CATEGORY
                            ================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="category_id"
                                >

                                    Kategori Produk *

                                </label>


                                <select
                                    name="category_id"
                                    id="category_id"
                                    class="form-select"
                                >

                                    <option
                                        value="0"
                                    >
                                        -- Pilih Kategori --
                                    </option>


                                    <?php foreach (
                                        $categories
                                        as $category
                                    ): ?>

                                        <option
                                            value="<?= e(
                                                (string)
                                                $category['id']
                                            ); ?>"
                                            <?= (int)
                                                $formData['category_id']
                                                ===
                                                (int)
                                                $category['id']
                                                    ? 'selected'
                                                    : ''; ?>
                                        >

                                            <?= e(
                                                $category['name']
                                            ); ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>


                                <div
                                    class="help-text"
                                >
                                    Kategori digunakan untuk filter produk dan menu katalog.

                                </div>

                            </div>


                            <!-- =================================
                                 SORT
                            ================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="sort_order"
                                >

                                    Urutan Tampilan

                                </label>


                                <input
                                    type="number"
                                    name="sort_order"
                                    id="sort_order"
                                    class="form-control"
                                    min="0"
                                    max="9999"
                                    value="<?= e(
                                        (string)
                                        $formData['sort_order']
                                    ); ?>"
                                >


                                <div
                                    class="help-text"
                                >
                                    Angka lebih kecil akan ditampilkan lebih dahulu.

                                </div>

                            </div>


                            <!-- =================================
                                 NAME
                            ================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="name"
                                >

                                    Nama Produk *

                                </label>


                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="form-control"
                                    maxlength="200"
                                    value="<?= e(
                                        (string)
                                        $formData['name']
                                    ); ?>"
                                    placeholder="Contoh: FDR Sport XR EVO"
                                    required
                                >

                            </div>


                            <!-- =================================
                                 SLUG
                            ================================== -->

                            <div
                                class="form-group"
                            >

                                <label
                                    class="form-label"
                                    for="slug"
                                >

                                    Slug

                                    <span>
                                        (opsional, otomatis dibuat)
                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="slug"
                                    id="slug"
                                    class="form-control"
                                    maxlength="220"
                                    value="<?= e(
                                        (string)
                                        $formData['slug']
                                    ); ?>"
                                    placeholder="fdr-sport-xr-evo"
                                >


                                <div
                                    class="help-text"
                                >
                                    Slug digunakan untuk URL detail produk.

                                </div>

                            </div>


                            <!-- =================================
                                 SHORT DESCRIPTION
                            ================================== -->

                            <div
                                class="form-group full"
                            >

                                <label
                                    class="form-label"
                                    for="short_description"
                                >

                                    Deskripsi Singkat

                                </label>


                                <textarea
                                    name="short_description"
                                    id="short_description"
                                    class="form-textarea"
                                    maxlength="1000"
                                    placeholder="Deskripsi singkat yang muncul di card produk."
                                ><?= e(
                                    (string)
                                    $formData['short_description']
                                ); ?></textarea>

                            </div>


                            <!-- =================================
                                 DESCRIPTION
                            ================================== -->

                            <div
                                class="form-group full"
                            >

                                <label
                                    class="form-label"
                                    for="description"
                                >

                                    Deskripsi Produk

                                </label>


                                <div
                                    class="editor-note"
                                >

                                    Untuk saat ini field menerima teks HTML sederhana.
                                    Nantinya dapat dikembangkan menjadi rich text editor
                                    melalui panel admin.

                                </div>


                                <textarea
                                    name="description"
                                    id="description"
                                    class="form-textarea"
                                    style="min-height:180px;"
                                    placeholder="Tuliskan deskripsi lengkap produk..."
                                ><?= e(
                                    (string)
                                    $formData['description']
                                ); ?></textarea>

                            </div>


                            <!-- =================================
                                 SPECIFICATIONS
                            ================================== -->

                            <div
                                class="form-group full"
                            >

                                <label
                                    class="form-label"
                                    for="specifications"
                                >

                                    Spesifikasi Produk

                                </label>


                                <div
                                    class="editor-note"
                                >

                                    Contoh format:
                                    Ukuran: 90/80-17
                                    &lt;br&gt;
                                    Tipe: Tubeless

                                </div>


                                <textarea
                                    name="specifications"
                                    id="specifications"
                                    class="form-textarea"
                                    style="min-height:145px;"
                                    placeholder="Tuliskan spesifikasi produk..."
                                ><?= e(
                                    (string)
                                    $formData['specifications']
                                ); ?></textarea>

                            </div>


                            <!-- =================================
                                 PRODUCT URL
                            ================================== -->

                            <div
                                class="form-group full"
                            >

                                <label
                                    class="form-label"
                                    for="product_url"
                                >

                                    Link tujuan tombol Buy Now (Bengkelku)

                                    <span>
                                        (opsional)
                                    </span>

                                </label>


                                <input
                                    type="url"
                                    name="product_url"
                                    id="product_url"
                                    class="form-control"
                                    maxlength="255"
                                    value="<?= e(
                                        (string)
                                        $formData['product_url']
                                    ); ?>"
                                    placeholder="https://..."
                                >

                                <div
                                    class="help-text"
                                >
                                    Isi URL halaman detail produk di bengkelku.online. Tombol Buy Now pada detail ban akan menuju URL ini.

                                </div>

                            </div>


                            <!-- =================================
                                 SEO FIELDS
                            ================================== -->

                            <div class="form-group">
                                <label class="form-label" for="meta_title">SEO Title</label>
                                <input type="text" name="meta_title" id="meta_title" class="form-control" maxlength="255" value="<?= e((string)$formData['meta_title']); ?>" placeholder="Nama produk + manfaat utama | Star Parts Motor">
                                <div class="help-text">Kosongkan untuk memakai nama produk sebagai fallback.</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="meta_description">SEO Description</label>
                                <textarea name="meta_description" id="meta_description" class="form-textarea" maxlength="500" placeholder="Ringkasan unik produk untuk hasil pencarian."><?= e((string)$formData['meta_description']); ?></textarea>
                                <div class="help-text">Tulis ringkasan natural dan spesifik.</div>
                            </div>

                            <!-- =================================
                                 MAIN IMAGE
                            ================================== -->

                            <div
                                class="form-group full"
                            >

                                <label
                                    class="form-label"
                                >

                                    Foto Utama Produk

                                    <span>
                                        JPG / PNG / WEBP / GIF, maksimal 5 MB
                                    </span>

                                </label>


                                <div
                                    class="media-box"
                                >

                                    <input
                                        type="file"
                                        name="image"
                                        id="image"
                                        accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                    >


                                    <?php if (
                                        $formImageUrl !== ''
                                    ): ?>

                                        <div
                                            class="current-image"
                                        >

                                            <img
                                                src="<?= e(
                                                    $formImageUrl
                                                ); ?>"
                                                alt="<?= e(
                                                    (string)
                                                    $formData['name']
                                                ); ?>"
                                            >


                                            <div
                                                class="current-image-name"
                                            >

                                                <?= e(
                                                    basename(
                                                        (string)
                                                        $formData['image']
                                                    )
                                                ); ?>

                                            </div>

                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div
                                    class="help-text"
                                >
                                    Gunakan foto resolusi tinggi dengan background yang bersih agar tampil lebih profesional di katalog.

                                </div>

                                <div style="margin-top:12px;">
                                    <label class="form-label" for="image_alt">ALT Text Foto Utama</label>
                                    <input type="text" name="image_alt" id="image_alt" class="form-control" maxlength="255" value="<?= e((string)$formData['image_alt']); ?>" placeholder="Ban motor FDR Sport XR EVO tampak samping">
                                    <div class="help-text">Jelaskan isi gambar secara natural.</div>
                                </div>

                            </div>


                            <!-- =================================
                                 GALLERY
                            ================================== -->

                            <div
                                class="form-group full"
                            >

                                <label
                                    class="form-label"
                                >

                                    Gallery Produk

                                    <span>
                                        maksimal 20 foto
                                    </span>

                                </label>


                                <div
                                    class="media-box"
                                >

                                    <input
                                        type="file"
                                        name="gallery[]"
                                        id="gallery"
                                        accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                        multiple
                                    >


                                    <div
                                        class="help-text"
                                    >
                                        Pilih beberapa foto sekaligus untuk galeri produk.

                                    </div>


                                    <?php if (
                                        !empty(
                                            $formData['gallery']
                                        )
                                    ): ?>

                                        <div
                                            class="gallery-current"
                                        >

                                            <?php foreach (
                                                $formData['gallery']
                                                as $galleryItem
                                            ): ?>

                                                <div
                                                    class="gallery-item"
                                                >

                                                    <img
                                                        src="<?= e(
                                                            media_url(
                                                                $galleryItem
                                                            )
                                                        ); ?>"
                                                        alt="Gallery"
                                                        loading="lazy"
                                                    >


                                                    <label
                                                        class="gallery-remove"
                                                        title="Hapus foto"
                                                    >

                                                        <input
                                                            type="checkbox"
                                                            name="remove_gallery[]"
                                                            value="<?= e(
                                                                $galleryItem
                                                            ); ?>"
                                                        >

                                                        Hapus

                                                    </label>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <!-- =================================
                                 OPTIONS
                            ================================== -->

                            <div
                                class="form-group full"
                            >

                                <div
                                    class="options"
                                >

                                    <label
                                        class="check-label"
                                    >

                                        <input
                                            type="checkbox"
                                            name="is_featured"
                                            value="1"
                                            <?= (int)
                                                $formData['is_featured']
                                                ===
                                                1
                                                    ? 'checked'
                                                    : ''; ?>
                                        >

                                        Produk Unggulan

                                    </label>


                                    <label
                                        class="check-label"
                                    >

                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            <?= (int)
                                                $formData['is_active']
                                                ===
                                                1
                                                    ? 'checked'
                                                    : ''; ?>
                                        >

                                        Produk Aktif

                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- =================================
                             FORM ACTION
                        ================================== -->

                        <div
                            class="form-actions"
                        >

                            <a
                                href="<?= e(
                                    $listUrl
                                ); ?>"
                                class="secondary-button"
                            >

                                <i
                                    class="fa-solid fa-xmark"
                                ></i>

                                Batal

                            </a>


                            <button
                                type="submit"
                                class="primary-button"
                                id="save-product-button"
                            >

                                <i
                                    class="fa-solid fa-floppy-disk"
                                ></i>

                                Simpan Produk

                            </button>

                        </div>

                    </form>

                </section>

            <?php endif; ?>


            <!-- =========================================
                 MINI STATISTICS
            ========================================== -->

            <?php if (
                !$isForm
            ): ?>

                <section
                    class="mini-stats"
                >

                    <article
                        class="mini-stat"
                    >

                        <div
                            class="mini-stat-top"
                        >

                            <span
                                class="mini-stat-icon"
                            >

                                <i
                                    class="fa-solid fa-box-open"
                                ></i>

                            </span>

                        </div>


                        <strong>
                            <?= e(
                                (string)
                                $totalProducts
                            ); ?>
                        </strong>

                        <span>
                            Total Produk
                        </span>

                    </article>


                    <article
                        class="mini-stat"
                    >

                        <div
                            class="mini-stat-top"
                        >

                            <span
                                class="mini-stat-icon green"
                            >

                                <i
                                    class="fa-solid fa-circle-check"
                                ></i>

                            </span>

                        </div>


                        <strong>
                            <?= e(
                                (string)
                                $activeProducts
                            ); ?>
                        </strong>

                        <span>
                            Produk Aktif
                        </span>

                    </article>


                    <article
                        class="mini-stat"
                    >

                        <div
                            class="mini-stat-top"
                        >

                            <span
                                class="mini-stat-icon blue"
                            >

                                <i
                                    class="fa-solid fa-star"
                                ></i>

                            </span>

                        </div>


                        <strong>
                            <?= e(
                                (string)
                                $featuredProducts
                            ); ?>
                        </strong>

                        <span>
                            Produk Unggulan
                        </span>

                    </article>


                    <article
                        class="mini-stat"
                    >

                        <div
                            class="mini-stat-top"
                        >

                            <span
                                class="mini-stat-icon black"
                            >

                                <i
                                    class="fa-solid fa-layer-group"
                                ></i>

                            </span>

                        </div>


                        <strong>
                            <?= e(
                                (string)
                                count(
                                    $categories
                                )
                            ); ?>
                        </strong>

                        <span>
                            Kategori Aktif
                        </span>

                    </article>

                </section>

            <?php endif; ?>


            <!-- =========================================
                 FILTER
            ========================================== -->

            <?php if (
                !$isForm
            ): ?>

                <section
                    class="filter-panel"
                >

                    <form
                        method="GET"
                        action=""
                        class="filter-form"
                    >

                        <input
                            type="text"
                            name="search"
                            class="filter-input"
                            value="<?= e(
                                $search
                            ); ?>"
                            placeholder="Cari nama produk, slug..."
                        >


                        <select
                            name="category_id"
                            class="filter-select"
                        >

                            <option
                                value="0"
                            >
                                Semua Kategori
                            </option>


                            <?php foreach (
                                $categories
                                as $category
                            ): ?>

                                <option
                                    value="<?= e(
                                        (string)
                                        $category['id']
                                    ); ?>"
                                    <?= $categoryFilter
                                        ===
                                        (int)
                                        $category['id']
                                            ? 'selected'
                                            : ''; ?>
                                >

                                    <?= e(
                                        $category['name']
                                    ); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>


                        <select
                            name="status"
                            class="filter-select"
                        >

                            <option
                                value="all"
                                <?= $statusFilter === 'all'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Semua Status
                            </option>

                            <option
                                value="active"
                                <?= $statusFilter === 'active'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Aktif
                            </option>

                            <option
                                value="inactive"
                                <?= $statusFilter === 'inactive'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Nonaktif
                            </option>

                        </select>


                        <select
                            name="featured"
                            class="filter-select"
                        >

                            <option
                                value="all"
                                <?= $featuredFilter === 'all'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Semua Produk
                            </option>

                            <option
                                value="yes"
                                <?= $featuredFilter === 'yes'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Unggulan
                            </option>

                            <option
                                value="no"
                                <?= $featuredFilter === 'no'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Biasa
                            </option>

                        </select>


                        <button
                            type="submit"
                            class="filter-button"
                        >

                            <i
                                class="fa-solid fa-magnifying-glass"
                            ></i>

                            Filter

                        </button>

                    </form>

                </section>

            <?php endif; ?>


            <!-- =========================================
                 PRODUCT LIST
            ========================================== -->

            <?php if (
                !$isForm
            ): ?>

                <section
                    class="panel"
                >

                    <div
                        class="panel-heading"
                    >

                        <div
                            class="panel-heading-left"
                        >

                            <h2>
                                Daftar Produk
                            </h2>

                            <p>
                                Produk yang tersedia di katalog Star Parts Motor.
                            </p>

                        </div>


                        <span
                            class="panel-count"
                        >

                            <?= e(
                                (string)
                                count(
                                    $products
                                )
                            ); ?>
                            hasil

                        </span>

                    </div>


                    <?php if (
                        !empty(
                            $products
                        )
                    ): ?>

                        <div
                            class="table-wrap"
                        >

                            <table
                                class="product-table"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            Produk
                                        </th>

                                        <th>
                                            Kategori
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Featured
                                        </th>

                                        <th>
                                            Urutan
                                        </th>

                                        <th>
                                            Dibuat
                                        </th>

                                        <th>
                                            Aksi
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $products
                                        as $product
                                    ): ?>

                                        <?php

                                        $productId =
                                            (int)
                                            (
                                                $product['id']
                                                ??
                                                0
                                            );

                                        $productName =
                                            trim(
                                                (string)
                                                (
                                                    $product['name']
                                                    ??
                                                    ''
                                                )
                                            );

                                        $categoryName =
                                            trim(
                                                (string)
                                                (
                                                    $product['category_name']
                                                    ??
                                                    '-'
                                                )
                                            );

                                        $productImage =
                                            trim(
                                                (string)
                                                (
                                                    $product['image']
                                                    ??
                                                    ''
                                                )
                                            );

                                        $productImageUrl =
                                            $productImage !== ''
                                                ? media_url(
                                                    $productImage
                                                )
                                                : '';

                                        $active =
                                            (int)
                                            (
                                                $product['is_active']
                                                ??
                                                0
                                            )
                                            ===
                                            1;

                                        $featured =
                                            (int)
                                            (
                                                $product['is_featured']
                                                ??
                                                0
                                            )
                                            ===
                                            1;

                                        ?>

                                        <tr>

                                            <td>

                                                <div
                                                    class="product-cell"
                                                >

                                                    <div
                                                        class="product-thumb"
                                                    >

                                                        <?php if (
                                                            $productImageUrl
                                                            !==
                                                            ''
                                                        ): ?>

                                                            <img
                                                                src="<?= e(
                                                                    $productImageUrl
                                                                ); ?>"
                                                                alt="<?= e(
                                                                    $productName
                                                                ); ?>"
                                                                loading="lazy"
                                                            >

                                                        <?php else: ?>

                                                            <i
                                                                class="fa-solid fa-box-open"
                                                            ></i>

                                                        <?php endif; ?>

                                                    </div>


                                                    <div
                                                        class="product-cell-text"
                                                    >

                                                        <strong>
                                                            <?= e(
                                                                $productName
                                                            ); ?>
                                                        </strong>

                                                        <span>
                                                            ID #<?= e(
                                                                (string)
                                                                $productId
                                                            ); ?>
                                                            &nbsp;•&nbsp;
                                                            <?= e(
                                                                (string)
                                                                (
                                                                    $product['slug']
                                                                    ??
                                                                    ''
                                                                )
                                                            ); ?>
                                                        </span>

                                                    </div>

                                                </div>

                                            </td>


                                            <td>

                                                <span
                                                    class="category-badge"
                                                >

                                                    <?= e(
                                                        $categoryName !== ''
                                                            ? $categoryName
                                                            : 'Tanpa kategori'
                                                    ); ?>

                                                </span>

                                            </td>


                                            <td>

                                                <span
                                                    class="badge <?= $active
                                                        ? 'badge-active'
                                                        : 'badge-inactive'; ?>"
                                                >

                                                    <i
                                                        class="fa-solid fa-circle"
                                                        style="font-size:4px;"
                                                    ></i>

                                                    <?= $active
                                                        ? 'Aktif'
                                                        : 'Nonaktif'; ?>

                                                </span>

                                            </td>


                                            <td>

                                                <span
                                                    class="badge <?= $featured
                                                        ? 'badge-featured'
                                                        : 'badge-normal'; ?>"
                                                >

                                                    <i
                                                        class="fa-solid fa-star"
                                                    ></i>

                                                    <?= $featured
                                                        ? 'Ya'
                                                        : 'Tidak'; ?>

                                                </span>

                                            </td>


                                            <td>

                                                <?= e(
                                                    (string)
                                                    (
                                                        $product['sort_order']
                                                        ??
                                                        0
                                                    )
                                                ); ?>

                                            </td>


                                            <td>

                                                <?= e(
                                                    format_date_id(
                                                        $product['created_at']
                                                        ??
                                                        ''
                                                    )
                                                ); ?>

                                            </td>


                                            <td>

                                                <div
                                                    class="table-actions"
                                                >

                                                    <!-- EDIT -->

                                                    <a
                                                        href="<?= e(
                                                            base_url(
                                                                'admin/products.php?action=edit&id='
                                                                .
                                                                $productId
                                                            )
                                                        ); ?>"
                                                        class="icon-button"
                                                        title="Edit"
                                                    >

                                                        <i
                                                            class="fa-solid fa-pen"
                                                        ></i>

                                                    </a>


                                                    <!-- FEATURED -->

                                                    <form
                                                        method="POST"
                                                        action=""
                                                        class="inline-form"
                                                    >

                                                        <?= csrf_input(); ?>


                                                        <input
                                                            type="hidden"
                                                            name="form_action"
                                                            value="featured"
                                                        >


                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= e(
                                                                (string)
                                                                $productId
                                                            ); ?>"
                                                        >


                                                        <button
                                                            type="submit"
                                                            class="icon-button featured"
                                                            title="<?= $featured
                                                                ? 'Hapus dari unggulan'
                                                                : 'Jadikan unggulan'; ?>"
                                                        >

                                                            <i
                                                                class="fa-<?= $featured
                                                                    ? 'solid'
                                                                    : 'regular'; ?> fa-star"
                                                            ></i>

                                                        </button>

                                                    </form>


                                                    <!-- TOGGLE -->

                                                    <form
                                                        method="POST"
                                                        action=""
                                                        class="inline-form"
                                                    >

                                                        <?= csrf_input(); ?>


                                                        <input
                                                            type="hidden"
                                                            name="form_action"
                                                            value="toggle"
                                                        >


                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= e(
                                                                (string)
                                                                $productId
                                                            ); ?>"
                                                        >


                                                        <button
                                                            type="submit"
                                                            class="icon-button"
                                                            title="<?= $active
                                                                ? 'Nonaktifkan'
                                                                : 'Aktifkan'; ?>"
                                                        >

                                                            <i
                                                                class="fa-solid <?= $active
                                                                    ? 'fa-eye-slash'
                                                                    : 'fa-eye'; ?>"
                                                            ></i>

                                                        </button>

                                                    </form>


                                                    <!-- DELETE -->

                                                    <form
                                                        method="POST"
                                                        action=""
                                                        class="inline-form delete-form"
                                                    >

                                                        <?= csrf_input(); ?>


                                                        <input
                                                            type="hidden"
                                                            name="form_action"
                                                            value="delete"
                                                        >


                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= e(
                                                                (string)
                                                                $productId
                                                            ); ?>"
                                                        >


                                                        <button
                                                            type="submit"
                                                            class="icon-button danger"
                                                            title="Hapus"
                                                        >

                                                            <i
                                                                class="fa-solid fa-trash"
                                                            ></i>

                                                        </button>

                                                    </form>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div
                            class="empty"
                        >

                            <i
                                class="fa-solid fa-box-open"
                            ></i>

                            <strong>
                                Belum ada produk
                            </strong>

                            <span>
                                Tambahkan produk melalui tombol
                                "Tambah Produk".
                            </span>

                        </div>

                    <?php endif; ?>

                </section>

            <?php endif; ?>


            <!-- =========================================
                 FOOTER
            ========================================== -->

            <div
                style="
                    padding:18px 0;
                    text-align:center;
                    color:#a0a0a0;
                    font-size:8px;
                "
            >

                <?= e(
                    $siteName
                ); ?>

                &nbsp;•&nbsp;

                Product Management

                &nbsp;•&nbsp;

                <?= e(
                    date('Y')
                ); ?>

            </div>

        </main>

    </div>

</div>


<!-- =======================================================
     JAVASCRIPT
======================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        /*
         * ================================================
         * SIDEBAR MOBILE
         * ================================================
         */

        const sidebar =
            document.getElementById(
                "sidebar"
            );

        const toggle =
            document.getElementById(
                "mobile-toggle"
            );

        const overlay =
            document.getElementById(
                "sidebar-overlay"
            );


        function openSidebar() {

            if (!sidebar) {
                return;
            }

            sidebar.classList.add(
                "open"
            );

            if (overlay) {

                overlay.classList.add(
                    "show"
                );

            }

            document.body.style.overflow =
                "hidden";
        }


        function closeSidebar() {

            if (!sidebar) {
                return;
            }

            sidebar.classList.remove(
                "open"
            );

            if (overlay) {

                overlay.classList.remove(
                    "show"
                );

            }

            document.body.style.overflow =
                "";
        }


        if (
            toggle
        ) {

            toggle.addEventListener(
                "click",
                function () {

                    if (
                        sidebar.classList.contains(
                            "open"
                        )
                    ) {

                        closeSidebar();

                    } else {

                        openSidebar();

                    }

                }
            );

        }


        if (
            overlay
        ) {

            overlay.addEventListener(
                "click",
                closeSidebar
            );

        }


        /*
         * ================================================
         * ESC
         * ================================================
         */

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape"
                ) {

                    closeSidebar();

                }

            }
        );


        /*
         * ================================================
         * DELETE CONFIRMATION
         * ================================================
         */

        document
            .querySelectorAll(
                ".delete-form"
            )
            .forEach(
                function (form) {

                    form.addEventListener(
                        "submit",
                        function (event) {

                            const confirmed =
                                window.confirm(
                                    "Yakin ingin menghapus produk ini? Foto utama dan gallery lokal yang terkait juga akan dihapus."
                                );


                            if (
                                !confirmed
                            ) {

                                event.preventDefault();

                            }

                        }
                    );

                }
            );


        /*
         * ================================================
         * FORM DOUBLE SUBMIT
         * ================================================
         */

        const productForm =
            document.getElementById(
                "product-form"
            );

        const saveButton =
            document.getElementById(
                "save-product-button"
            );


        if (
            productForm
            &&
            saveButton
        ) {

            productForm.addEventListener(
                "submit",
                function () {

                    saveButton.disabled =
                        true;

                    saveButton.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

                }
            );

        }


        /*
         * ================================================
         * AUTO SLUG
         * ================================================
         */

        const nameInput =
            document.getElementById(
                "name"
            );

        const slugInput =
            document.getElementById(
                "slug"
            );


        if (
            nameInput
            &&
            slugInput
        ) {

            let manuallyEdited =
                slugInput.value.trim()
                !==
                "";


            slugInput.addEventListener(
                "input",
                function () {

                    manuallyEdited =
                        true;

                }
            );


            nameInput.addEventListener(
                "input",
                function () {

                    if (
                        manuallyEdited
                    ) {

                        return;

                    }


                    const value =
                        nameInput.value
                            .toLowerCase()
                            .normalize(
                                "NFD"
                            )
                            .replace(
                                /[\u0300-\u036f]/g,
                                ""
                            )
                            .replace(
                                /[^a-z0-9]+/g,
                                "-"
                            )
                            .replace(
                                /^-+|-+$/g,
                                ""
                            );


                    slugInput.value =
                        value;

                }
            );

        }


        /*
         * ================================================
         * IMAGE SIZE VALIDATION
         * ================================================
         */

        const imageInput =
            document.getElementById(
                "image"
            );


        if (
            imageInput
        ) {

            imageInput.addEventListener(
                "change",
                function () {

                    if (
                        !this.files
                        ||
                        !this.files[0]
                    ) {

                        return;

                    }


                    const max =
                        5 *
                        1024 *
                        1024;


                    if (
                        this.files[0].size
                        >
                        max
                    ) {

                        alert(
                            "Ukuran foto utama maksimal 5 MB."
                        );

                        this.value =
                            "";

                    }

                }
            );

        }


        /*
         * ================================================
         * GALLERY COUNT VALIDATION
         * ================================================
         */

        const galleryInput =
            document.getElementById(
                "gallery"
            );


        if (
            galleryInput
        ) {

            galleryInput.addEventListener(
                "change",
                function () {

                    const maxFiles =
                        20;


                    if (
                        this.files.length
                        >
                        maxFiles
                    ) {

                        alert(
                            "Maksimal 20 foto gallery dalam satu produk."
                        );


                        this.value =
                            "";

                        return;

                    }


                    const maxSize =
                        5 *
                        1024 *
                        1024;


                    for (
                        let i = 0;
                        i < this.files.length;
                        i++
                    ) {

                        if (
                            this.files[i].size
                            >
                            maxSize
                        ) {

                            alert(
                                "Setiap foto gallery maksimal 5 MB."
                            );


                            this.value =
                                "";

                            return;

                        }

                    }

                }
            );

        }


        /*
         * ================================================
         * AUTO HIDE ALERT
         * ================================================
         */

        setTimeout(
            function () {

                document
                    .querySelectorAll(
                        ".alert"
                    )
                    .forEach(
                        function (
                            alertElement
                        ) {

                            alertElement.style.transition =
                                "opacity .4s ease";

                            alertElement.style.opacity =
                                "0";


                            setTimeout(
                                function () {

                                    if (
                                        alertElement.parentNode
                                    ) {

                                        alertElement.parentNode
                                            .removeChild(
                                                alertElement
                                            );

                                    }

                                },
                                450
                            );

                        }
                    );

            },
            5000
        );

    }
);

</script>


</body>
</html>
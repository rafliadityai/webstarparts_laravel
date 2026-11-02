<?php
// admin/locations.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Lokasi';
$admin     = current_admin();

/*
|--------------------------------------------------------------------------
| Helper: deteksi struktur tabel
|--------------------------------------------------------------------------
*/

function location_table_columns(): array
{
    static $columns = null;

    if ($columns !== null) {
        return $columns;
    }

    global $pdo;

    $columns = [];

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM locations");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }
    } catch (Throwable $e) {
        $columns = [];
    }

    return $columns;
}

function location_has_column(string $column): bool
{
    return in_array(
        $column,
        location_table_columns(),
        true
    );
}

function location_column(
    array $candidates
): ?string {
    $columns = location_table_columns();

    foreach ($candidates as $column) {
        if (in_array($column, $columns, true)) {
            return $column;
        }
    }

    return null;
}

function location_id_column(): ?string
{
    return location_column([
        'id',
        'location_id'
    ]);
}

function location_name_column(): ?string
{
    return location_column([
        'name',
        'location_name',
        'title',
        'nama'
    ]);
}

function location_type_column(): ?string
{
    return location_column([
        'type',
        'location_type',
        'category',
        'jenis'
    ]);
}

function location_address_column(): ?string
{
    return location_column([
        'address',
        'alamat'
    ]);
}

function location_city_column(): ?string
{
    return location_column([
        'city',
        'kota',
        'kabupaten'
    ]);
}

function location_province_column(): ?string
{
    return location_column([
        'province',
        'provinsi'
    ]);
}

function location_postal_column(): ?string
{
    return location_column([
        'postal_code',
        'postcode',
        'zip_code',
        'kode_pos'
    ]);
}

function location_phone_column(): ?string
{
    return location_column([
        'phone',
        'phone_number',
        'telephone',
        'telp'
    ]);
}

function location_email_column(): ?string
{
    return location_column([
        'email',
        'email_address'
    ]);
}

function location_latitude_column(): ?string
{
    return location_column([
        'latitude',
        'lat'
    ]);
}

function location_longitude_column(): ?string
{
    return location_column([
        'longitude',
        'lng',
        'long'
    ]);
}

function location_description_column(): ?string
{
    return location_column([
        'description',
        'content',
        'deskripsi'
    ]);
}

function location_map_url_column(): ?string
{
    return location_column([
        'map_url',
        'google_maps_url',
        'maps_url',
        'map_link'
    ]);
}

function location_sort_column(): ?string
{
    return location_column([
        'sort_order',
        'display_order',
        'urutan'
    ]);
}

function location_active_column(): ?string
{
    return location_column([
        'is_active',
        'active',
        'status'
    ]);
}

function location_created_column(): ?string
{
    return location_column([
        'created_at',
        'created_on',
        'date_created'
    ]);
}

function location_updated_column(): ?string
{
    return location_column([
        'updated_at',
        'modified_at',
        'updated_on'
    ]);
}

function location_identifier(
    string $column
): string {
    return '`' .
        str_replace(
            '`',
            '``',
            $column
        ) .
        '`';
}

/*
|--------------------------------------------------------------------------
| Deteksi kolom
|--------------------------------------------------------------------------
*/

$locationIdColumn =
    location_id_column();

$locationNameColumn =
    location_name_column();

$locationTypeColumn =
    location_type_column();

$locationAddressColumn =
    location_address_column();

$locationCityColumn =
    location_city_column();

$locationProvinceColumn =
    location_province_column();

$locationPostalColumn =
    location_postal_column();

$locationPhoneColumn =
    location_phone_column();

$locationEmailColumn =
    location_email_column();

$locationLatitudeColumn =
    location_latitude_column();

$locationLongitudeColumn =
    location_longitude_column();

$locationDescriptionColumn =
    location_description_column();

$locationMapUrlColumn =
    location_map_url_column();

$locationSortColumn =
    location_sort_column();

$locationActiveColumn =
    location_active_column();

$locationCreatedColumn =
    location_created_column();

$locationUpdatedColumn =
    location_updated_column();

/*
|--------------------------------------------------------------------------
| Validasi minimum
|--------------------------------------------------------------------------
*/

if (!$locationIdColumn) {
    die(
        'Tabel locations tidak memiliki kolom ID yang dapat digunakan.'
    );
}

if (!$locationNameColumn) {
    die(
        'Tabel locations tidak memiliki kolom nama lokasi.'
    );
}

/*
|--------------------------------------------------------------------------
| POST ACTION
|--------------------------------------------------------------------------
*/

if (is_post()) {

    /*
     * CSRF wajib valid sebelum action POST diproses.
     */
    if (!verify_csrf()) {

        set_flash(
            'error',
            'Permintaan tidak valid. Silakan refresh halaman dan coba kembali.'
        );

        redirect(
            BASE_URL .
            '/admin/locations.php'
        );
    }

    $action = post_string(
        'action'
    );

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    if ($action === 'save') {

        $id =
            get_int('id');

        $name =
            trim(
                post_string('name')
            );

        $type =
            trim(
                post_string('type')
            );

        $address =
            trim(
                post_string('address')
            );

        $city =
            trim(
                post_string('city')
            );

        $province =
            trim(
                post_string('province')
            );

        $postalCode =
            trim(
                post_string('postal_code')
            );

        $phone =
            trim(
                post_string('phone')
            );

        $email =
            trim(
                post_string('email')
            );

        $latitude =
            trim(
                post_string('latitude')
            );

        $longitude =
            trim(
                post_string('longitude')
            );

        $mapUrl =
            trim(
                post_string('map_url')
            );

        $description =
            trim(
                post_string('description')
            );

        $sortOrder =
            get_int('sort_order');

        $isActive =
            isset(
                $_POST['is_active']
            )
            ? 1
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Validasi
        |--------------------------------------------------------------------------
        */

        if ($name === '') {

            set_flash(
                'error',
                'Nama lokasi wajib diisi.'
            );

            redirect(
                BASE_URL .
                '/admin/locations.php' .
                ($id
                    ? '?edit=' . $id
                    : '') .
                '#location-form'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Latitude
        |--------------------------------------------------------------------------
        */

        if ($latitude !== '') {

            if (
                !is_numeric(
                    $latitude
                ) ||
                (float) $latitude < -90 ||
                (float) $latitude > 90
            ) {

                set_flash(
                    'error',
                    'Latitude harus berada antara -90 sampai 90.'
                );

                redirect(
                    BASE_URL .
                    '/admin/locations.php' .
                    ($id
                        ? '?edit=' . $id
                        : '') .
                    '#location-form'
                );
            }

            $latitude =
                (string) (
                    (float) $latitude
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Longitude
        |--------------------------------------------------------------------------
        */

        if ($longitude !== '') {

            if (
                !is_numeric(
                    $longitude
                ) ||
                (float) $longitude < -180 ||
                (float) $longitude > 180
            ) {

                set_flash(
                    'error',
                    'Longitude harus berada antara -180 sampai 180.'
                );

                redirect(
                    BASE_URL .
                    '/admin/locations.php' .
                    ($id
                        ? '?edit=' . $id
                        : '') .
                    '#location-form'
                );
            }

            $longitude =
                (string) (
                    (float) $longitude
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Data
        |--------------------------------------------------------------------------
        */

        $oldLocation = null;

        if ($id > 0) {

            $stmtOld = $pdo->prepare("
                SELECT *
                FROM locations
                WHERE " .
                location_identifier(
                    $locationIdColumn
                ) .
                " = ?
                LIMIT 1
            ");

            $stmtOld->execute([
                $id
            ]);

            $oldLocation =
                $stmtOld->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$oldLocation) {

                set_flash(
                    'error',
                    'Data lokasi tidak ditemukan.'
                );

                redirect(
                    BASE_URL .
                    '/admin/locations.php'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Build UPDATE / INSERT dinamically
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();

            $data = [];

            $data[$locationNameColumn] =
                $name;

            if ($locationTypeColumn) {
                $data[$locationTypeColumn] =
                    $type !== ''
                        ? $type
                        : null;
            }

            if ($locationAddressColumn) {
                $data[$locationAddressColumn] =
                    $address !== ''
                        ? $address
                        : null;
            }

            if ($locationCityColumn) {
                $data[$locationCityColumn] =
                    $city !== ''
                        ? $city
                        : null;
            }

            if ($locationProvinceColumn) {
                $data[$locationProvinceColumn] =
                    $province !== ''
                        ? $province
                        : null;
            }

            if ($locationPostalColumn) {
                $data[$locationPostalColumn] =
                    $postalCode !== ''
                        ? $postalCode
                        : null;
            }

            if ($locationPhoneColumn) {
                $data[$locationPhoneColumn] =
                    $phone !== ''
                        ? $phone
                        : null;
            }

            if ($locationEmailColumn) {
                $data[$locationEmailColumn] =
                    $email !== ''
                        ? $email
                        : null;
            }

            if ($locationLatitudeColumn) {
                $data[$locationLatitudeColumn] =
                    $latitude !== ''
                        ? $latitude
                        : null;
            }

            if ($locationLongitudeColumn) {
                $data[$locationLongitudeColumn] =
                    $longitude !== ''
                        ? $longitude
                        : null;
            }

            if ($locationMapUrlColumn) {
                $data[$locationMapUrlColumn] =
                    $mapUrl !== ''
                        ? $mapUrl
                        : null;
            }

            if ($locationDescriptionColumn) {
                $data[$locationDescriptionColumn] =
                    $description !== ''
                        ? $description
                        : null;
            }

            if ($locationSortColumn) {
                $data[$locationSortColumn] =
                    $sortOrder;
            }

            if ($locationActiveColumn) {
                $data[$locationActiveColumn] =
                    $isActive;
            }

            if ($id > 0) {

                $setParts = [];
                $params   = [];

                foreach ($data as $column => $value) {

                    $setParts[] =
                        location_identifier(
                            $column
                        ) .
                        ' = ?';

                    $params[] =
                        $value;
                }

                if ($locationUpdatedColumn) {

                    $setParts[] =
                        location_identifier(
                            $locationUpdatedColumn
                        ) .
                        ' = NOW()';
                }

                $params[] =
                    $id;

                $sql = "
                    UPDATE locations
                    SET " .
                    implode(
                        ', ',
                        $setParts
                    ) .
                    "
                    WHERE " .
                    location_identifier(
                        $locationIdColumn
                    ) .
                    " = ?
                ";

                $stmt =
                    $pdo->prepare(
                        $sql
                    );

                $stmt->execute(
                    $params
                );

                $pdo->commit();

                set_flash(
                    'success',
                    'Lokasi berhasil diperbarui.'
                );

            } else {

                $columns = [];
                $values  = [];
                $params  = [];

                foreach ($data as $column => $value) {

                    $columns[] =
                        location_identifier(
                            $column
                        );

                    $values[] =
                        '?';

                    $params[] =
                        $value;
                }

                if ($locationCreatedColumn) {

                    $columns[] =
                        location_identifier(
                            $locationCreatedColumn
                        );

                    $values[] =
                        'NOW()';
                }

                if ($locationUpdatedColumn) {

                    $columns[] =
                        location_identifier(
                            $locationUpdatedColumn
                        );

                    $values[] =
                        'NOW()';
                }

                $sql = "
                    INSERT INTO locations
                    (" .
                    implode(
                        ', ',
                        $columns
                    ) .
                    ")
                    VALUES
                    (" .
                    implode(
                        ', ',
                        $values
                    ) .
                    ")
                ";

                $stmt =
                    $pdo->prepare(
                        $sql
                    );

                $stmt->execute(
                    $params
                );

                $pdo->commit();

                set_flash(
                    'success',
                    'Lokasi berhasil ditambahkan.'
                );
            }

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            set_flash(
                'error',
                'Terjadi kesalahan saat menyimpan lokasi.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/locations.php'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id =
            get_int('id');

        if ($id <= 0) {

            set_flash(
                'error',
                'ID lokasi tidak valid.'
            );

            redirect(
                BASE_URL .
                '/admin/locations.php'
            );
        }

        try {

            $stmt =
                $pdo->prepare("
                    DELETE FROM locations
                    WHERE " .
                    location_identifier(
                        $locationIdColumn
                    ) .
                    " = ?
                ");

            $stmt->execute([
                $id
            ]);

            set_flash(
                'success',
                'Lokasi berhasil dihapus.'
            );

        } catch (Throwable $e) {

            set_flash(
                'error',
                'Lokasi gagal dihapus.'
            );
        }

        redirect(
            BASE_URL .
            '/admin/locations.php'
        );
    }
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim(
        get_string('q')
    );

$typeFilter =
    trim(
        get_string('type')
    );

$statusFilter =
    trim(
        get_string('status')
    );

$editId =
    get_int('edit');

/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$where = [
    '1=1'
];

$params = [];

if ($search !== '') {

    $searchConditions = [];

    $searchColumns = [
        $locationNameColumn,
        $locationTypeColumn,
        $locationAddressColumn,
        $locationCityColumn,
        $locationProvinceColumn,
        $locationPhoneColumn,
        $locationEmailColumn
    ];

    foreach ($searchColumns as $column) {

        if (!$column) {
            continue;
        }

        $searchConditions[] =
            location_identifier(
                $column
            ) .
            ' LIKE ?';

        $params[] =
            '%' .
            $search .
            '%';
    }

    if ($searchConditions) {

        $where[] =
            '(' .
            implode(
                ' OR ',
                $searchConditions
            ) .
            ')';
    }
}

/*
|--------------------------------------------------------------------------
| TYPE FILTER
|--------------------------------------------------------------------------
*/

if (
    $locationTypeColumn &&
    $typeFilter !== ''
) {

    $where[] =
        location_identifier(
            $locationTypeColumn
        ) .
        ' = ?';

    $params[] =
        $typeFilter;
}

/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if (
    $locationActiveColumn &&
    $statusFilter === 'active'
) {

    $where[] =
        location_identifier(
            $locationActiveColumn
        ) .
        ' = 1';

} elseif (
    $locationActiveColumn &&
    $statusFilter === 'inactive'
) {

    $where[] =
        location_identifier(
            $locationActiveColumn
        ) .
        ' = 0';
}

/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

if ($locationSortColumn) {

    $orderBy =
        location_identifier(
            $locationSortColumn
        ) .
        ' ASC';

} else {

    $orderBy =
        location_identifier(
            $locationNameColumn
        ) .
        ' ASC';
}

if ($locationIdColumn) {

    $orderBy .=
        ', ' .
        location_identifier(
            $locationIdColumn
        ) .
        ' DESC';
}

$sql = "
    SELECT *
    FROM locations
    WHERE " .
    implode(
        ' AND ',
        $where
    ) .
    "
    ORDER BY " .
    $orderBy;

$stmt =
    $pdo->prepare(
        $sql
    );

$stmt->execute(
    $params
);

$locations =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| TYPE LIST
|--------------------------------------------------------------------------
*/

$locationTypes = [];

if ($locationTypeColumn) {

    try {

        $stmtTypes =
            $pdo->query("
                SELECT DISTINCT " .
                location_identifier(
                    $locationTypeColumn
                ) .
                " AS location_type
                FROM locations
                WHERE " .
                location_identifier(
                    $locationTypeColumn
                ) .
                " IS NOT NULL
                AND TRIM(" .
                location_identifier(
                    $locationTypeColumn
                ) .
                ") <> ''
                ORDER BY " .
                location_identifier(
                    $locationTypeColumn
                ) . "
                ASC
            ");

        $locationTypes =
            $stmtTypes->fetchAll(
                PDO::FETCH_COLUMN
            );

    } catch (Throwable $e) {
        $locationTypes = [];
    }
}

/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
*/

$editLocation = null;

if ($editId > 0) {

    $stmtEdit =
        $pdo->prepare("
            SELECT *
            FROM locations
            WHERE " .
            location_identifier(
                $locationIdColumn
            ) .
            " = ?
            LIMIT 1
        ");

    $stmtEdit->execute([
        $editId
    ]);

    $editLocation =
        $stmtEdit->fetch(
            PDO::FETCH_ASSOC
        );

    if (!$editLocation) {

        set_flash(
            'error',
            'Data lokasi tidak ditemukan.'
        );

        redirect(
            BASE_URL .
            '/admin/locations.php'
        );
    }
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalLocations = 0;
$activeLocations = 0;
$inactiveLocations = 0;
$totalWithCoordinates = 0;

try {

    $totalLocations =
        (int) $pdo
            ->query("
                SELECT COUNT(*)
                FROM locations
            ")
            ->fetchColumn();

} catch (Throwable $e) {
    $totalLocations = 0;
}

if ($locationActiveColumn) {

    try {

        $activeLocations =
            (int) $pdo
                ->query("
                    SELECT COUNT(*)
                    FROM locations
                    WHERE " .
                    location_identifier(
                        $locationActiveColumn
                    ) .
                    " = 1
                ")
                ->fetchColumn();

        $inactiveLocations =
            (int) $pdo
                ->query("
                    SELECT COUNT(*)
                    FROM locations
                    WHERE " .
                    location_identifier(
                        $locationActiveColumn
                    ) .
                    " = 0
                ")
                ->fetchColumn();

    } catch (Throwable $e) {

        $activeLocations = 0;
        $inactiveLocations = 0;
    }

} else {

    $activeLocations =
        $totalLocations;
}

if (
    $locationLatitudeColumn &&
    $locationLongitudeColumn
) {

    try {

        $totalWithCoordinates =
            (int) $pdo
                ->query("
                    SELECT COUNT(*)
                    FROM locations
                    WHERE " .
                    location_identifier(
                        $locationLatitudeColumn
                    ) .
                    " IS NOT NULL
                    AND " .
                    location_identifier(
                        $locationLongitudeColumn
                    ) .
                    " IS NOT NULL
                    AND TRIM(CAST(" .
                    location_identifier(
                        $locationLatitudeColumn
                    ) .
                    " AS CHAR)) <> ''
                    AND TRIM(CAST(" .
                    location_identifier(
                        $locationLongitudeColumn
                    ) .
                    " AS CHAR)) <> ''
                ")
                ->fetchColumn();

    } catch (Throwable $e) {

        $totalWithCoordinates = 0;
    }
}

$flash =
    get_flash();

/*
|--------------------------------------------------------------------------
| Field Helper
|--------------------------------------------------------------------------
*/

function location_field(
    ?array $row,
    ?string $column,
    string $default = ''
): string {

    if (
        !$row ||
        !$column
    ) {
        return $default;
    }

    $value =
        $row[$column] ??
        '';

    return trim(
        (string) $value
    );
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($pageTitle) ?> - Star Parts Motor
    </title>

    <meta
        name="theme-color"
        content="#d90000"
    >

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

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        :root {
            --red: #d90000;
            --red-dark: #b80000;
            --black: #111;
            --text: #252525;
            --muted: #777;
            --border: #e7e7e7;
            --bg: #f7f7f9;
            --white: #fff;
            --success: #14804a;
            --warning: #b87a00;
            --danger: #c92525;
            --blue: #4667d8;
            --shadow: 0 12px 35px rgba(0,0,0,.07);
            --radius: 18px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        textarea,
        select {
            font: inherit;
        }

        .admin-layout {
            min-height: 100vh;
            display: flex;
        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .admin-sidebar {
            width: 275px;
            background: var(--black);
            color: #fff;
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1000;
            overflow-y: auto;
            transition: .3s ease;
        }

        .admin-brand {
            padding: 24px 22px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .admin-brand-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-brand-logo {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: var(--red);
            color: #fff;
            font-weight: 800;
            font-family: Poppins, sans-serif;
            box-shadow: 0 8px 20px rgba(217,0,0,.3);
        }

        .admin-brand h1 {
            margin: 0;
            font: 700 17px/1.2 Poppins, sans-serif;
        }

        .admin-brand p {
            margin: 4px 0 0;
            color: rgba(255,255,255,.55);
            font-size: 11px;
        }

        .admin-nav {
            padding: 18px 13px 24px;
        }

        .admin-nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: rgba(255,255,255,.35);
            padding: 12px 11px 8px;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px;
            border-radius: 11px;
            color: rgba(255,255,255,.72);
            margin-bottom: 4px;
            transition: .2s ease;
            font-size: 13px;
            font-weight: 600;
        }

        .admin-nav a i {
            width: 19px;
            text-align: center;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            color: #fff;
            background: var(--red);
            box-shadow: 0 8px 18px rgba(217,0,0,.22);
        }

        .admin-sidebar-bottom {
            margin-top: 20px;
            padding: 14px 13px 24px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .admin-user-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px;
            border-radius: 12px;
            background: rgba(255,255,255,.05);
            margin-bottom: 10px;
        }

        .admin-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #fff;
            color: var(--black);
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .admin-user-name {
            font-weight: 700;
            font-size: 12px;
        }

        .admin-user-role {
            color: rgba(255,255,255,.5);
            font-size: 10px;
            margin-top: 2px;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .admin-main {
            flex: 1;
            margin-left: 275px;
            min-width: 0;
        }

        .admin-topbar {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .admin-topbar-left {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .admin-mobile-toggle {
            width: 42px;
            height: 42px;
            display: none;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 12px;
            cursor: pointer;
        }

        .topbar-title h2 {
            margin: 0;
            font: 700 18px Poppins, sans-serif;
        }

        .topbar-title p {
            margin: 3px 0 0;
            font-size: 11px;
            color: var(--muted);
        }

        .admin-content {
            padding: 28px;
        }

        /*
        |--------------------------------------------------------------------------
        | FLASH
        |--------------------------------------------------------------------------
        */

        .flash {
            padding: 14px 16px;
            border-radius: 13px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .flash.success {
            background: #eaf8f1;
            color: var(--success);
            border: 1px solid #caeddc;
        }

        .flash.error {
            background: #fff0f0;
            color: var(--danger);
            border: 1px solid #ffd2d2;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin: 0;
            font: 800 28px Poppins, sans-serif;
            letter-spacing: -.5px;
        }

        .page-header p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 43px;
            padding: 0 16px;
            border: 0;
            border-radius: 11px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: .2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: #fff;
            background: var(--red);
            box-shadow: 0 8px 20px rgba(217,0,0,.18);
        }

        .btn-light {
            background: #f2f2f4;
            color: #333;
        }

        .btn-danger {
            background: #fff0f0;
            color: var(--danger);
        }

        .btn-success {
            background: #eaf8f1;
            color: var(--success);
        }

        .btn-blue {
            background: #f2f5ff;
            color: var(--blue);
        }

        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .stats-grid {
            display: grid;
            grid-template-columns:
                repeat(4,minmax(0,1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 19px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            flex: 0 0 50px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 18px;
            background: #fff0f0;
            color: var(--red);
        }

        .stat-label {
            font-size: 11px;
            color: var(--muted);
            margin-bottom: 3px;
        }

        .stat-value {
            font: 800 21px Poppins, sans-serif;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .form-card-head {
            padding: 20px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .form-card-head h3 {
            margin: 0;
            font: 700 16px Poppins, sans-serif;
        }

        .form-card-head p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 11px;
        }

        .form-card-body {
            padding: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns:
                repeat(12,minmax(0,1fr));
            gap: 16px;
        }

        .col-12 {
            grid-column: span 12;
        }

        .col-8 {
            grid-column: span 8;
        }

        .col-6 {
            grid-column: span 6;
        }

        .col-4 {
            grid-column: span 4;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-label {
            color: #444;
            font-size: 11px;
            font-weight: 700;
        }

        .form-label span {
            color: var(--red);
        }

        .form-control {
            width: 100%;
            min-height: 45px;
            padding: 10px 13px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            color: #222;
            outline: none;
            font-size: 12px;
            transition: .2s ease;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            line-height: 1.7;
        }

        .form-control:focus {
            border-color: var(--red);
            box-shadow:
                0 0 0 3px
                rgba(217,0,0,.08);
        }

        .form-help {
            color: #999;
            font-size: 10px;
            line-height: 1.5;
        }

        .form-actions {
            display: flex;
            gap: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | SWITCH
        |--------------------------------------------------------------------------
        */

        .switch-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 45px;
        }

        .switch {
            position: relative;
            width: 45px;
            height: 25px;
            flex: 0 0 45px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .switch-slider {
            position: absolute;
            inset: 0;
            background: #d8d8d8;
            border-radius: 999px;
            cursor: pointer;
            transition: .2s ease;
        }

        .switch-slider::before {
            content: '';
            position: absolute;
            width: 19px;
            height: 19px;
            left: 3px;
            top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,.15);
        }

        .switch input:checked + .switch-slider {
            background: var(--red);
        }

        .switch input:checked + .switch-slider::before {
            transform: translateX(20px);
        }

        /*
        |--------------------------------------------------------------------------
        | MAP PREVIEW
        |--------------------------------------------------------------------------
        */

        .map-preview {
            min-height: 190px;
            border-radius: 14px;
            border: 1px solid #eee;
            background:
                linear-gradient(
                    135deg,
                    #f5f5f5,
                    #fafafa
                );
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 25px;
            color: #999;
        }

        .map-preview i {
            display: block;
            font-size: 31px;
            color: var(--red);
            margin-bottom: 8px;
        }

        .map-preview strong {
            display: block;
            color: #555;
            font-size: 12px;
        }

        .map-preview span {
            display: block;
            margin-top: 4px;
            font-size: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .filter-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 17px;
            margin-bottom: 18px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns:
                minmax(0,1fr)
                180px
                170px
                auto
                auto;
            gap: 10px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-label {
            color: #777;
            font-size: 10px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-head {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
        }

        .table-head h3 {
            margin: 0;
            font: 700 15px Poppins, sans-serif;
        }

        .table-head p {
            margin: 4px 0 0;
            color: #999;
            font-size: 10px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1120px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px 16px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #fafafa;
            color: #777;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        td {
            font-size: 12px;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .location-cell {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 280px;
        }

        .location-icon {
            width: 47px;
            height: 47px;
            border-radius: 12px;
            background: #fff0f0;
            color: var(--red);
            display: grid;
            place-items: center;
            flex: 0 0 47px;
            font-size: 18px;
        }

        .location-name {
            color: #222;
            font-weight: 700;
            line-height: 1.4;
        }

        .location-type {
            display: inline-flex;
            margin-top: 5px;
            padding: 4px 7px;
            border-radius: 999px;
            background: #f2f2f4;
            color: #777;
            font-size: 9px;
            font-weight: 700;
        }

        .address-text {
            max-width: 270px;
            color: #666;
            line-height: 1.55;
        }

        .contact-text {
            line-height: 1.55;
            color: #555;
        }

        .coordinates {
            white-space: nowrap;
            color: #666;
            font-size: 10px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-active {
            background: #eaf8f1;
            color: var(--success);
        }

        .badge-inactive {
            background: #f2f2f2;
            color: #777;
        }

        .badge-map {
            background: #f2f5ff;
            color: var(--blue);
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            border: 0;
            display: inline-grid;
            place-items: center;
            cursor: pointer;
            transition: .2s ease;
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        .action-edit {
            background: #f2f5ff;
            color: var(--blue);
        }

        .action-delete {
            background: #fff0f0;
            color: var(--danger);
        }

        .empty-state {
            padding: 55px 25px;
            text-align: center;
            color: #999;
        }

        .empty-state i {
            display: block;
            color: #ccc;
            font-size: 38px;
            margin-bottom: 12px;
        }

        .empty-state h4 {
            margin: 0 0 5px;
            color: #555;
            font: 700 15px Poppins, sans-serif;
        }

        .empty-state p {
            margin: 0;
            font-size: 11px;
        }

        /*
        |--------------------------------------------------------------------------
        | OVERLAY
        |--------------------------------------------------------------------------
        */

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 950;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1250px) {

            .stats-grid {
                grid-template-columns:
                    repeat(2,minmax(0,1fr));
            }

            .filter-grid {
                grid-template-columns:
                    minmax(0,1fr)
                    160px
                    160px
                    auto;
            }

        }

        @media (max-width: 900px) {

            .admin-sidebar {
                transform:
                    translateX(-100%);
            }

            .admin-sidebar.show {
                transform:
                    translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-mobile-toggle {
                display: inline-flex;
            }

            .admin-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns:
                    repeat(2,minmax(0,1fr));
            }

            .col-12,
            .col-8,
            .col-6,
            .col-4 {
                grid-column: span 2;
            }

            .filter-grid {
                grid-template-columns:
                    1fr 1fr;
            }

        }

        @media (max-width: 600px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-topbar {
                padding: 12px 15px;
            }

            .admin-content {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 23px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .form-card-head {
                padding: 17px 16px;
            }

            .form-card-body {
                padding: 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .col-12,
            .col-8,
            .col-6,
            .col-4 {
                grid-column: span 1;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .form-actions .btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->

    <aside
        class="admin-sidebar"
        id="adminSidebar"
    >

        <div class="admin-brand">

            <div class="admin-brand-wrap">

                <div class="admin-brand-logo">
                    SPM
                </div>

                <div>

                    <h1>
                        Star Parts Motor
                    </h1>

                    <p>
                        Administration Panel
                    </p>

                </div>

            </div>

        </div>

        <nav class="admin-nav">

            <div class="admin-nav-label">
                Main
            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/dashboard.php"
            >
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>

            <div class="admin-nav-label">
                Management
            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/banners.php"
            >
                <i class="fa-solid fa-images"></i>
                <span>Banner</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/products.php"
            >
                <i class="fa-solid fa-box-open"></i>
                <span>Produk</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/categories.php"
            >
                <i class="fa-solid fa-layer-group"></i>
                <span>Kategori</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/brands.php"
            >
                <i class="fa-solid fa-tags"></i>
                <span>Brand</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/posts.php"
            >
                <i class="fa-solid fa-newspaper"></i>
                <span>Berita / Artikel</span>
            </a>

            <div class="admin-nav-label">
                Website
            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/pages.php"
            >
                <i class="fa-solid fa-file-lines"></i>
                <span>Halaman</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/media.php"
            >
                <i class="fa-solid fa-photo-film"></i>
                <span>Media</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/settings.php"
            >
                <i class="fa-solid fa-sliders"></i>
                <span>Pengaturan</span>
            </a>

            <div class="admin-nav-label">
                Others
            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/messages.php"
            >
                <i class="fa-solid fa-envelope"></i>
                <span>Pesan Kontak</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/locations.php"
                class="active"
            >
                <i class="fa-solid fa-location-dot"></i>
                <span>Lokasi</span>
            </a>

            <a
                href="<?= e(BASE_URL) ?>/admin/testimonials.php"
            >
                <i class="fa-solid fa-comment-dots"></i>
                <span>Testimoni</span>
            </a>

        </nav>

        <div class="admin-sidebar-bottom">

            <div class="admin-user-box">

                <div class="admin-user-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>

                    <div class="admin-user-name">
                        <?= e(
                            $admin['name']
                            ??
                            $admin['username']
                            ??
                            'Administrator'
                        ) ?>
                    </div>

                    <div class="admin-user-role">
                        Administrator
                    </div>

                </div>

            </div>

            <a
                href="<?= e(BASE_URL) ?>/admin/logout.php"
                style="
                    display:flex;
                    align-items:center;
                    gap:10px;
                    padding:11px 12px;
                    color:rgba(255,255,255,.65);
                    font-size:12px;
                    font-weight:600;
                "
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>

        </div>

    </aside>

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>

    <!-- MAIN -->

    <main class="admin-main">

        <header class="admin-topbar">

            <div class="admin-topbar-left">

                <button
                    type="button"
                    class="admin-mobile-toggle"
                    id="adminMobileToggle"
                    aria-label="Buka menu"
                >
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="topbar-title">

                    <h2>
                        Lokasi
                    </h2>

                    <p>
                        Kelola kantor, gudang, cabang, dan titik distribusi
                    </p>

                </div>

            </div>

            <a
                href="<?= e(BASE_URL) ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-light"
            >
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Lihat Website
            </a>

        </header>

        <div class="admin-content">

            <?php if ($flash): ?>

                <div
                    class="flash <?= e(
                        $flash['type']
                        ?? 'success'
                    ) ?>"
                >
                    <?= e(
                        $flash['message']
                        ?? ''
                    ) ?>
                </div>

            <?php endif; ?>

            <!-- HEADER -->

            <div class="page-header">

                <div>

                    <h1>
                        Lokasi
                    </h1>

                    <p>
                        Kelola informasi lokasi perusahaan dan titik
                        distribusi Star Parts Motor.
                    </p>

                </div>

                <a
                    href="#location-form"
                    class="btn btn-primary"
                >
                    <i class="fa-solid fa-plus"></i>
                    Tambah Lokasi
                </a>

            </div>

            <!-- STATS -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Lokasi
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $totalLocations,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Lokasi Aktif
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $activeLocations,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-circle-pause"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Lokasi Nonaktif
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $inactiveLocations,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Memiliki Koordinat
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $totalWithCoordinates,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

            <!-- FORM -->

            <div
                class="form-card"
                id="location-form"
            >

                <div class="form-card-head">

                    <div>

                        <h3>
                            <?= $editLocation
                                ? 'Edit Lokasi'
                                : 'Tambah Lokasi Baru'
                            ?>
                        </h3>

                        <p>
                            <?= $editLocation
                                ? 'Perbarui informasi lokasi yang dipilih.'
                                : 'Tambahkan kantor, gudang, cabang, atau titik distribusi baru.'
                            ?>
                        </p>

                    </div>

                    <?php if ($editLocation): ?>

                        <a
                            href="<?= e(BASE_URL) ?>/admin/locations.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Batal Edit
                        </a>

                    <?php endif; ?>

                </div>

                <div class="form-card-body">

                    <form
                        method="post"
                        action="<?= e(BASE_URL) ?>/admin/locations.php"
                    >

                        <?= csrf_input() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="save"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) (
                                $editLocation[
                                    $locationIdColumn
                                ]
                                ??
                                0
                            ) ?>"
                        >

                        <div class="form-grid">

                            <!-- NAMA -->

                            <div class="form-group col-8">

                                <label class="form-label">
                                    Nama Lokasi
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationNameColumn
                                        )
                                    ) ?>"
                                    placeholder="Contoh: Kantor Pusat Star Parts Motor"
                                    required
                                >

                            </div>

                            <!-- TYPE -->

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Tipe Lokasi
                                </label>

                                <input
                                    type="text"
                                    name="type"
                                    list="locationTypes"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationTypeColumn
                                        )
                                    ) ?>"
                                    placeholder="Kantor / Gudang / Cabang"
                                >

                                <datalist
                                    id="locationTypes"
                                >

                                    <?php foreach (
                                        $locationTypes
                                        as $locationType
                                    ): ?>

                                        <option
                                            value="<?= e(
                                                $locationType
                                            ) ?>"
                                        ></option>

                                    <?php endforeach; ?>

                                </datalist>

                            </div>

                            <!-- ALAMAT -->

                            <div class="form-group col-12">

                                <label class="form-label">
                                    Alamat Lengkap
                                </label>

                                <textarea
                                    name="address"
                                    class="form-control"
                                    placeholder="Alamat lengkap lokasi..."
                                ><?= e(
                                    location_field(
                                        $editLocation,
                                        $locationAddressColumn
                                    )
                                ) ?></textarea>

                            </div>

                            <!-- CITY -->

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Kota / Kabupaten
                                </label>

                                <input
                                    type="text"
                                    name="city"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationCityColumn
                                        )
                                    ) ?>"
                                    placeholder="Bandung"
                                >

                            </div>

                            <!-- PROVINCE -->

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Provinsi
                                </label>

                                <input
                                    type="text"
                                    name="province"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationProvinceColumn
                                        )
                                    ) ?>"
                                    placeholder="Jawa Barat"
                                >

                            </div>

                            <!-- POSTAL -->

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Kode Pos
                                </label>

                                <input
                                    type="text"
                                    name="postal_code"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationPostalColumn
                                        )
                                    ) ?>"
                                    placeholder="401xx"
                                >

                            </div>

                            <!-- PHONE -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Telepon
                                </label>

                                <input
                                    type="text"
                                    name="phone"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationPhoneColumn
                                        )
                                    ) ?>"
                                    placeholder="022xxxxxxx"
                                >

                            </div>

                            <!-- EMAIL -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationEmailColumn
                                        )
                                    ) ?>"
                                    placeholder="info@starpartsmotor.com"
                                >

                            </div>

                            <!-- LATITUDE -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Latitude
                                </label>

                                <input
                                    type="text"
                                    name="latitude"
                                    id="latitude"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationLatitudeColumn
                                        )
                                    ) ?>"
                                    placeholder="-6.917464"
                                >

                                <div class="form-help">
                                    Contoh koordinat: -6.917464
                                </div>

                            </div>

                            <!-- LONGITUDE -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Longitude
                                </label>

                                <input
                                    type="text"
                                    name="longitude"
                                    id="longitude"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationLongitudeColumn
                                        )
                                    ) ?>"
                                    placeholder="107.619123"
                                >

                                <div class="form-help">
                                    Contoh koordinat: 107.619123
                                </div>

                            </div>

                            <!-- MAP URL -->

                            <div class="form-group col-12">

                                <label class="form-label">
                                    Google Maps URL
                                </label>

                                <input
                                    type="url"
                                    name="map_url"
                                    class="form-control"
                                    value="<?= e(
                                        location_field(
                                            $editLocation,
                                            $locationMapUrlColumn
                                        )
                                    ) ?>"
                                    placeholder="https://maps.google.com/?q=..."
                                >

                                <div class="form-help">
                                    Masukkan link Google Maps lokasi apabila tersedia.
                                </div>

                            </div>

                            <!-- DESCRIPTION -->

                            <div class="form-group col-8">

                                <label class="form-label">
                                    Deskripsi
                                </label>

                                <textarea
                                    name="description"
                                    class="form-control"
                                    placeholder="Informasi tambahan lokasi..."
                                ><?= e(
                                    location_field(
                                        $editLocation,
                                        $locationDescriptionColumn
                                    )
                                ) ?></textarea>

                            </div>

                            <!-- MAP PREVIEW -->

                            <div class="form-group col-4">

                                <label class="form-label">
                                    Preview Peta
                                </label>

                                <div
                                    class="map-preview"
                                    id="mapPreview"
                                >

                                    <div>

                                        <i class="fa-solid fa-map-location-dot"></i>

                                        <strong>
                                            Koordinat Lokasi
                                        </strong>

                                        <span id="mapPreviewText">
                                            Masukkan latitude dan longitude.
                                        </span>

                                    </div>

                                </div>

                            </div>

                            <!-- SORT -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Urutan Tampil
                                </label>

                                <input
                                    type="number"
                                    name="sort_order"
                                    class="form-control"
                                    value="<?= (int) (
                                        $editLocation[
                                            $locationSortColumn
                                        ]
                                        ??
                                        0
                                    ) ?>"
                                    min="0"
                                    step="1"
                                >

                                <div class="form-help">
                                    Nilai lebih kecil tampil lebih dahulu.
                                </div>

                            </div>

                            <!-- STATUS -->

                            <div class="form-group col-6">

                                <label class="form-label">
                                    Status
                                </label>

                                <div class="switch-wrap">

                                    <label class="switch">

                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            <?= (
                                                !$editLocation ||
                                                !$locationActiveColumn ||
                                                (int) (
                                                    $editLocation[
                                                        $locationActiveColumn
                                                    ]
                                                    ??
                                                    0
                                                ) === 1
                                            )
                                                ? 'checked'
                                                : ''
                                            ?>
                                        >

                                        <span class="switch-slider"></span>

                                    </label>

                                    <div>

                                        <strong
                                            style="
                                                font-size:12px;
                                            "
                                        >
                                            Aktifkan lokasi
                                        </strong>

                                        <div
                                            style="
                                                color:#999;
                                                font-size:10px;
                                                margin-top:3px;
                                            "
                                        >
                                            Lokasi aktif dapat ditampilkan pada website.
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <!-- ACTION -->

                            <div class="col-12">

                                <div class="form-actions">

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <i class="fa-solid fa-floppy-disk"></i>

                                        <?= $editLocation
                                            ? 'Simpan Perubahan'
                                            : 'Simpan Lokasi'
                                        ?>
                                    </button>

                                    <?php if ($editLocation): ?>

                                        <a
                                            href="<?= e(BASE_URL) ?>/admin/locations.php"
                                            class="btn btn-light"
                                        >
                                            Batal
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

            <!-- FILTER -->

            <div class="filter-card">

                <form
                    method="get"
                    action="<?= e(BASE_URL) ?>/admin/locations.php"
                >

                    <div class="filter-grid">

                        <div class="filter-group">

                            <label class="filter-label">
                                Cari Lokasi
                            </label>

                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                value="<?= e(
                                    $search
                                ) ?>"
                                placeholder="Nama, alamat, kota, telepon..."
                            >

                        </div>

                        <div class="filter-group">

                            <label class="filter-label">
                                Tipe
                            </label>

                            <select
                                name="type"
                                class="form-control"
                            >

                                <option value="">
                                    Semua Tipe
                                </option>

                                <?php foreach (
                                    $locationTypes
                                    as $locationType
                                ): ?>

                                    <option
                                        value="<?= e(
                                            $locationType
                                        ) ?>"
                                        <?= $typeFilter ===
                                            $locationType
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        <?= e(
                                            $locationType
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="filter-group">

                            <label class="filter-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-control"
                            >

                                <option value="">
                                    Semua
                                </option>

                                <option
                                    value="active"
                                    <?= $statusFilter === 'active'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Aktif
                                </option>

                                <option
                                    value="inactive"
                                    <?= $statusFilter === 'inactive'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Nonaktif
                                </option>

                            </select>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-filter"></i>
                            Filter
                        </button>

                        <a
                            href="<?= e(BASE_URL) ?>/admin/locations.php"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                            Reset
                        </a>

                    </div>

                </form>

            </div>

            <!-- TABLE -->

            <div class="table-card">

                <div class="table-head">

                    <h3>
                        Daftar Lokasi
                    </h3>

                    <p>
                        Menampilkan
                        <?= number_format(
                            count($locations),
                            0,
                            ',',
                            '.'
                        ) ?>
                        lokasi
                    </p>

                </div>

                <div class="table-responsive">

                    <?php if (!$locations): ?>

                        <div class="empty-state">

                            <i class="fa-solid fa-location-dot"></i>

                            <h4>
                                Belum ada lokasi
                            </h4>

                            <p>
                                Tambahkan lokasi perusahaan untuk mulai mengisi jaringan distribusi.
                            </p>

                        </div>

                    <?php else: ?>

                        <table>

                            <thead>

                                <tr>

                                    <th width="60">
                                        No
                                    </th>

                                    <th>
                                        Lokasi
                                    </th>

                                    <th>
                                        Alamat
                                    </th>

                                    <th>
                                        Kontak
                                    </th>

                                    <th>
                                        Koordinat
                                    </th>

                                    <th width="120">
                                        Status
                                    </th>

                                    <th width="110">
                                        Aksi
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach (
                                $locations
                                as $index => $location
                            ): ?>

                                <?php

                                $locationId =
                                    (int) (
                                        $location[
                                            $locationIdColumn
                                        ]
                                        ??
                                        0
                                    );

                                $name =
                                    location_field(
                                        $location,
                                        $locationNameColumn,
                                        'Lokasi'
                                    );

                                $type =
                                    location_field(
                                        $location,
                                        $locationTypeColumn,
                                        ''
                                    );

                                $address =
                                    location_field(
                                        $location,
                                        $locationAddressColumn
                                    );

                                $city =
                                    location_field(
                                        $location,
                                        $locationCityColumn
                                    );

                                $province =
                                    location_field(
                                        $location,
                                        $locationProvinceColumn
                                    );

                                $phone =
                                    location_field(
                                        $location,
                                        $locationPhoneColumn
                                    );

                                $email =
                                    location_field(
                                        $location,
                                        $locationEmailColumn
                                    );

                                $latitude =
                                    location_field(
                                        $location,
                                        $locationLatitudeColumn
                                    );

                                $longitude =
                                    location_field(
                                        $location,
                                        $locationLongitudeColumn
                                    );

                                $mapUrl =
                                    location_field(
                                        $location,
                                        $locationMapUrlColumn
                                    );

                                $isActive =
                                    !$locationActiveColumn ||
                                    (int) (
                                        $location[
                                            $locationActiveColumn
                                        ]
                                        ??
                                        0
                                    ) === 1;

                                ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>

                                        <div class="location-cell">

                                            <div class="location-icon">

                                                <i class="fa-solid fa-location-dot"></i>

                                            </div>

                                            <div>

                                                <div class="location-name">
                                                    <?= e($name) ?>
                                                </div>

                                                <?php if ($type): ?>

                                                    <span class="location-type">
                                                        <?= e($type) ?>
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <div class="address-text">

                                            <?php if ($address): ?>

                                                <?= e(
                                                    excerpt(
                                                        $address,
                                                        120
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                <span
                                                    style="
                                                        color:#aaa;
                                                    "
                                                >
                                                    Alamat belum diisi
                                                </span>

                                            <?php endif; ?>

                                            <?php if (
                                                $city ||
                                                $province
                                            ): ?>

                                                <div
                                                    style="
                                                        margin-top:5px;
                                                        color:#999;
                                                        font-size:10px;
                                                    "
                                                >
                                                    <?= e(
                                                        trim(
                                                            $city .
                                                            (
                                                                $city &&
                                                                $province
                                                                    ? ', '
                                                                    : ''
                                                            ) .
                                                            $province
                                                        )
                                                    ) ?>
                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                    <td>

                                        <div class="contact-text">

                                            <?php if ($phone): ?>

                                                <div>
                                                    <i class="fa-solid fa-phone"></i>
                                                    <?= e($phone) ?>
                                                </div>

                                            <?php endif; ?>

                                            <?php if ($email): ?>

                                                <div
                                                    style="
                                                        margin-top:4px;
                                                    "
                                                >
                                                    <i class="fa-solid fa-envelope"></i>
                                                    <?= e($email) ?>
                                                </div>

                                            <?php endif; ?>

                                            <?php if (
                                                !$phone &&
                                                !$email
                                            ): ?>

                                                <span
                                                    style="
                                                        color:#aaa;
                                                    "
                                                >
                                                    -
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                    <td>

                                        <?php if (
                                            $latitude !== '' &&
                                            $longitude !== ''
                                        ): ?>

                                            <div class="coordinates">

                                                <?= e(
                                                    $latitude
                                                ) ?>

                                                ,

                                                <?= e(
                                                    $longitude
                                                ) ?>

                                            </div>

                                            <?php if (
                                                $mapUrl !== ''
                                            ): ?>

                                                <div
                                                    style="
                                                        margin-top:5px;
                                                    "
                                                >

                                                    <a
                                                        href="<?= e(
                                                            $mapUrl
                                                        ) ?>"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="badge badge-map"
                                                    >
                                                        <i class="fa-solid fa-map"></i>
                                                        Buka Maps
                                                    </a>

                                                </div>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span
                                                style="
                                                    color:#aaa;
                                                    font-size:10px;
                                                "
                                            >
                                                Belum tersedia
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if ($isActive): ?>

                                            <span class="badge badge-active">

                                                <i class="fa-solid fa-circle-check"></i>
                                                Aktif

                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-inactive">

                                                <i class="fa-solid fa-circle-pause"></i>
                                                Nonaktif

                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="action-group">

                                            <a
                                                href="<?= e(BASE_URL) ?>/admin/locations.php?edit=<?= $locationId ?>#location-form"
                                                class="action-btn action-edit"
                                                title="Edit"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                            <form
                                                method="post"
                                                action="<?= e(BASE_URL) ?>/admin/locations.php"
                                                style="display:inline;"
                                                onsubmit="
                                                    return confirm(
                                                        'Yakin ingin menghapus lokasi ini?'
                                                    );
                                                "
                                            >

                                                <?= csrf_input() ?>

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= $locationId ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action-btn action-delete"
                                                    title="Hapus"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </main>

</div>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | MOBILE SIDEBAR
        |--------------------------------------------------------------------------
        */

        const sidebar =
            document.getElementById(
                'adminSidebar'
            );

        const toggle =
            document.getElementById(
                'adminMobileToggle'
            );

        const overlay =
            document.getElementById(
                'sidebarOverlay'
            );

        function openSidebar() {

            if (!sidebar) {
                return;
            }

            sidebar.classList.add(
                'show'
            );

            if (overlay) {

                overlay.classList.add(
                    'show'
                );

            }

            document.body.style.overflow =
                'hidden';
        }

        function closeSidebar() {

            if (!sidebar) {
                return;
            }

            sidebar.classList.remove(
                'show'
            );

            if (overlay) {

                overlay.classList.remove(
                    'show'
                );

            }

            document.body.style.overflow =
                '';
        }

        if (toggle) {

            toggle.addEventListener(
                'click',
                function () {

                    if (
                        sidebar.classList.contains(
                            'show'
                        )
                    ) {

                        closeSidebar();

                    } else {

                        openSidebar();

                    }

                }
            );

        }

        if (overlay) {

            overlay.addEventListener(
                'click',
                closeSidebar
            );

        }

        /*
        |--------------------------------------------------------------------------
        | MAP PREVIEW
        |--------------------------------------------------------------------------
        */

        const latitude =
            document.getElementById(
                'latitude'
            );

        const longitude =
            document.getElementById(
                'longitude'
            );

        const mapPreviewText =
            document.getElementById(
                'mapPreviewText'
            );

        function updateMapPreview() {

            if (
                !latitude ||
                !longitude ||
                !mapPreviewText
            ) {
                return;
            }

            const lat =
                latitude.value.trim();

            const lng =
                longitude.value.trim();

            if (
                lat !== '' &&
                lng !== ''
            ) {

                mapPreviewText.innerHTML =
                    'Lokasi: ' +
                    lat +
                    ', ' +
                    lng;

            } else {

                mapPreviewText.textContent =
                    'Masukkan latitude dan longitude.';

            }

        }

        if (latitude) {

            latitude.addEventListener(
                'input',
                updateMapPreview
            );

        }

        if (longitude) {

            longitude.addEventListener(
                'input',
                updateMapPreview
            );

        }

        updateMapPreview();

        /*
        |--------------------------------------------------------------------------
        | DOUBLE SUBMIT
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                'form'
            )
            .forEach(
                function (form) {

                    form.addEventListener(
                        'submit',
                        function (event) {

                            if (
                                form.dataset
                                    .submitting ===
                                'true'
                            ) {

                                event.preventDefault();

                                return;
                            }

                            form.dataset.submitting =
                                'true';

                            const button =
                                form.querySelector(
                                    'button[type="submit"]'
                                );

                            if (button) {

                                button.disabled =
                                    true;

                                button.style.opacity =
                                    '.7';

                            }

                        }
                    );

                }
            );

    }
);

</script>

</body>
</html>
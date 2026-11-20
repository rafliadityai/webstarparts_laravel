<?php
// ============================================================
// FILE   : messages.php
// LOKASI : starparts/admin/messages.php
// ============================================================
//
// Manajemen pesan dari form kontak website Star Parts Motor.
//
// Fitur:
// - Daftar pesan masuk
// - Statistik pesan
// - Filter status baca/belum baca
// - Pencarian nama, perusahaan, email, telepon, subjek, pesan
// - Detail pesan
// - Tandai sudah dibaca
// - Tandai belum dibaca
// - Hapus pesan
// - Tombol email
// - Tombol WhatsApp
// - Kompatibel dengan beberapa variasi nama kolom database
// - CSRF protection
// - Responsive
//
// Tidak mengubah alur frontend.
// ============================================================

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';


/*
|------------------------------------------------------------------
| AUTH
|------------------------------------------------------------------
*/

require_admin();

$admin = current_admin();


/*
|------------------------------------------------------------------
| BASIC HELPERS
|------------------------------------------------------------------
*/

function spm_messages_h(
    mixed $value
): string {

    return e(
        (string)$value
    );
}


function spm_messages_redirect(
    string $query = ''
): never {

    $url = base_url(
        'admin/messages.php'
    );

    if ($query !== '') {

        $url .=
            '?' .
            ltrim(
                $query,
                '?'
            );
    }

    redirect($url);
}


function spm_messages_normalize_phone(
    string $phone
): string {

    $phone = preg_replace(
        '/[^0-9]/',
        '',
        $phone
    ) ?? '';


    if (
        str_starts_with(
            $phone,
            '0'
        )
    ) {

        $phone =
            '62' .
            substr(
                $phone,
                1
            );
    }


    return $phone;
}


function spm_messages_column(
    array $columns,
    array $candidates
): ?string {

    foreach ($candidates as $candidate) {

        if (
            in_array(
                $candidate,
                $columns,
                true
            )
        ) {

            return $candidate;
        }
    }

    return null;
}


function spm_messages_identifier(
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
|------------------------------------------------------------------
| DATABASE
|------------------------------------------------------------------
*/

if (
    !isset($pdo)
    || !($pdo instanceof PDO)
) {

    http_response_code(500);

    exit(
        'Database connection tidak tersedia.'
    );
}


/*
|------------------------------------------------------------------
| CHECK TABLE
|------------------------------------------------------------------
*/

try {

    $tableCheck = $pdo->query(
        "SHOW TABLES LIKE 'contact_messages'"
    );

    $tableExists = (
        $tableCheck
        && $tableCheck->fetchColumn() !== false
    );

} catch (Throwable $e) {

    error_log(
        'StarParts admin messages table check error: ' .
        $e->getMessage()
    );

    $tableExists = false;
}


if (!$tableExists) {

    http_response_code(500);

    exit(
        'Tabel contact_messages belum tersedia.'
    );
}


/*
|------------------------------------------------------------------
| DETECT COLUMNS
|------------------------------------------------------------------
*/

try {

    $columnStmt = $pdo->query(
        "SHOW COLUMNS FROM `contact_messages`"
    );

    $columnRows = $columnStmt
        ? $columnStmt->fetchAll(PDO::FETCH_ASSOC)
        : [];

} catch (Throwable $e) {

    error_log(
        'StarParts admin messages column check error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    exit(
        'Struktur tabel contact_messages tidak dapat diperiksa.'
    );
}


$columns = [];

foreach ($columnRows as $row) {

    if (
        isset($row['Field'])
        && $row['Field'] !== ''
    ) {

        $columns[] = (string)$row['Field'];
    }
}


/*
|------------------------------------------------------------------
| COLUMN MAP
|------------------------------------------------------------------
*/

$idColumn = spm_messages_column(
    $columns,
    [
        'id',
        'message_id'
    ]
);


$nameColumn = spm_messages_column(
    $columns,
    [
        'name',
        'nama',
        'sender_name',
        'full_name'
    ]
);


$companyColumn = spm_messages_column(
    $columns,
    [
        'company',
        'perusahaan',
        'company_name',
        'organization',
        'organization_name'
    ]
);


$emailColumn = spm_messages_column(
    $columns,
    [
        'email',
        'sender_email'
    ]
);


$phoneColumn = spm_messages_column(
    $columns,
    [
        'phone',
        'telepon',
        'whatsapp',
        'phone_number',
        'mobile'
    ]
);


$subjectColumn = spm_messages_column(
    $columns,
    [
        'subject',
        'subjek',
        'title'
    ]
);


$messageColumn = spm_messages_column(
    $columns,
    [
        'message',
        'pesan',
        'content',
        'body'
    ]
);


$isReadColumn = spm_messages_column(
    $columns,
    [
        'is_read',
        'read_status'
    ]
);


$readAtColumn = spm_messages_column(
    $columns,
    [
        'read_at'
    ]
);


$createdAtColumn = spm_messages_column(
    $columns,
    [
        'created_at',
        'submitted_at',
        'sent_at',
        'tanggal'
    ]
);


/*
|------------------------------------------------------------------
| REQUIRED COLUMN
|------------------------------------------------------------------
*/

if ($idColumn === null) {

    http_response_code(500);

    exit(
        'Kolom ID pada contact_messages tidak ditemukan.'
    );
}


/*
|------------------------------------------------------------------
| FLASH
|------------------------------------------------------------------
*/

$flash = get_flash();

if (!is_array($flash)) {
    $flash = null;
}


/*
|------------------------------------------------------------------
| REQUEST PARAMETERS
|------------------------------------------------------------------
*/

$action = trim(
    (string)(
        $_POST['action']
        ?? $_GET['action']
        ?? ''
    )
);


$selectedId = (int)(
    $_POST['id']
    ?? $_GET['id']
    ?? 0
);


$statusFilter = trim(
    (string)(
        $_GET['status']
        ?? ''
    )
);


$search = trim(
    (string)(
        $_GET['q']
        ?? ''
    )
);


/*
|------------------------------------------------------------------
| CSRF
|------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = trim(
        (string)(
            $_POST['csrf_token']
            ?? ''
        )
    );


    try {

        if (
            $csrfToken === ''
            || !verify_csrf()
        ) {

            set_flash(
                'error',
                'Sesi keamanan formulir tidak valid. Silakan coba kembali.'
            );

            spm_messages_redirect();
        }

    } catch (Throwable $e) {

        error_log(
            'StarParts admin messages CSRF error: ' .
            $e->getMessage()
        );

        set_flash(
            'error',
            'Validasi keamanan gagal. Silakan coba kembali.'
        );

        spm_messages_redirect();
    }
}


/*
|------------------------------------------------------------------
| ACTION: MARK READ
|------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $action === 'mark_read'
) {

    if (
        $selectedId <= 0
        || $isReadColumn === null
    ) {

        set_flash(
            'error',
            'Pesan tidak dapat ditandai sebagai sudah dibaca.'
        );

        spm_messages_redirect();
    }


    try {

        $sql =
            'UPDATE `contact_messages` SET ' .
            spm_messages_identifier(
                $isReadColumn
            ) .
            ' = 1';


        if ($readAtColumn !== null) {

            $sql .=
                ', ' .
                spm_messages_identifier(
                    $readAtColumn
                ) .
                ' = NOW()';
        }


        $sql .=
            ' WHERE ' .
            spm_messages_identifier(
                $idColumn
            ) .
            ' = ?';


        $stmt = $pdo->prepare(
            $sql
        );

        $stmt->execute([
            $selectedId
        ]);


        set_flash(
            'success',
            'Pesan berhasil ditandai sebagai sudah dibaca.'
        );

    } catch (Throwable $e) {

        error_log(
            'StarParts admin messages mark read error: ' .
            $e->getMessage()
        );

        set_flash(
            'error',
            'Pesan gagal diperbarui.'
        );
    }


    spm_messages_redirect();
}


/*
|------------------------------------------------------------------
| ACTION: MARK UNREAD
|------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $action === 'mark_unread'
) {

    if (
        $selectedId <= 0
        || $isReadColumn === null
    ) {

        set_flash(
            'error',
            'Pesan tidak dapat ditandai sebagai belum dibaca.'
        );

        spm_messages_redirect();
    }


    try {

        $sql =
            'UPDATE `contact_messages` SET ' .
            spm_messages_identifier(
                $isReadColumn
            ) .
            ' = 0';


        if ($readAtColumn !== null) {

            $sql .=
                ', ' .
                spm_messages_identifier(
                    $readAtColumn
                ) .
                ' = NULL';
        }


        $sql .=
            ' WHERE ' .
            spm_messages_identifier(
                $idColumn
            ) .
            ' = ?';


        $stmt = $pdo->prepare(
            $sql
        );

        $stmt->execute([
            $selectedId
        ]);


        set_flash(
            'success',
            'Pesan berhasil ditandai sebagai belum dibaca.'
        );

    } catch (Throwable $e) {

        error_log(
            'StarParts admin messages mark unread error: ' .
            $e->getMessage()
        );

        set_flash(
            'error',
            'Pesan gagal diperbarui.'
        );
    }


    spm_messages_redirect();
}


/*
|------------------------------------------------------------------
| ACTION: DELETE
|------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $action === 'delete'
) {

    if ($selectedId <= 0) {

        set_flash(
            'error',
            'ID pesan tidak valid.'
        );

        spm_messages_redirect();
    }


    try {

        $stmt = $pdo->prepare(
            'DELETE FROM `contact_messages`
             WHERE ' .
             spm_messages_identifier(
                 $idColumn
             ) .
             ' = ?
             LIMIT 1'
        );


        $stmt->execute([
            $selectedId
        ]);


        if (
            $stmt->rowCount() === 1
        ) {

            set_flash(
                'success',
                'Pesan berhasil dihapus.'
            );

        } else {

            set_flash(
                'error',
                'Pesan tidak ditemukan.'
            );
        }

    } catch (Throwable $e) {

        error_log(
            'StarParts admin messages delete error: ' .
            $e->getMessage()
        );

        set_flash(
            'error',
            'Pesan gagal dihapus.'
        );
    }


    spm_messages_redirect();
}


/*
|------------------------------------------------------------------
| DETAIL DATA
|------------------------------------------------------------------
*/

$detail = null;


if (
    $selectedId > 0
    && (
        isset($_GET['view'])
        || $action === 'view'
    )
) {

    $selectParts = [
        spm_messages_identifier($idColumn)
        . ' AS id'
    ];


    if ($nameColumn !== null) {

        $selectParts[] =
            spm_messages_identifier(
                $nameColumn
            ) .
            ' AS name';
    } else {

        $selectParts[] =
            "'' AS name";
    }


    if ($companyColumn !== null) {

        $selectParts[] =
            spm_messages_identifier(
                $companyColumn
            ) .
            ' AS company';
    } else {

        $selectParts[] =
            "'' AS company";
    }


    if ($emailColumn !== null) {

        $selectParts[] =
            spm_messages_identifier(
                $emailColumn
            ) .
            ' AS email';
    } else {

        $selectParts[] =
            "'' AS email";
    }


    if ($phoneColumn !== null) {

        $selectParts[] =
            spm_messages_identifier(
                $phoneColumn
            ) .
            ' AS phone';
    } else {

        $selectParts[] =
            "'' AS phone";
    }


    if ($subjectColumn !== null) {

        $selectParts[] =
            spm_messages_identifier(
                $subjectColumn
            ) .
            ' AS subject';
    } else {

        $selectParts[] =
            "'' AS subject";
    }


    if ($messageColumn !== null) {

        $selectParts[] =
            spm_messages_identifier(
                $messageColumn
            ) .
            ' AS message';
    } else {

        $selectParts[] =
            "'' AS message";
    }


    if ($isReadColumn !== null) {

        $selectParts[] =
            spm_messages_identifier(
                $isReadColumn
            ) .
            ' AS is_read';
    } else {

        $selectParts[] =
            "0 AS is_read";
    }


    if ($readAtColumn !== null) {

        $selectParts[] =
            spm_messages_identifier(
                $readAtColumn
            ) .
            ' AS read_at';
    } else {

        $selectParts[] =
            'NULL AS read_at';
    }


    if ($createdAtColumn !== null) {

        $selectParts[] =
            spm_messages_identifier(
                $createdAtColumn
            ) .
            ' AS created_at';
    } else {

        $selectParts[] =
            'NULL AS created_at';
    }


    try {

        $stmt = $pdo->prepare(
            'SELECT ' .
            implode(
                ', ',
                $selectParts
            ) .
            ' FROM `contact_messages`
              WHERE ' .
            spm_messages_identifier(
                $idColumn
            ) .
            ' = ?
              LIMIT 1'
        );


        $stmt->execute([
            $selectedId
        ]);


        $detail = $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;


        /*
        |--------------------------------------------------------------------------
        | Otomatis tandai sudah dibaca ketika membuka detail.
        |--------------------------------------------------------------------------
        */

        if (
            $detail
            && $isReadColumn !== null
            && (int)(
                $detail['is_read']
                ?? 0
            ) === 0
        ) {

            try {

                $readSql =
                    'UPDATE `contact_messages`
                     SET ' .
                    spm_messages_identifier(
                        $isReadColumn
                    ) .
                    ' = 1';


                if ($readAtColumn !== null) {

                    $readSql .=
                        ', ' .
                        spm_messages_identifier(
                            $readAtColumn
                        ) .
                        ' = NOW()';
                }


                $readSql .=
                    ' WHERE ' .
                    spm_messages_identifier(
                        $idColumn
                    ) .
                    ' = ?';


                $readStmt = $pdo->prepare(
                    $readSql
                );

                $readStmt->execute([
                    $selectedId
                ]);


                $detail['is_read'] = 1;

            } catch (Throwable $readException) {

                error_log(
                    'StarParts admin message auto-read error: ' .
                    $readException->getMessage()
                );
            }
        }

    } catch (Throwable $e) {

        error_log(
            'StarParts admin messages detail error: ' .
            $e->getMessage()
        );

        $detail = null;
    }
}


/*
|------------------------------------------------------------------
| STATISTICS
|------------------------------------------------------------------
*/

$totalMessages = 0;
$unreadMessages = 0;
$readMessages = 0;


try {

    $totalMessages = (int)$pdo
        ->query(
            'SELECT COUNT(*)
             FROM `contact_messages`'
        )
        ->fetchColumn();


    if ($isReadColumn !== null) {

        $unreadMessages = (int)$pdo
            ->query(
                'SELECT COUNT(*)
                 FROM `contact_messages`
                 WHERE ' .
                spm_messages_identifier(
                    $isReadColumn
                ) .
                ' = 0'
            )
            ->fetchColumn();


        $readMessages = (int)$pdo
            ->query(
                'SELECT COUNT(*)
                 FROM `contact_messages`
                 WHERE ' .
                spm_messages_identifier(
                    $isReadColumn
                ) .
                ' = 1'
            )
            ->fetchColumn();

    } else {

        $unreadMessages = $totalMessages;
        $readMessages = 0;
    }

} catch (Throwable $e) {

    error_log(
        'StarParts admin message stats error: ' .
        $e->getMessage()
    );
}


/*
|------------------------------------------------------------------
| BUILD LIST QUERY
|------------------------------------------------------------------
*/

$messages = [];

$where = [];
$params = [];


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if (
    $statusFilter === 'unread'
    && $isReadColumn !== null
) {

    $where[] =
        spm_messages_identifier(
            $isReadColumn
        ) .
        ' = 0';
}


if (
    $statusFilter === 'read'
    && $isReadColumn !== null
) {

    $where[] =
        spm_messages_identifier(
            $isReadColumn
        ) .
        ' = 1';
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $searchColumns = array_filter([
        $nameColumn,
        $companyColumn,
        $emailColumn,
        $phoneColumn,
        $subjectColumn,
        $messageColumn,
    ]);


    $searchParts = [];


    foreach ($searchColumns as $searchColumn) {

        $searchParts[] =
            spm_messages_identifier(
                $searchColumn
            ) .
            ' LIKE ?';

        $params[] =
            '%' .
            $search .
            '%';
    }


    if (!empty($searchParts)) {

        $where[] =
            '(' .
            implode(
                ' OR ',
                $searchParts
            ) .
            ')';
    }
}


/*
|------------------------------------------------------------------
| SELECT LIST FIELDS
|------------------------------------------------------------------
*/

$listSelect = [
    spm_messages_identifier($idColumn)
    . ' AS id'
];


if ($nameColumn !== null) {

    $listSelect[] =
        spm_messages_identifier(
            $nameColumn
        ) .
        ' AS name';
} else {

    $listSelect[] =
        "'' AS name";
}


if ($companyColumn !== null) {

    $listSelect[] =
        spm_messages_identifier(
            $companyColumn
        ) .
        ' AS company';
} else {

    $listSelect[] =
        "'' AS company";
}


if ($emailColumn !== null) {

    $listSelect[] =
        spm_messages_identifier(
            $emailColumn
        ) .
        ' AS email';
} else {

    $listSelect[] =
        "'' AS email";
}


if ($phoneColumn !== null) {

    $listSelect[] =
        spm_messages_identifier(
            $phoneColumn
        ) .
        ' AS phone';
} else {

    $listSelect[] =
        "'' AS phone";
}


if ($subjectColumn !== null) {

    $listSelect[] =
        spm_messages_identifier(
            $subjectColumn
        ) .
        ' AS subject';
} else {

    $listSelect[] =
        "'' AS subject";
}


if ($messageColumn !== null) {

    $listSelect[] =
        spm_messages_identifier(
            $messageColumn
        ) .
        ' AS message';
} else {

    $listSelect[] =
        "'' AS message";
}


if ($isReadColumn !== null) {

    $listSelect[] =
        spm_messages_identifier(
            $isReadColumn
        ) .
        ' AS is_read';
} else {

    $listSelect[] =
        "0 AS is_read";
}


if ($createdAtColumn !== null) {

    $listSelect[] =
        spm_messages_identifier(
            $createdAtColumn
        ) .
        ' AS created_at';
} else {

    $listSelect[] =
        'NULL AS created_at';
}


/*
|------------------------------------------------------------------
| ORDER
|------------------------------------------------------------------
*/

$orderSql = (
    $createdAtColumn !== null
)
    ? ' ORDER BY ' .
        spm_messages_identifier(
            $createdAtColumn
        ) .
        ' DESC'
    : ' ORDER BY ' .
        spm_messages_identifier(
            $idColumn
        ) .
        ' DESC';


/*
|------------------------------------------------------------------
| PAGINATION
|------------------------------------------------------------------
*/

$perPage = 15;

$page = max(
    1,
    (int)(
        $_GET['page']
        ?? 1
    )
);


$totalFiltered = 0;


try {

    $countSql =
        'SELECT COUNT(*)
         FROM `contact_messages`';


    if (!empty($where)) {

        $countSql .=
            ' WHERE ' .
            implode(
                ' AND ',
                $where
            );
    }


    $countStmt = $pdo->prepare(
        $countSql
    );

    $countStmt->execute(
        $params
    );


    $totalFiltered = (int)$countStmt
        ->fetchColumn();

} catch (Throwable $e) {

    error_log(
        'StarParts admin messages count error: ' .
        $e->getMessage()
    );

    $totalFiltered = 0;
}


$totalPages = max(
    1,
    (int)ceil(
        $totalFiltered /
        $perPage
    )
);


$page = min(
    $page,
    $totalPages
);


$offset = (
    $page - 1
) * $perPage;


/*
|------------------------------------------------------------------
| FETCH DATA
|------------------------------------------------------------------
*/

try {

    $listSql =
        'SELECT ' .
        implode(
            ', ',
            $listSelect
        ) .
        ' FROM `contact_messages`';


    if (!empty($where)) {

        $listSql .=
            ' WHERE ' .
            implode(
                ' AND ',
                $where
            );
    }


    $listSql .=
        $orderSql .
        ' LIMIT ' .
        (int)$perPage .
        ' OFFSET ' .
        (int)$offset;


    $stmt = $pdo->prepare(
        $listSql
    );

    $stmt->execute(
        $params
    );


    $messages = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (Throwable $e) {

    error_log(
        'StarParts admin messages list error: ' .
        $e->getMessage()
    );

    $messages = [];
}


/*
|------------------------------------------------------------------
| URL BUILDER
|------------------------------------------------------------------
*/

$queryBase = [];

if ($search !== '') {
    $queryBase['q'] = $search;
}

if ($statusFilter !== '') {
    $queryBase['status'] = $statusFilter;
}


function spm_messages_page_url(
    int $page,
    array $queryBase
): string {

    $query =
        array_merge(
            $queryBase,
            [
                'page' => $page
            ]
        );


    return base_url(
        'admin/messages.php?' .
        http_build_query(
            $query
        )
    );
}


/*
|------------------------------------------------------------------
| PAGE META
|------------------------------------------------------------------
*/

$pageTitle =
    'Messages | Star Parts Motor';


/*
|------------------------------------------------------------------
| FORMATTERS
|------------------------------------------------------------------
*/

function spm_messages_date(
    mixed $value
): string {

    $value = trim(
        (string)$value
    );


    if ($value === '') {
        return '-';
    }


    $timestamp = strtotime(
        $value
    );


    if ($timestamp === false) {
        return $value;
    }


    return date(
        'd M Y, H:i',
        $timestamp
    );
}


function spm_messages_excerpt(
    string $text,
    int $length = 110
): string {

    $text = trim(
        preg_replace(
            '/\s+/u',
            ' ',
            $text
        ) ?? $text
    );


    if (
        mb_strlen($text) <= $length
    ) {

        return $text;
    }


    return mb_substr(
        $text,
        0,
        $length
    ) .
    '...';
}

?>
<!DOCTYPE html>
<html
    lang="id"
>
<head>

    <meta
        charset="UTF-8"
    >

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= spm_messages_h($pageTitle); ?>
    </title>


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
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        :root {
            --spm-red: #d90000;
            --spm-red-dark: #b30000;
            --spm-black: #111111;
            --spm-dark: #181818;
            --spm-gray: #f5f5f5;
            --spm-border: #e7e7e7;
            --spm-text: #171717;
            --spm-soft: #727272;
            --spm-green: #0c9b62;
        }


        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            margin: 0;
            background: #f6f6f6;
            color: var(--spm-text);
            font-family: Inter, Arial, sans-serif;
            font-size: 14px;
        }


        a {
            color: inherit;
            text-decoration: none;
        }


        button,
        input,
        select {
            font: inherit;
        }


        .admin-shell {
            min-height: 100vh;
        }


        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            background: #111;
            color: #fff;
            box-shadow: 0 4px 18px rgba(0,0,0,.12);
        }


        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            letter-spacing: -.02em;
        }


        .admin-brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--spm-red);
            color: #fff;
        }


        .admin-brand small {
            display: block;
            margin-top: 2px;
            color: rgba(255,255,255,.52);
            font-size: 10px;
            font-weight: 500;
            letter-spacing: .08em;
            text-transform: uppercase;
        }


        .admin-user {
            display: flex;
            align-items: center;
            gap: 14px;
        }


        .admin-user-name {
            text-align: right;
        }


        .admin-user-name strong {
            display: block;
            font-size: 13px;
        }


        .admin-user-name span {
            display: block;
            margin-top: 2px;
            color: rgba(255,255,255,.52);
            font-size: 10px;
            text-transform: uppercase;
        }


        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 12px;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 9px;
            color: #fff;
            background: rgba(255,255,255,.07);
            transition: .2s ease;
        }


        .logout-btn:hover {
            background: var(--spm-red);
            border-color: var(--spm-red);
        }


        .admin-main {
            width: min(1450px, 100%);
            margin: 0 auto;
            padding: 28px;
        }


        .page-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }


        .page-heading-kicker {
            margin-bottom: 7px;
            color: var(--spm-red);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
        }


        .page-heading h1 {
            margin: 0;
            font-size: clamp(25px, 3vw, 34px);
            font-weight: 800;
            letter-spacing: -.04em;
        }


        .page-heading p {
            max-width: 670px;
            margin: 8px 0 0;
            color: var(--spm-soft);
            line-height: 1.7;
        }


        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            padding: 11px 14px;
            border: 1px solid var(--spm-border);
            border-radius: 10px;
            background: #fff;
            font-size: 12px;
            font-weight: 700;
        }


        .back-link:hover {
            border-color: #ccc;
        }


        .flash {
            padding: 14px 16px;
            margin-bottom: 20px;
            border-radius: 11px;
            border: 1px solid;
            font-size: 13px;
            line-height: 1.55;
        }


        .flash.success {
            color: #086942;
            background: #eafaf3;
            border-color: #bcebd6;
        }


        .flash.error {
            color: #a30000;
            background: #fff0f0;
            border-color: #f2c0c0;
        }


        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }


        .stat-card {
            position: relative;
            overflow: hidden;
            padding: 20px;
            background: #fff;
            border: 1px solid var(--spm-border);
            border-radius: 14px;
            box-shadow: 0 8px 26px rgba(0,0,0,.035);
        }


        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 70px;
            height: 70px;
            background: rgba(217,0,0,.055);
            border-radius: 0 0 0 70px;
        }


        .stat-label {
            color: var(--spm-soft);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }


        .stat-value {
            margin-top: 8px;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -.04em;
        }


        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
        }


        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }


        .filter-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 12px;
            border: 1px solid var(--spm-border);
            border-radius: 9px;
            background: #fff;
            color: #4d4d4d;
            font-size: 11px;
            font-weight: 700;
            transition: .2s ease;
        }


        .filter-link:hover,
        .filter-link.active {
            background: var(--spm-black);
            border-color: var(--spm-black);
            color: #fff;
        }


        .search-form {
            display: flex;
            gap: 8px;
            width: min(100%, 390px);
        }


        .search-input {
            flex: 1;
            min-width: 0;
            padding: 11px 13px;
            border: 1px solid var(--spm-border);
            border-radius: 10px;
            background: #fff;
            outline: none;
        }


        .search-input:focus {
            border-color: #bbb;
            box-shadow: 0 0 0 3px rgba(217,0,0,.08);
        }


        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 13px;
            border: 1px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
            transition: .2s ease;
        }


        .btn-dark {
            background: #111;
            color: #fff;
        }


        .btn-dark:hover {
            background: #222;
        }


        .btn-red {
            background: var(--spm-red);
            color: #fff;
        }


        .btn-red:hover {
            background: var(--spm-red-dark);
        }


        .btn-light {
            background: #fff;
            border-color: var(--spm-border);
            color: #333;
        }


        .btn-light:hover {
            border-color: #ccc;
        }


        .btn-danger {
            background: #fff1f1;
            border-color: #f0c3c3;
            color: #b00000;
        }


        .btn-danger:hover {
            background: #ffe6e6;
        }


        .card {
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--spm-border);
            border-radius: 14px;
            box-shadow: 0 8px 26px rgba(0,0,0,.035);
        }


        .table-wrap {
            overflow-x: auto;
        }


        table {
            width: 100%;
            min-width: 920px;
            border-collapse: collapse;
        }


        th,
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            text-align: left;
        }


        th {
            background: #fafafa;
            color: #6b6b6b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }


        tbody tr:hover {
            background: #fcfcfc;
        }


        .sender-name {
            font-weight: 800;
            color: #171717;
        }


        .sender-company {
            margin-top: 4px;
            color: #777;
            font-size: 11px;
        }


        .sender-email {
            margin-top: 4px;
            color: #999;
            font-size: 10px;
            word-break: break-all;
        }


        .subject-text {
            max-width: 250px;
            font-weight: 700;
            line-height: 1.45;
        }


        .message-preview {
            max-width: 350px;
            color: #777;
            line-height: 1.6;
        }


        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }


        .badge-unread {
            background: #fff0f0;
            color: #c00000;
        }


        .badge-read {
            background: #edf8f3;
            color: #08784d;
        }


        .row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }


        .icon-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--spm-border);
            border-radius: 8px;
            background: #fff;
            color: #444;
            cursor: pointer;
            transition: .2s ease;
        }


        .icon-btn:hover {
            border-color: #bbb;
            background: #f7f7f7;
        }


        .icon-btn.red {
            color: var(--spm-red);
        }


        .icon-btn.danger {
            color: #b00000;
        }


        .empty-state {
            padding: 65px 30px;
            text-align: center;
        }


        .empty-state-icon {
            width: 62px;
            height: 62px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: #f7f7f7;
            color: #aaa;
            font-size: 22px;
        }


        .empty-state h3 {
            margin: 0 0 7px;
            font-size: 17px;
        }


        .empty-state p {
            margin: 0 auto;
            max-width: 450px;
            color: #898989;
            font-size: 12px;
            line-height: 1.7;
        }


        .pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            padding: 18px;
            border-top: 1px solid #eee;
        }


        .page-link {
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--spm-border);
            border-radius: 8px;
            background: #fff;
            font-size: 11px;
            font-weight: 700;
        }


        .page-link:hover,
        .page-link.active {
            border-color: var(--spm-black);
            background: var(--spm-black);
            color: #fff;
        }


        .detail-wrap {
            margin-top: 20px;
        }


        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1.25fr;
            gap: 18px;
        }


        .detail-card {
            padding: 22px;
            background: #fff;
            border: 1px solid var(--spm-border);
            border-radius: 14px;
            box-shadow: 0 8px 26px rgba(0,0,0,.035);
        }


        .detail-card h2 {
            margin: 0 0 18px;
            font-size: 18px;
            letter-spacing: -.02em;
        }


        .detail-item {
            padding: 13px 0;
            border-bottom: 1px solid #eee;
        }


        .detail-item:last-child {
            border-bottom: 0;
        }


        .detail-label {
            margin-bottom: 5px;
            color: #999;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }


        .detail-value {
            color: #242424;
            font-size: 13px;
            line-height: 1.65;
            word-break: break-word;
        }


        .detail-message {
            white-space: pre-wrap;
        }


        .detail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }


        .company-highlight {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            padding: 5px 7px;
            border-radius: 7px;
            background: #f7f7f7;
            color: #666;
            font-size: 10px;
            font-weight: 700;
        }


        @media (max-width: 900px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .admin-topbar {
                padding: 0 16px;
            }

            .admin-main {
                padding: 20px 16px;
            }

            .admin-user-name {
                display: none;
            }

        }


        @media (max-width: 640px) {

            .page-heading {
                flex-direction: column;
            }

            .toolbar {
                align-items: stretch;
            }

            .search-form {
                width: 100%;
            }

            .logout-btn span {
                display: none;
            }

            .admin-brand small {
                display: none;
            }

        }

    </style>

</head>

<body>
<?php render_admin_sidebar_assets(); ?>
<?php render_admin_sidebar('messages.php'); ?>


<div class="admin-shell">


    <!-- ======================================================
         TOP BAR
    ======================================================= -->

    <header class="admin-topbar">

        <a
            href="<?= spm_messages_h(base_url('admin/dashboard.php')); ?>"
            class="admin-brand"
        >

            <span class="admin-brand-mark">
                <i class="fa-solid fa-star"></i>
            </span>


            <span>
                Star Parts Motor

                <small>
                    Administration
                </small>
            </span>

        </a>


        <div class="admin-user">

            <div class="admin-user-name">

                <strong>
                    <?= spm_messages_h(
                        $admin['name']
                        ?? $admin['username']
                        ?? 'Administrator'
                    ); ?>
                </strong>

                <span>
                    Administrator
                </span>

            </div>


            <a
                href="<?= spm_messages_h(base_url('admin/logout.php')); ?>"
                class="logout-btn"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>
                    Keluar
                </span>

            </a>

        </div>

    </header>


    <!-- ======================================================
         MAIN
    ======================================================= -->

    <main class="admin-main">


        <!-- ==================================================
             HEADER
        =================================================== -->

        <div class="page-heading">

            <div>

                <div class="page-heading-kicker">
                    Customer Communication
                </div>


                <h1>
                    Pesan Masuk
                </h1>


                <p>
                    Kelola pesan yang dikirim melalui formulir kontak
                    website Star Parts Motor.
                </p>

            </div>


            <a
                href="<?= spm_messages_h(base_url('admin/dashboard.php')); ?>"
                class="back-link"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Dashboard

            </a>

        </div>


        <!-- ==================================================
             FLASH
        =================================================== -->

        <?php if (
            is_array($flash)
            && !empty($flash['message'])
        ): ?>

            <div
                class="flash <?= (
                    ($flash['type'] ?? 'success')
                    === 'error'
                ) ? 'error' : 'success'; ?>"
            >

                <?= spm_messages_h(
                    $flash['message']
                ); ?>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             STATISTICS
        =================================================== -->

        <div class="stats-grid">


            <div class="stat-card">

                <div class="stat-label">
                    Total Pesan
                </div>

                <div class="stat-value">
                    <?= number_format(
                        $totalMessages,
                        0,
                        ',',
                        '.'
                    ); ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Belum Dibaca
                </div>

                <div
                    class="stat-value"
                    style="color:var(--spm-red);"
                >
                    <?= number_format(
                        $unreadMessages,
                        0,
                        ',',
                        '.'
                    ); ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Sudah Dibaca
                </div>

                <div
                    class="stat-value"
                    style="color:var(--spm-green);"
                >
                    <?= number_format(
                        $readMessages,
                        0,
                        ',',
                        '.'
                    ); ?>
                </div>

            </div>


        </div>


        <!-- ==================================================
             FILTER TOOLBAR
        =================================================== -->

        <div class="toolbar">


            <div class="filters">

                <a
                    href="<?= spm_messages_h(
                        base_url(
                            'admin/messages.php'
                        )
                    ); ?>"
                    class="filter-link <?= (
                        $statusFilter === ''
                    ) ? 'active' : ''; ?>"
                >

                    Semua

                </a>


                <a
                    href="<?= spm_messages_h(
                        spm_messages_page_url(
                            1,
                            ['status' => 'unread']
                        )
                    ); ?>"
                    class="filter-link <?= (
                        $statusFilter === 'unread'
                    ) ? 'active' : ''; ?>"
                >

                    Belum Dibaca

                </a>


                <a
                    href="<?= spm_messages_h(
                        spm_messages_page_url(
                            1,
                            ['status' => 'read']
                        )
                    ); ?>"
                    class="filter-link <?= (
                        $statusFilter === 'read'
                    ) ? 'active' : ''; ?>"
                >

                    Sudah Dibaca

                </a>

            </div>


            <form
                method="GET"
                class="search-form"
            >

                <?php if ($statusFilter !== ''): ?>

                    <input
                        type="hidden"
                        name="status"
                        value="<?= spm_messages_h(
                            $statusFilter
                        ); ?>"
                    >

                <?php endif; ?>


                <input
                    type="search"
                    name="q"
                    value="<?= spm_messages_h(
                        $search
                    ); ?>"
                    class="search-input"
                    placeholder="Cari nama, perusahaan, email, subjek..."
                >


                <button
                    type="submit"
                    class="btn btn-dark"
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                    Cari

                </button>


                <?php if ($search !== ''): ?>

                    <a
                        href="<?= spm_messages_h(
                            base_url(
                                'admin/messages.php'
                            )
                        ); ?>"
                        class="btn btn-light"
                    >

                        <i class="fa-solid fa-xmark"></i>

                    </a>

                <?php endif; ?>

            </form>


        </div>


        <!-- ==================================================
             DETAIL
        =================================================== -->

        <?php if (
            isset($_GET['view'])
            && $selectedId > 0
        ): ?>


            <div class="detail-wrap">


                <?php if ($detail): ?>


                    <div class="detail-grid">


                        <!-- ==================================
                             CUSTOMER INFO
                        =================================== -->

                        <div class="detail-card">

                            <h2>
                                Informasi Pengirim
                            </h2>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Nama
                                </div>

                                <div class="detail-value">
                                    <?= spm_messages_h(
                                        $detail['name']
                                    ); ?>
                                </div>

                            </div>


                            <?php if (
                                trim(
                                    (string)$detail['company']
                                ) !== ''
                            ): ?>

                                <div class="detail-item">

                                    <div class="detail-label">
                                        Perusahaan
                                    </div>

                                    <div class="detail-value">

                                        <span class="company-highlight">

                                            <i class="fa-solid fa-building"></i>

                                            <?= spm_messages_h(
                                                $detail['company']
                                            ); ?>

                                        </span>

                                    </div>

                                </div>

                            <?php endif; ?>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Email
                                </div>

                                <div class="detail-value">

                                    <?php if (
                                        trim(
                                            (string)$detail['email']
                                        ) !== ''
                                    ): ?>

                                        <a
                                            href="mailto:<?= spm_messages_h(
                                                $detail['email']
                                            ); ?>"
                                            style="color:var(--spm-red);font-weight:700;"
                                        >
                                            <?= spm_messages_h(
                                                $detail['email']
                                            ); ?>
                                        </a>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Telepon
                                </div>

                                <div class="detail-value">

                                    <?php if (
                                        trim(
                                            (string)$detail['phone']
                                        ) !== ''
                                    ): ?>

                                        <?= spm_messages_h(
                                            $detail['phone']
                                        ); ?>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Diterima
                                </div>

                                <div class="detail-value">
                                    <?= spm_messages_h(
                                        spm_messages_date(
                                            $detail['created_at']
                                        )
                                    ); ?>
                                </div>

                            </div>


                            <div class="detail-actions">


                                <?php if (
                                    trim(
                                        (string)$detail['email']
                                    ) !== ''
                                ): ?>

                                    <a
                                        href="mailto:<?= spm_messages_h(
                                            $detail['email']
                                        ); ?>?subject=Re:%20<?= rawurlencode(
                                            (string)$detail['subject']
                                        ); ?>"
                                        class="btn btn-light"
                                    >

                                        <i class="fa-solid fa-envelope"></i>

                                        Email

                                    </a>

                                <?php endif; ?>


                                <?php

                                $detailPhone =
                                    spm_messages_normalize_phone(
                                        (string)(
                                            $detail['phone']
                                            ?? ''
                                        )
                                    );


                                $detailWaMessage =
                                    "Halo " .
                                    (
                                        trim(
                                            (string)$detail['name']
                                        ) !== ''
                                            ? (string)$detail['name']
                                            : 'Bapak/Ibu'
                                    ) .
                                    ",\n\n" .
                                    "Terima kasih telah menghubungi Star Parts Motor.\n" .
                                    "Kami ingin menindaklanjuti pesan Anda" .
                                    (
                                        trim(
                                            (string)$detail['subject']
                                        ) !== ''
                                            ? " mengenai \"" .
                                                (string)$detail['subject'] .
                                                "\""
                                            : ''
                                    ) .
                                    ".\n\n" .
                                    "Terima kasih.";

                                ?>


                                <?php if (
                                    $detailPhone !== ''
                                ): ?>

                                    <a
                                        href="https://wa.me/<?= spm_messages_h(
                                            $detailPhone
                                        ); ?>?text=<?= rawurlencode(
                                            $detailWaMessage
                                        ); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-red"
                                    >

                                        <i class="fa-brands fa-whatsapp"></i>

                                        WhatsApp

                                    </a>

                                <?php endif; ?>


                                <?php if (
                                    (int)(
                                        $detail['is_read']
                                        ?? 0
                                    ) === 1
                                    && $isReadColumn !== null
                                ): ?>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <?= csrf_input(); ?>

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="mark_unread"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$detail['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-light"
                                        >

                                            <i class="fa-regular fa-envelope"></i>

                                            Belum Dibaca

                                        </button>

                                    </form>

                                <?php endif; ?>


                                <a
                                    href="<?= spm_messages_h(
                                        spm_messages_page_url(
                                            1,
                                            array_filter(
                                                [
                                                    'q' => $search,
                                                    'status' => $statusFilter
                                                ],
                                                static fn(
                                                    mixed $value
                                                ): bool => $value !== ''
                                            )
                                        )
                                    ); ?>"
                                    class="btn btn-light"
                                >

                                    <i class="fa-solid fa-arrow-left"></i>

                                    Kembali

                                </a>


                            </div>

                        </div>


                        <!-- ==================================
                             MESSAGE
                        =================================== -->

                        <div class="detail-card">

                            <h2>
                                Isi Pesan
                            </h2>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Subjek
                                </div>

                                <div
                                    class="detail-value"
                                    style="font-weight:800;"
                                >
                                    <?= spm_messages_h(
                                        trim(
                                            (string)$detail['subject']
                                        ) !== ''
                                            ? $detail['subject']
                                            : 'Pertanyaan dari Website'
                                    ); ?>
                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Pesan
                                </div>

                                <div class="detail-value detail-message">
                                    <?= spm_messages_h(
                                        $detail['message']
                                    ); ?>
                                </div>

                            </div>


                        </div>


                    </div>


                <?php else: ?>


                    <div class="card">

                        <div class="empty-state">

                            <div class="empty-state-icon">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>


                            <h3>
                                Pesan tidak ditemukan
                            </h3>


                            <p>
                                Data pesan yang diminta tidak tersedia
                                atau mungkin sudah dihapus.
                            </p>

                        </div>

                    </div>


                <?php endif; ?>


            </div>


        <?php endif; ?>


        <!-- ==================================================
             MESSAGE TABLE
        =================================================== -->

        <div class="card">


            <?php if (
                !empty($messages)
            ): ?>


                <div class="table-wrap">


                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Pengirim
                                </th>

                                <th>
                                    Subjek
                                </th>

                                <th>
                                    Pesan
                                </th>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $messages
                            as $row
                        ): ?>


                            <?php

                            $rowId =
                                (int)(
                                    $row['id']
                                    ?? 0
                                );


                            $rowIsRead =
                                (int)(
                                    $row['is_read']
                                    ?? 0
                                ) === 1;


                            $rowViewParams = [
                                'view' => 1,
                                'id' => $rowId
                            ];


                            if (
                                $search !== ''
                            ) {

                                $rowViewParams['q'] =
                                    $search;
                            }


                            if (
                                $statusFilter !== ''
                            ) {

                                $rowViewParams['status'] =
                                    $statusFilter;
                            }


                            $rowViewUrl =
                                base_url(
                                    'admin/messages.php?' .
                                    http_build_query(
                                        $rowViewParams
                                    )
                                );


                            $rowPhone =
                                spm_messages_normalize_phone(
                                    (string)(
                                        $row['phone']
                                        ?? ''
                                    )
                                );


                            $rowWaMessage =
                                "Halo " .
                                (
                                    trim(
                                        (string)(
                                            $row['name']
                                            ?? ''
                                        )
                                    ) !== ''
                                        ? (string)$row['name']
                                        : 'Bapak/Ibu'
                                ) .
                                ",\n\n" .
                                "Terima kasih telah menghubungi Star Parts Motor." .
                                "\n\n" .
                                "Tim kami akan menindaklanjuti pesan Anda.";

                            ?>


                            <tr>


                                <!-- ==========================
                                     SENDER
                                =========================== -->

                                <td>

                                    <div class="sender-name">

                                        <?= spm_messages_h(
                                            $row['name']
                                        ); ?>

                                    </div>


                                    <?php if (
                                        trim(
                                            (string)$row['company']
                                        ) !== ''
                                    ): ?>

                                        <div class="sender-company">

                                            <i
                                                class="fa-solid fa-building"
                                                style="font-size:9px;"
                                            ></i>

                                            <?= spm_messages_h(
                                                $row['company']
                                            ); ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (
                                        trim(
                                            (string)$row['email']
                                        ) !== ''
                                    ): ?>

                                        <div class="sender-email">

                                            <?= spm_messages_h(
                                                $row['email']
                                            ); ?>

                                        </div>

                                    <?php endif; ?>


                                </td>


                                <!-- ==========================
                                     SUBJECT
                                =========================== -->

                                <td>

                                    <div class="subject-text">

                                        <?= spm_messages_h(
                                            trim(
                                                (string)(
                                                    $row['subject']
                                                    ?? ''
                                                )
                                            ) !== ''
                                                ? $row['subject']
                                                : 'Pertanyaan dari Website'
                                        ); ?>

                                    </div>

                                </td>


                                <!-- ==========================
                                     MESSAGE
                                =========================== -->

                                <td>

                                    <div class="message-preview">

                                        <?= spm_messages_h(
                                            spm_messages_excerpt(
                                                (string)(
                                                    $row['message']
                                                    ?? ''
                                                )
                                            )
                                        ); ?>

                                    </div>

                                </td>


                                <!-- ==========================
                                     DATE
                                =========================== -->

                                <td>

                                    <span
                                        style="font-size:11px;color:#666;white-space:nowrap;"
                                    >
                                        <?= spm_messages_h(
                                            spm_messages_date(
                                                $row['created_at']
                                            )
                                        ); ?>
                                    </span>

                                </td>


                                <!-- ==========================
                                     STATUS
                                =========================== -->

                                <td>

                                    <?php if (
                                        $rowIsRead
                                    ): ?>

                                        <span
                                            class="badge badge-read"
                                        >

                                            <i class="fa-solid fa-check"></i>

                                            Dibaca

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge badge-unread"
                                        >

                                            <i class="fa-solid fa-circle"></i>

                                            Baru

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ==========================
                                     ACTION
                                =========================== -->

                                <td>

                                    <div class="row-actions">


                                        <a
                                            href="<?= spm_messages_h(
                                                $rowViewUrl
                                            ); ?>"
                                            class="icon-btn"
                                            title="Lihat pesan"
                                        >

                                            <i class="fa-regular fa-eye"></i>

                                        </a>


                                        <?php if (
                                            $rowPhone !== ''
                                        ): ?>

                                            <a
                                                href="https://wa.me/<?= spm_messages_h(
                                                    $rowPhone
                                                ); ?>?text=<?= rawurlencode(
                                                    $rowWaMessage
                                                ); ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="icon-btn red"
                                                title="WhatsApp"
                                            >

                                                <i class="fa-brands fa-whatsapp"></i>

                                            </a>

                                        <?php endif; ?>


                                        <?php if (
                                            trim(
                                                (string)$row['email']
                                            ) !== ''
                                        ): ?>

                                            <a
                                                href="mailto:<?= spm_messages_h(
                                                    $row['email']
                                                ); ?>"
                                                class="icon-btn"
                                                title="Email"
                                            >

                                                <i class="fa-regular fa-envelope"></i>

                                            </a>

                                        <?php endif; ?>


                                        <?php if (
                                            $isReadColumn !== null
                                        ): ?>

                                            <form
                                                method="POST"
                                                style="display:inline;"
                                            >

                                                <?= csrf_input(); ?>


                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= $rowId; ?>"
                                                >


                                                <?php if (
                                                    $rowIsRead
                                                ): ?>

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="mark_unread"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="icon-btn"
                                                        title="Tandai belum dibaca"
                                                    >

                                                        <i class="fa-regular fa-envelope"></i>

                                                    </button>

                                                <?php else: ?>

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="mark_read"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="icon-btn"
                                                        title="Tandai sudah dibaca"
                                                    >

                                                        <i class="fa-solid fa-check"></i>

                                                    </button>

                                                <?php endif; ?>

                                            </form>

                                        <?php endif; ?>


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirm('Hapus pesan ini? Data yang sudah dihapus tidak dapat dikembalikan.');"
                                        >

                                            <?= csrf_input(); ?>


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >


                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= $rowId; ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="icon-btn danger"
                                                title="Hapus pesan"
                                            >

                                                <i class="fa-regular fa-trash-can"></i>

                                            </button>

                                        </form>


                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>

                    </table>


                </div>


                <!-- ==========================================
                     PAGINATION
                =========================================== -->

                <?php if (
                    $totalPages > 1
                ): ?>


                    <div class="pagination">


                        <?php if (
                            $page > 1
                        ): ?>

                            <a
                                href="<?= spm_messages_h(
                                    spm_messages_page_url(
                                        $page - 1,
                                        $queryBase
                                    )
                                ); ?>"
                                class="page-link"
                            >

                                <i class="fa-solid fa-chevron-left"></i>

                            </a>

                        <?php endif; ?>


                        <?php

                        $startPage = max(
                            1,
                            $page - 2
                        );


                        $endPage = min(
                            $totalPages,
                            $page + 2
                        );

                        ?>


                        <?php for (
                            $paginationPage = $startPage;
                            $paginationPage <= $endPage;
                            $paginationPage++
                        ): ?>

                            <a
                                href="<?= spm_messages_h(
                                    spm_messages_page_url(
                                        $paginationPage,
                                        $queryBase
                                    )
                                ); ?>"
                                class="page-link <?= (
                                    $paginationPage === $page
                                ) ? 'active' : ''; ?>"
                            >

                                <?= $paginationPage; ?>

                            </a>

                        <?php endfor; ?>


                        <?php if (
                            $page < $totalPages
                        ): ?>

                            <a
                                href="<?= spm_messages_h(
                                    spm_messages_page_url(
                                        $page + 1,
                                        $queryBase
                                    )
                                ); ?>"
                                class="page-link"
                            >

                                <i class="fa-solid fa-chevron-right"></i>

                            </a>

                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            <?php else: ?>


                <div class="empty-state">

                    <div class="empty-state-icon">

                        <i class="fa-regular fa-envelope"></i>

                    </div>


                    <h3>
                        Belum ada pesan
                    </h3>


                    <p>

                        Belum ditemukan pesan yang sesuai dengan
                        filter atau pencarian Anda.

                    </p>

                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>
</html>
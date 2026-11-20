<?php

declare(strict_types=1);

function admin_sidebar_items(): array
{
    $items = [
        ['dashboard.php', 'fa-solid fa-gauge-high', 'Dashboard', null],
        ['banners.php', 'fa-solid fa-images', 'Banner', null],
        ['products.php', 'fa-solid fa-box-open', 'Produk', null],
        ['fdr-details.php', 'fa-solid fa-motorcycle', 'Konten Detail Ban', null],
        ['categories.php', 'fa-solid fa-layer-group', 'Kategori', null],
        ['brands.php', 'fa-solid fa-tags', 'Brand', null],
        ['posts.php', 'fa-solid fa-newspaper', 'Berita', null],
        ['media.php', 'fa-solid fa-photo-film', 'Media', null],
        ['locations.php', 'fa-solid fa-location-dot', 'Lokasi', null],
        ['messages.php', 'fa-solid fa-envelope', 'Pesan', null],
    ];

    $websiteItems = [
        ['pages.php', 'fa-regular fa-file-lines', 'Halaman', null],
        ['careers.php', 'fa-solid fa-briefcase', 'Lowongan Karir', null],
        ['menus.php', 'fa-solid fa-bars', 'Menu Website', null],
        ['settings.php', 'fa-solid fa-sliders', 'Pengaturan', null],
        ['testimonials.php', 'fa-solid fa-comment-dots', 'Testimoni', null],
        ['security.php', 'fa-solid fa-shield-halved', 'Security', null],
    ];

    $admin = function_exists('current_admin') ? current_admin() : null;
    if (strtolower((string) ($admin['role'] ?? $_SESSION['admin_role'] ?? 'admin')) === 'superadmin') {
        $websiteItems[] = ['users.php', 'fa-solid fa-users-gear', 'Admin Users', null];
    }

    return [$items, $websiteItems];
}

function render_admin_sidebar(string $activePage): void
{
    [$items, $websiteItems] = admin_sidebar_items();
    $siteName = trim((string) get_setting('site_name', 'Star Parts Motor'));
    $logoUrl = '';

    if (function_exists('media_url')) {
        $logo = trim((string) get_setting('logo', ''));
        $logoUrl = $logo !== '' ? media_url($logo) : '';
    }

    ?>
    <button
        type="button"
        class="spm-admin-sidebar-toggle"
        id="spm-admin-sidebar-toggle"
        aria-controls="spm-admin-sidebar"
        aria-expanded="true"
        aria-label="Tutup sidebar"
        title="Tutup atau buka sidebar"
    >
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>

    <aside class="spm-admin-sidebar" id="spm-admin-sidebar">
        <div class="spm-admin-sidebar-brand">
            <a href="<?= e(base_url('admin/dashboard.php')); ?>">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?= e($logoUrl); ?>" alt="<?= e($siteName); ?>">
                <?php else: ?>
                    <span class="spm-admin-brand-mark">SPM</span>
                    <span>
                        <strong><?= e($siteName); ?></strong>
                        <small>Administration Panel</small>
                    </span>
                <?php endif; ?>
            </a>
        </div>

        <nav class="spm-admin-sidebar-nav" aria-label="Navigasi admin">
            <span class="spm-admin-sidebar-label">Main Menu</span>
            <?php foreach ($items as [$page, $icon, $label]): ?>
                <a
                    href="<?= e(base_url('admin/' . $page)); ?>"
                    class="spm-admin-sidebar-link<?= $activePage === $page ? ' active' : ''; ?>"
                    <?= $activePage === $page ? 'aria-current="page"' : ''; ?>
                >
                    <i class="<?= e($icon); ?>" aria-hidden="true"></i>
                    <span><?= e($label); ?></span>
                </a>
            <?php endforeach; ?>

            <span class="spm-admin-sidebar-label">Website</span>
            <?php foreach ($websiteItems as [$page, $icon, $label]): ?>
                <a
                    href="<?= e(base_url('admin/' . $page)); ?>"
                    class="spm-admin-sidebar-link<?= $activePage === $page ? ' active' : ''; ?>"
                    <?= $activePage === $page ? 'aria-current="page"' : ''; ?>
                >
                    <i class="<?= e($icon); ?>" aria-hidden="true"></i>
                    <span><?= e($label); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="spm-admin-sidebar-overlay" id="spm-admin-sidebar-overlay"></div>
    <?php
}

function render_admin_sidebar_assets(): void
{
    ?>
    <style>
        .spm-admin-sidebar-toggle {
            position: fixed;
            z-index: 100;
            left: 276px;
            top: 18px;
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            color: #111827;
            box-shadow: 0 8px 20px rgba(15,23,42,.12);
            cursor: pointer;
            transition: left .2s ease, background .15s ease, color .15s ease;
        }
        .spm-admin-sidebar-toggle:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
        .spm-admin-sidebar {
            position: fixed;
            z-index: 80;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            display: flex;
            flex-direction: column;
            background: #111827;
            color: #fff;
            border-right: 1px solid #1f2937;
        }
        .spm-admin-sidebar-brand { height: 78px; flex: 0 0 78px; display: flex; align-items: center; padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .spm-admin-sidebar-brand a { display: flex; align-items: center; gap: 11px; color: #fff; text-decoration: none; min-width: 0; }
        .spm-admin-sidebar-brand img { max-width: 218px; max-height: 48px; object-fit: contain; }
        .spm-admin-sidebar-brand strong, .spm-admin-sidebar-brand small { display: block; }
        .spm-admin-sidebar-brand strong { font-size: 14px; line-height: 1.2; }
        .spm-admin-sidebar-brand small { margin-top: 4px; color: #9ca3af; font-size: 10px; }
        .spm-admin-brand-mark { width: 42px; height: 42px; flex: 0 0 42px; display: grid; place-items: center; border-radius: 10px; background: #dc2626; font-weight: 900; }
        .spm-admin-sidebar-nav { flex: 1; min-height: 0; overflow-y: auto; padding: 14px 12px 24px; scrollbar-width: thin; scrollbar-color: #374151 transparent; }
        .spm-admin-sidebar-label { display: block; margin: 10px 10px 7px; color: #6b7280; font-size: 10px; line-height: 1; text-transform: uppercase; letter-spacing: .12em; font-weight: 800; }
        .spm-admin-sidebar-label:not(:first-child) { margin-top: 18px; }
        .spm-admin-sidebar-link { min-height: 42px; display: flex; align-items: center; gap: 11px; margin: 3px 0; padding: 10px 12px; border-radius: 9px; color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 700; transition: background .15s ease, color .15s ease; }
        .spm-admin-sidebar-link i { width: 18px; text-align: center; color: #9ca3af; }
        .spm-admin-sidebar-link:hover { background: rgba(255,255,255,.07); color: #fff; }
        .spm-admin-sidebar-link.active { background: #dc2626; color: #fff; box-shadow: 0 8px 18px rgba(220,38,38,.18); }
        .spm-admin-sidebar-link.active i { color: #fff; }
        .spm-admin-sidebar-overlay { display: none; }

        /* Legacy sidebars are hidden while pages migrate to this shared template. */
        aside.admin-sidebar,
        aside.sidebar,
        aside.side {
            display: none !important;
        }
        @media (min-width: 901px) {
            .admin-main,
            .main,
            .content {
                margin-left: 260px;
            }
            body > .wrap,
            .wrap.spm-shift,
            .security-wrap {
                margin-left: 260px;
                width: calc(100% - 260px);
            }
            .admin-layout,
            .admin-app,
            .app {
                min-width: 0;
            }
            body.spm-sidebar-collapsed .spm-admin-sidebar {
                transform: translateX(-100%);
            }
            body.spm-sidebar-collapsed .spm-admin-sidebar-toggle {
                left: 18px;
            }
            body.spm-sidebar-collapsed .admin-main,
            body.spm-sidebar-collapsed .main,
            body.spm-sidebar-collapsed .content,
            body.spm-sidebar-collapsed > .wrap,
            body.spm-sidebar-collapsed .wrap.spm-shift,
            body.spm-sidebar-collapsed .security-wrap {
                margin-left: 0;
                width: 100%;
            }
        }

        @media (max-width: 900px) {
            .spm-admin-sidebar-toggle {
                left: 14px;
                top: 14px;
            }
            .spm-admin-sidebar { transform: translateX(-100%); transition: transform .2s ease; box-shadow: 18px 0 40px rgba(15,23,42,.18); }
            .spm-admin-sidebar.open { transform: translateX(0); }
            .spm-admin-sidebar-overlay.show { display: block; position: fixed; z-index: 79; inset: 0; background: rgba(15,23,42,.45); }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sidebar = document.getElementById('spm-admin-sidebar');
            var overlay = document.getElementById('spm-admin-sidebar-overlay');
            var toggle = document.getElementById('spm-admin-sidebar-toggle');
            if (!sidebar) return;

            if (!window.matchMedia('(max-width: 900px)').matches) {
                var collapsed = false;
                try {
                    collapsed = localStorage.getItem('spm-admin-sidebar-collapsed') === 'true';
                } catch (error) {}
                document.body.classList.toggle('spm-sidebar-collapsed', collapsed);
                if (toggle) {
                    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    toggle.setAttribute('aria-label', collapsed ? 'Buka sidebar' : 'Tutup sidebar');
                }
            } else if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Buka sidebar');
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('show');
                if (window.matchMedia('(max-width: 900px)').matches) {
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                    if (toggle) toggle.setAttribute('aria-label', 'Buka sidebar');
                }
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    var isMobile = window.matchMedia('(max-width: 900px)').matches;
                    var isOpen;

                    if (isMobile) {
                        isOpen = sidebar.classList.toggle('open');
                        if (overlay) overlay.classList.toggle('show', isOpen);
                    } else {
                        isOpen = document.body.classList.toggle('spm-sidebar-collapsed');
                        isOpen = !isOpen;
                        try {
                            localStorage.setItem('spm-admin-sidebar-collapsed', isOpen ? 'false' : 'true');
                        } catch (error) {}
                    }

                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    toggle.setAttribute('aria-label', isOpen ? 'Tutup sidebar' : 'Buka sidebar');
                });
            }
            if (overlay) overlay.addEventListener('click', closeSidebar);
            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeSidebar);
            });
        });
    </script>
    <?php
}

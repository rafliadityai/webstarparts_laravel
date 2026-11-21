/* ============================================================
 * FILE   : app.js
 * LOKASI : starparts/assets/js/app.js
 * ============================================================
 *
 * JavaScript global website Star Parts Motor.
 *
 * Menangani:
 * - Preloader
 * - Sticky header
 * - Mobile navigation
 * - Mobile submenu
 * - Back to top
 * - Smooth scrolling
 * - Swiper slider
 * - AOS animation
 * - Counter animation
 * - Lazy media
 * - Form UX
 * - Prevent double submit
 * - Image fallback
 * - External link safety
 * - WhatsApp helper
 * - Clipboard helper
 * - Debounce / throttle
 * - Vue 3 enhancement
 *
 * Tidak menggunakan framework sebagai pengganti PHP.
 * Vue hanya menjadi enhancement layer pada elemen
 * yang memang secara eksplisit menggunakan [data-vue-app].
 * ============================================================ */

(function (window, document) {

    'use strict';


    /* =========================================================
     * GLOBAL OBJECT
     * ========================================================= */

    const StarParts = {};


    /* =========================================================
     * CONFIG
     * ========================================================= */

    StarParts.config = {

        selectors: {

            header: '#site-header',

            mobileToggle: '#mobile-menu-toggle',

            mobileNavigation: '#mobile-navigation',

            mobileNavigationClose:
                '#mobile-navigation-close',

            mobileOverlay:
                '#mobile-nav-overlay',

            backToTop:
                '#back-to-top',

            preloader:
                '#sp-preloader',

            contactForm:
                '[data-contact-form]',

            counter:
                '[data-counter]',

            swiper:
                '.swiper',

            aos:
                '[data-aos]',

        },

        classes: {

            scrolled:
                'is-scrolled',

            menuOpen:
                'menu-open',

            active:
                'active',

            visible:
                'is-visible',

            hidden:
                'is-hidden',

        },

        scrollOffset: 90,

    };


    /* =========================================================
     * UTILITY
     * ========================================================= */

    StarParts.$ = function (selector, scope) {

        return (scope || document).querySelector(
            selector
        );

    };


    StarParts.$$ = function (selector, scope) {

        return Array.prototype.slice.call(
            (scope || document).querySelectorAll(
                selector
            )
        );

    };


    StarParts.on = function (
        element,
        event,
        handler,
        options
    ) {

        if (!element) {
            return;
        }

        element.addEventListener(
            event,
            handler,
            options || false
        );

    };


    StarParts.dispatch = function (
        name,
        detail
    ) {

        try {

            document.dispatchEvent(
                new CustomEvent(
                    name,
                    {
                        detail:
                            detail || {}
                    }
                )
            );

        } catch (error) {

            /* Browser lama: abaikan. */

        }

    };


    /* =========================================================
     * DOM READY
     * ========================================================= */

    StarParts.ready = function (callback) {

        if (
            document.readyState ===
            'loading'
        ) {

            document.addEventListener(
                'DOMContentLoaded',
                callback,
                {
                    once: true
                }
            );

        } else {

            callback();

        }

    };


    /* =========================================================
     * PRELOADER
     * ========================================================= */

    StarParts.initPreloader = function () {

        const preloader =
            StarParts.$(
                StarParts.config.selectors.preloader
            );


        if (!preloader) {
            return;
        }


        const hidePreloader = function () {

            preloader.classList.add(
                StarParts.config.classes.hidden
            );


            window.setTimeout(
                function () {

                    if (
                        preloader &&
                        preloader.parentNode
                    ) {

                        preloader.parentNode.removeChild(
                            preloader
                        );

                    }

                },
                500
            );

        };


        if (
            document.readyState ===
            'complete'
        ) {

            window.setTimeout(
                hidePreloader,
                180
            );

        } else {

            window.addEventListener(
                'load',
                function () {

                    window.setTimeout(
                        hidePreloader,
                        180
                    );

                },
                {
                    once: true
                }
            );

        }

    };


    /* =========================================================
     * HEADER
     * ========================================================= */

    StarParts.initHeader = function () {

        const header =
            StarParts.$(
                StarParts.config.selectors.header
            );


        if (!header) {
            return;
        }


        const updateHeader =
            function () {

                if (
                    window.scrollY >
                    20
                ) {

                    header.classList.add(
                        StarParts.config.classes.scrolled
                    );

                } else {

                    header.classList.remove(
                        StarParts.config.classes.scrolled
                    );

                }

            };


        updateHeader();


        StarParts.on(
            window,
            'scroll',
            updateHeader,
            {
                passive: true
            }
        );

    };


    /* =========================================================
     * MOBILE NAVIGATION
     * ========================================================= */

    StarParts.initMobileMenu = function () {

        const toggle =
            StarParts.$(
                StarParts.config.selectors.mobileToggle
            );


        const navigation =
            StarParts.$(
                StarParts.config.selectors.mobileNavigation
            );


        const closeButton =
            StarParts.$(
                StarParts.config.selectors.mobileNavigationClose
            );


        const overlay =
            StarParts.$(
                StarParts.config.selectors.mobileOverlay
            );


        if (
            !toggle ||
            !navigation
        ) {

            return;

        }


        const setOpen =
            function (open) {

                // Keep the JavaScript state aligned with the CSS selectors.
                navigation.classList.toggle(
                    'is-open',
                    open
                );

                // Reset expanded submenus whenever the whole drawer closes.
                if (!open) {
                    StarParts.$$('.mobile-navigation-item.submenu-open', navigation)
                        .forEach(function (item) {
                            item.classList.remove('submenu-open');
                            const submenuToggle = item.querySelector('.mobile-submenu-toggle');
                            if (submenuToggle) {
                                submenuToggle.classList.remove('active');
                                submenuToggle.setAttribute('aria-expanded', 'false');
                            }
                        });
                }

                toggle.classList.toggle(
                    'is-active',
                    open
                );

                document.body.classList.toggle(
                    StarParts.config.classes.menuOpen,
                    open
                );


                toggle.setAttribute(
                    'aria-expanded',
                    open
                        ? 'true'
                        : 'false'
                );


                navigation.setAttribute(
                    'aria-hidden',
                    open
                        ? 'false'
                        : 'true'
                );


                if (overlay) {

                    overlay.classList.toggle(
                        'is-visible',
                        open
                    );


                    overlay.setAttribute(
                        'aria-hidden',
                        open
                            ? 'false'
                            : 'true'
                    );

                }


                StarParts.dispatch(
                    'starparts:mobile-menu',
                    {
                        open: open
                    }
                );

            };


        StarParts.on(
            toggle,
            'click',
            function () {

                const isOpen =
                    navigation.classList.contains('is-open');


                setOpen(
                    !isOpen
                );

            }
        );


        if (closeButton) {

            StarParts.on(
                closeButton,
                'click',
                function () {

                    setOpen(false);

                }
            );

        }


        if (overlay) {

            StarParts.on(
                overlay,
                'click',
                function () {

                    setOpen(false);

                }
            );

        }


        /*
         * Tutup menu apabila user memilih
         * link navigasi biasa.
         */

        StarParts.$$(
            '.mobile-navigation-link, .mobile-submenu-link',
            navigation
        ).forEach(
            function (link) {

                StarParts.on(
                    link,
                    'click',
                    function () {

                        const href =
                            link.getAttribute(
                                'href'
                            ) || '';


                        if (
                            href &&
                            !href.startsWith(
                                '#'
                            )
                        ) {

                            setOpen(false);

                        }

                    }
                );

            }
        );


        /*
         * Escape key.
         */

        StarParts.on(
            document,
            'keydown',
            function (event) {

                if (
                    event.key ===
                    'Escape'
                ) {

                    setOpen(false);

                }

            }
        );


        /*
         * Resize desktop.
         */

        StarParts.on(
            window,
            'resize',
            function () {

                if (
                    window.innerWidth >
                    991
                ) {

                    setOpen(false);

                }

            },
            {
                passive: true
            }
        );


        /*
         * expose internal helper
         */

        StarParts.mobileMenu = {
            open: function () {
                setOpen(true);
            },
            close: function () {
                setOpen(false);
            },
            toggle: function () {

                const isOpen =
                    navigation.classList.contains('is-open');

                setOpen(
                    !isOpen
                );

            }
        };

    };


    /* =========================================================
     * MOBILE SUBMENU
     * ========================================================= */

    StarParts.initMobileSubmenus = function () {

        const toggles =
            StarParts.$$(
                '.mobile-submenu-toggle'
            );


        if (!toggles.length) {
            return;
        }


        toggles.forEach(
            function (toggle) {

                StarParts.on(
                    toggle,
                    'click',
                    function () {

                        const parent =
                            toggle.closest(
                                '.mobile-navigation-item'
                            );


                        if (!parent) {
                            return;
                        }


                        const submenu =
                            StarParts.$(
                                '.mobile-submenu',
                                parent
                            );


                        if (!submenu) {
                            return;
                        }


                        const isOpen =
                            parent.classList.contains('submenu-open');


                        /*
                         * Tutup semua submenu lain
                         * pada level yang sama.
                         */

                        StarParts.$$(
                            '.mobile-navigation-item'
                        ).forEach(
                            function (item) {

                                const otherSubmenu =
                                    StarParts.$(
                                        '.mobile-submenu',
                                        item
                                    );


                                const otherToggle =
                                    StarParts.$(
                                        '.mobile-submenu-toggle',
                                        item
                                    );


                                if (
                                    !otherSubmenu ||
                                    otherSubmenu ===
                                        submenu
                                ) {

                                    return;
                                }


                                item.classList.remove('submenu-open');


                                if (otherToggle) {

                                    otherToggle.classList.remove('active');


                                    otherToggle.setAttribute(
                                        'aria-expanded',
                                        'false'
                                    );

                                }

                            }
                        );


                        parent.classList.toggle(
                            'submenu-open',
                            !isOpen
                        );


                        toggle.classList.toggle(
                            'active',
                            !isOpen
                        );


                        toggle.setAttribute(
                            'aria-expanded',
                            !isOpen
                                ? 'true'
                                : 'false'
                        );

                    }
                );

            }
        );

    };


    /* =========================================================
     * BACK TO TOP
     * ========================================================= */

    StarParts.initBackToTop = function () {

        const button =
            StarParts.$(
                StarParts.config.selectors.backToTop
            );


        if (!button) {
            return;
        }


        const update =
            function () {

                const visible =
                    window.scrollY >
                    450;


                button.classList.toggle(
                    StarParts.config.classes.visible,
                    visible
                );

            };


        update();


        StarParts.on(
            window,
            'scroll',
            update,
            {
                passive: true
            }
        );


        StarParts.on(
            button,
            'click',
            function () {

                try {

                    window.scrollTo(
                        {
                            top: 0,
                            behavior: 'smooth'
                        }
                    );

                } catch (error) {

                    window.scrollTo(
                        0,
                        0
                    );

                }

            }
        );

    };


    /* =========================================================
     * SMOOTH SCROLL
     * ========================================================= */

    StarParts.initSmoothScroll = function () {

        const links =
            StarParts.$$(
                'a[href^="#"]'
            );


        links.forEach(
            function (link) {

                StarParts.on(
                    link,
                    'click',
                    function (event) {

                        const href =
                            link.getAttribute(
                                'href'
                            );


                        if (
                            !href ||
                            href === '#'
                        ) {

                            return;

                        }


                        const target =
                            document.querySelector(
                                href
                            );


                        if (!target) {
                            return;
                        }


                        event.preventDefault();


                        const header =
                            StarParts.$(
                                StarParts.config.selectors.header
                            );


                        const headerHeight =
                            header
                                ? header.offsetHeight
                                : 0;


                        const targetTop =
                            target.getBoundingClientRect().top
                            +
                            window.pageYOffset
                            -
                            headerHeight
                            -
                            10;


                        window.scrollTo(
                            {
                                top:
                                    Math.max(
                                        0,
                                        targetTop
                                    ),
                                behavior:
                                    'smooth'
                            }
                        );


                        /*
                         * Tutup mobile menu.
                         */

                        if (
                            StarParts.mobileMenu
                            &&
                            typeof
                                StarParts.mobileMenu.close
                                ===
                                'function'
                        ) {

                            StarParts.mobileMenu.close();

                        }

                    }
                );

            }
        );

    };


    /* =========================================================
     * SWIPER
     * ========================================================= */

    StarParts.initSwiper = function () {

        if (
            typeof window.Swiper !==
            'function'
        ) {

            return;

        }


        /*
         * Hero slider
         */

        StarParts.$$(
            '.hero-swiper, .hero-slider, .spm-catalog-banner-swiper, .spm-catalog-right-swiper'
        ).forEach(
            function (element) {

                if (
                    element.dataset.swiperInitialized ===
                    '1'
                ) {

                    return;

                }


                try {

                    var catalogSwiper = new window.Swiper(
                        element,
                        {

                            loop: true,

                            initialSlide: Math.max(0, parseInt(element.dataset.initialSlide || '0', 10) || 0),

                            speed: 850,

                            autoplay: {
                                delay: Math.max(500, parseInt(element.dataset.autoplayDelay || '5500', 10) || 5500),
                                disableOnInteraction: false,
                                reverseDirection: element.dataset.autoplayDirection === 'reverse'
                            },

                            effect: 'slide',

                            watchOverflow: true,

                            pagination: {
                                el: StarParts.$(
                                    '.swiper-pagination',
                                    element
                                ),
                                clickable: true
                            },

                            navigation: {
                                nextEl: StarParts.$(
                                    element.dataset.autoplayDirection === 'reverse'
                                        ? '.swiper-button-prev'
                                        : '.swiper-button-next',
                                    element
                                ),
                                prevEl: StarParts.$(
                                    element.dataset.autoplayDirection === 'reverse'
                                        ? '.swiper-button-next'
                                        : '.swiper-button-prev',
                                    element
                                )
                            },

                            keyboard: {
                                enabled: true
                            }

                        }
                    );

                    if (element.classList.contains('spm-catalog-right-swiper')) {
                        var copyPanel = element.closest('[data-catalog-copy]');
                        var titleElement = copyPanel ? copyPanel.querySelector('[data-catalog-title]') : null;
                        var subtitleElement = copyPanel ? copyPanel.querySelector('[data-catalog-subtitle]') : null;
                        var actionElement = copyPanel ? copyPanel.querySelector('[data-catalog-action]') : null;
                        var buttonElement = copyPanel ? copyPanel.querySelector('[data-catalog-button]') : null;

                        function updateCatalogCopy(swiper) {
                            var slide = swiper.slides[swiper.activeIndex];
                            if (!slide) return;
                            var slideTitle = slide.dataset.catalogTitle || '';
                            var slideSubtitle = slide.dataset.catalogSubtitle || '';
                            var slideButton = slide.dataset.catalogButton || '';
                            var slideUrl = slide.dataset.catalogUrl || '';

                            if (titleElement) titleElement.textContent = slideTitle || 'Katalog FDR 2026';
                            if (subtitleElement) subtitleElement.textContent = slideSubtitle;
                            if (actionElement) {
                                actionElement.hidden = !(slideButton && slideUrl);
                            }
                            if (buttonElement) {
                                buttonElement.href = slideUrl || '#';
                                buttonElement.childNodes[0].nodeValue = slideButton ? (slideButton + ' ') : '';
                            }
                        }

                        updateCatalogCopy(catalogSwiper);
                        catalogSwiper.on('slideChange', function () {
                            updateCatalogCopy(catalogSwiper);
                        });
                    }


                    element.dataset.swiperInitialized =
                        '1';

                } catch (error) {

                    console.error(
                        'StarParts hero Swiper error:',
                        error
                    );

                }

            }
        );


        /*
         * Generic product swiper
         */

        StarParts.$$(
            '.products-swiper'
        ).forEach(
            function (element) {

                if (
                    element.dataset.swiperInitialized ===
                    '1'
                ) {

                    return;

                }


                try {

                    new window.Swiper(
                        element,
                        {

                            slidesPerView: 1,

                            spaceBetween: 18,

                            speed: 650,

                            watchOverflow: true,

                            breakpoints: {

                                576: {
                                    slidesPerView: 2
                                },

                                992: {
                                    slidesPerView: 3
                                },

                                1200: {
                                    slidesPerView: 4
                                }

                            },

                            pagination: {
                                el: StarParts.$(
                                    '.swiper-pagination',
                                    element
                                ),
                                clickable: true
                            },

                            navigation: {
                                nextEl: StarParts.$(
                                    '.swiper-button-next',
                                    element
                                ),
                                prevEl: StarParts.$(
                                    '.swiper-button-prev',
                                    element
                                )
                            }

                        }
                    );


                    element.dataset.swiperInitialized =
                        '1';

                } catch (error) {

                    console.error(
                        'StarParts product Swiper error:',
                        error
                    );

                }

            }
        );


        /*
         * Brands
         */

        StarParts.$$(
            '.brands-swiper'
        ).forEach(
            function (element) {

                if (
                    element.dataset.swiperInitialized ===
                    '1'
                ) {

                    return;

                }


                try {

                    new window.Swiper(
                        element,
                        {

                            slidesPerView: 2,

                            spaceBetween: 14,

                            loop: true,

                            speed: 800,

                            autoplay: {
                                delay: 2600,
                                disableOnInteraction: false
                            },

                            breakpoints: {

                                576: {
                                    slidesPerView: 3
                                },

                                768: {
                                    slidesPerView: 4
                                },

                                992: {
                                    slidesPerView: 5
                                },

                                1200: {
                                    slidesPerView: 6
                                }

                            }

                        }
                    );


                    element.dataset.swiperInitialized =
                        '1';

                } catch (error) {

                    console.error(
                        'StarParts brand Swiper error:',
                        error
                    );

                }

            }
        );


        /*
         * Generic swiper with data attributes.
         */

        StarParts.$$(
            '[data-swiper-generic]'
        ).forEach(
            function (element) {

                if (
                    element.dataset.swiperInitialized ===
                    '1'
                ) {

                    return;

                }


                try {

                    const slides =
                        parseInt(
                            element.dataset.slides ||
                            '3',
                            10
                        );


                    new window.Swiper(
                        element,
                        {

                            slidesPerView: 1,

                            spaceBetween: 18,

                            speed: 650,

                            breakpoints: {

                                576: {
                                    slidesPerView:
                                        Math.max(
                                            1,
                                            Math.min(
                                                2,
                                                slides
                                            )
                                        )
                                },

                                992: {
                                    slidesPerView:
                                        slides
                                }

                            },

                            navigation: {

                                nextEl:
                                    StarParts.$(
                                        '.swiper-button-next',
                                        element
                                    ),

                                prevEl:
                                    StarParts.$(
                                        '.swiper-button-prev',
                                        element
                                    )

                            },

                            pagination: {

                                el:
                                    StarParts.$(
                                        '.swiper-pagination',
                                        element
                                    ),

                                clickable:
                                    true

                            }

                        }
                    );


                    element.dataset.swiperInitialized =
                        '1';

                } catch (error) {

                    console.error(
                        'StarParts generic Swiper error:',
                        error
                    );

                }

            }
        );

    };


    /* =========================================================
     * AOS
     * ========================================================= */

    StarParts.initAOS = function () {

        if (
            typeof window.AOS !==
            'undefined'
            &&
            typeof window.AOS.init ===
            'function'
        ) {

            try {

                window.AOS.init(
                    {

                        duration: 700,

                        easing:
                            'ease-out-cubic',

                        once: true,

                        offset: 45,

                        disable:
                            'mobile'

                    }
                );

            } catch (error) {

                console.warn(
                    'StarParts AOS init error:',
                    error
                );

            }

        }

    };


    /* =========================================================
     * COUNTERS
     * ========================================================= */

    StarParts.initCounters = function () {

        const counters =
            StarParts.$$(
                StarParts.config.selectors.counter
            );


        if (!counters.length) {
            return;
        }


        /*
         * IntersectionObserver lebih ringan
         * daripada animasi semua counter sekaligus.
         */

        const animate =
            function (element) {

                if (
                    element.dataset.counterAnimated ===
                    '1'
                ) {

                    return;

                }


                element.dataset.counterAnimated =
                    '1';


                const original =
                    (
                        element.dataset.counter
                        ||
                        element.textContent
                        ||
                        ''
                    ).trim();


                /*
                 * Memisahkan angka dan suffix.
                 *
                 * Contoh:
                 * 20+
                 * 500+
                 * 1.500
                 * 95%
                 */

                const numeric =
                    original.replace(
                        /[^0-9.,-]/g,
                        ''
                    );


                const suffix =
                    original.replace(
                        /[0-9.,-]/g,
                        ''
                    );


                let target =
                    parseFloat(
                        numeric.replace(
                            /\./g,
                            ''
                        ).replace(
                            ',',
                            '.'
                        )
                    );


                if (
                    !Number.isFinite(
                        target
                    )
                ) {

                    element.textContent =
                        original;

                    return;

                }


                target =
                    Math.max(
                        0,
                        target
                    );


                const decimalPlaces =
                    (
                        numeric.indexOf(
                            '.'
                        ) >= 0
                    )
                        ? Math.max(
                            0,
                            (
                                numeric.split(
                                    '.'
                                )[1]
                                ||
                                ''
                            ).length
                        )
                        : 0;


                const duration =
                    1100;


                const startTime =
                    performance.now();


                const formatter =
                    function (value) {

                        if (
                            decimalPlaces >
                            0
                        ) {

                            return value.toFixed(
                                decimalPlaces
                            );

                        }


                        return Math.round(
                            value
                        ).toLocaleString(
                            'id-ID'
                        );

                    };


                const frame =
                    function (
                        currentTime
                    ) {

                        const elapsed =
                            currentTime -
                            startTime;


                        const progress =
                            Math.min(
                                elapsed /
                                duration,
                                1
                            );


                        /*
                         * easeOutQuart
                         */

                        const eased =
                            1 -
                            Math.pow(
                                1 - progress,
                                4
                            );


                        const value =
                            target *
                            eased;


                        element.textContent =
                            formatter(
                                value
                            ) +
                            suffix;


                        if (
                            progress <
                            1
                        ) {

                            window.requestAnimationFrame(
                                frame
                            );

                        } else {

                            element.textContent =
                                formatter(
                                    target
                                ) +
                                suffix;

                        }

                    };


                window.requestAnimationFrame(
                    frame
                );

            };


        if (
            'IntersectionObserver' in
            window
        ) {

            const observer =
                new IntersectionObserver(
                    function (
                        entries,
                        currentObserver
                    ) {

                        entries.forEach(
                            function (
                                entry
                            ) {

                                if (
                                    entry.isIntersecting
                                ) {

                                    animate(
                                        entry.target
                                    );


                                    currentObserver.unobserve(
                                        entry.target
                                    );

                                }

                            }
                        );

                    },
                    {
                        threshold: .45
                    }
                );


            counters.forEach(
                function (counter) {

                    observer.observe(
                        counter
                    );

                }
            );

        } else {

            counters.forEach(
                animate
            );

        }

    };


    /* =========================================================
     * LAZY MEDIA
     * ========================================================= */

    StarParts.initLazyMedia = function () {

        const elements =
            StarParts.$$(
                'img[data-src], source[data-src]'
            );


        if (!elements.length) {
            return;
        }


        const load =
            function (element) {

                const source =
                    element.dataset.src;


                if (
                    !source
                ) {

                    return;

                }


                element.src =
                    source;


                element.removeAttribute(
                    'data-src'
                );


                if (
                    element.tagName.toLowerCase()
                    ===
                    'source'
                ) {

                    const picture =
                        element.closest(
                            'picture'
                        );


                    if (
                        picture
                    ) {

                        const image =
                            StarParts.$(
                                'img',
                                picture
                            );


                        if (
                            image
                        ) {

                            image.src =
                                image.dataset.src ||
                                image.src;

                        }

                    }

                }

            };


        if (
            'IntersectionObserver' in
            window
        ) {

            const observer =
                new IntersectionObserver(
                    function (
                        entries,
                        currentObserver
                    ) {

                        entries.forEach(
                            function (
                                entry
                            ) {

                                if (
                                    entry.isIntersecting
                                ) {

                                    load(
                                        entry.target
                                    );


                                    currentObserver.unobserve(
                                        entry.target
                                    );

                                }

                            }
                        );

                    },
                    {
                        rootMargin:
                            '250px 0px'
                    }
                );


            elements.forEach(
                function (element) {

                    observer.observe(
                        element
                    );

                }
            );

        } else {

            elements.forEach(
                load
            );

        }

    };


    /* =========================================================
     * IMAGE FALLBACK
     * ========================================================= */

    StarParts.initImageFallback = function () {

        const images =
            StarParts.$$(
                'img'
            );


        images.forEach(
            function (image) {

                StarParts.on(
                    image,
                    'error',
                    function () {

                        image.classList.add(
                            'image-error'
                        );


                        image.setAttribute(
                            'data-image-error',
                            '1'
                        );


                        /*
                         * Jangan mengganti image berkali-kali.
                         */

                        if (
                            image.dataset.fallbackApplied
                            ===
                            '1'
                        ) {

                            return;

                        }


                        image.dataset.fallbackApplied =
                            '1';


                        /*
                         * SVG placeholder internal.
                         */

                        const placeholder =
                            'data:image/svg+xml;charset=UTF-8,' +
                            encodeURIComponent(
                                `
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="800"
                                    height="600"
                                    viewBox="0 0 800 600"
                                >
                                    <rect
                                        width="800"
                                        height="600"
                                        fill="#f3f3f3"
                                    />
                                    <circle
                                        cx="400"
                                        cy="270"
                                        r="72"
                                        fill="#dfdfdf"
                                    />
                                    <path
                                        d="M350 270h100M400 220v100"
                                        stroke="#aaa"
                                        stroke-width="18"
                                        stroke-linecap="round"
                                    />
                                    <text
                                        x="400"
                                        y="400"
                                        text-anchor="middle"
                                        fill="#888"
                                        font-family="Arial"
                                        font-size="24"
                                    >
                                        Star Parts Motor
                                    </text>
                                </svg>
                                `
                            );


                        image.src =
                            placeholder;

                    }
                );

            }
        );

    };


    /* =========================================================
     * EXTERNAL LINK SAFETY
     * ========================================================= */

    StarParts.initExternalLinks = function () {

        const links =
            StarParts.$$(
                'a[target="_blank"]'
            );


        links.forEach(
            function (link) {

                const rel =
                    (
                        link.getAttribute(
                            'rel'
                        )
                        ||
                        ''
                    ).split(
                        /\s+/
                    ).filter(
                        Boolean
                    );


                if (
                    !rel.includes(
                        'noopener'
                    )
                ) {

                    rel.push(
                        'noopener'
                    );

                }


                if (
                    !rel.includes(
                        'noreferrer'
                    )
                ) {

                    rel.push(
                        'noreferrer'
                    );

                }


                link.setAttribute(
                    'rel',
                    rel.join(' ')
                );

            }
        );

    };


    /* =========================================================
     * CONTACT FORM
     * ========================================================= */

    StarParts.initContactForms = function () {

        const forms =
            StarParts.$$(
                StarParts.config.selectors.contactForm
            );


        if (!forms.length) {
            return;
        }


        forms.forEach(
            function (form) {

                let submitted =
                    false;


                StarParts.on(
                    form,
                    'submit',
                    function (event) {

                        /*
                         * Prevent double submit.
                         */

                        if (
                            submitted
                        ) {

                            event.preventDefault();

                            return;

                        }


                        /*
                         * HTML5 validation.
                         */

                        if (
                            typeof
                                form.checkValidity
                                ===
                                'function'
                            &&
                            !form.checkValidity()
                        ) {

                            /*
                             * Biarkan browser menampilkan
                             * native validation.
                             */

                            return;

                        }


                        submitted =
                            true;


                        form.classList.add(
                            'is-submitting'
                        );


                        const button =
                            form.querySelector(
                                'button[type="submit"]'
                            );


                        if (
                            button
                            &&
                            !button.dataset.originalText
                        ) {

                            button.dataset.originalText =
                                button.innerHTML;

                        }


                        if (button) {

                            button.disabled =
                                true;


                            button.innerHTML =
                                `
                                <span
                                    class="form-spinner"
                                    aria-hidden="true"
                                ></span>
                                <span>
                                    Mengirim...
                                </span>
                                `;

                        }

                    }
                );


                /*
                 * Jika browser kembali ke halaman
                 * tanpa reload penuh, izinkan submit lagi.
                 */

                window.addEventListener(
                    'pageshow',
                    function () {

                        submitted =
                            false;


                        form.classList.remove(
                            'is-submitting'
                        );


                        const button =
                            form.querySelector(
                                'button[type="submit"]'
                            );


                        if (button) {

                            button.disabled =
                                false;


                            if (
                                button.dataset.originalText
                            ) {

                                button.innerHTML =
                                    button.dataset.originalText;

                            }

                        }

                    }
                );

            }
        );

    };


    /* =========================================================
     * INPUT UX
     * ========================================================= */

    StarParts.initInputUX = function () {

        const inputs =
            StarParts.$$(
                'input, textarea, select'
            );


        inputs.forEach(
            function (input) {

                StarParts.on(
                    input,
                    'focus',
                    function () {

                        const group =
                            input.closest(
                                '.form-group'
                            );


                        if (
                            group
                        ) {

                            group.classList.add(
                                'is-focused'
                            );

                        }

                    }
                );


                StarParts.on(
                    input,
                    'blur',
                    function () {

                        const group =
                            input.closest(
                                '.form-group'
                            );


                        if (
                            group
                        ) {

                            group.classList.remove(
                                'is-focused'
                            );

                        }

                    }
                );

            }
        );

    };


    /* =========================================================
     * ESCAPE KEY
     * ========================================================= */

    StarParts.initEscapeHandling = function () {

        StarParts.on(
            document,
            'keydown',
            function (event) {

                if (
                    event.key !==
                    'Escape'
                ) {

                    return;

                }


                /*
                 * Tutup dropdown mobile.
                 */

                StarParts.$$(
                    '.mobile-navigation-item.submenu-open'
                ).forEach(
                    function (item) {

                        item.classList.remove('submenu-open');

                        const toggle = item.querySelector('.mobile-submenu-toggle');
                        if (toggle) {
                            toggle.classList.remove('active');
                            toggle.setAttribute('aria-expanded', 'false');
                        }

                    }
                );


                /*
                 * Tutup mobile menu.
                 */

                if (
                    StarParts.mobileMenu
                    &&
                    typeof
                        StarParts.mobileMenu.close
                        ===
                        'function'
                ) {

                    StarParts.mobileMenu.close();

                }

            }
        );

    };


    /* =========================================================
     * REVEAL FALLBACK
     * ========================================================= */

    StarParts.initReveal = function () {

        const elements =
            StarParts.$$(
                '.reveal-on-scroll'
            );


        if (!elements.length) {
            return;
        }


        if (
            !('IntersectionObserver' in window)
        ) {

            elements.forEach(
                function (element) {

                    element.classList.add(
                        'revealed'
                    );

                }
            );

            return;

        }


        const observer =
            new IntersectionObserver(
                function (
                    entries,
                    currentObserver
                ) {

                    entries.forEach(
                        function (
                            entry
                        ) {

                            if (
                                entry.isIntersecting
                            ) {

                                entry.target.classList.add(
                                    'revealed'
                                );


                                currentObserver.unobserve(
                                    entry.target
                                );

                            }

                        }
                    );

                },
                {
                    threshold: .15
                }
            );


        elements.forEach(
            function (element) {

                observer.observe(
                    element
                );

            }
        );

    };


    /* =========================================================
     * WHATSAPP HELPER
     * ========================================================= */

    StarParts.whatsapp = function (
        phone,
        message
    ) {

        let number =
            String(
                phone ||
                ''
            ).replace(
                /[^0-9]/g,
                ''
            );


        if (
            number.startsWith(
                '0'
            )
        ) {

            number =
                '62' +
                number.substring(
                    1
                );

        }


        if (
            !number
        ) {

            return '#';

        }


        const text =
            String(
                message ||
                ''
            );


        return (
            'https://wa.me/' +
            number +
            (
                text
                    ? '?text=' +
                        encodeURIComponent(
                            text
                        )
                    : ''
            )
        );

    };


    /* =========================================================
     * COPY TO CLIPBOARD
     * ========================================================= */

    StarParts.copy = async function (
        text
    ) {

        const value =
            String(
                text ||
                ''
            );


        if (
            !value
        ) {

            return false;

        }


        try {

            if (
                navigator.clipboard
                &&
                typeof
                    navigator.clipboard.writeText
                    ===
                    'function'
            ) {

                await navigator.clipboard.writeText(
                    value
                );

                return true;

            }

        } catch (error) {

            /* fallback */

        }


        try {

            const textarea =
                document.createElement(
                    'textarea'
                );


            textarea.value =
                value;


            textarea.style.position =
                'fixed';

            textarea.style.opacity =
                '0';


            document.body.appendChild(
                textarea
            );


            textarea.focus();
            textarea.select();


            const success =
                document.execCommand(
                    'copy'
                );


            document.body.removeChild(
                textarea
            );


            return success;

        } catch (error) {

            return false;

        }

    };


    /* =========================================================
     * DEBOUNCE
     * ========================================================= */

    StarParts.debounce = function (
        callback,
        delay
    ) {

        let timeout = null;


        return function () {

            const context =
                this;

            const args =
                arguments;


            window.clearTimeout(
                timeout
            );


            timeout =
                window.setTimeout(
                    function () {

                        callback.apply(
                            context,
                            args
                        );

                    },
                    delay
                );

        };

    };


    /* =========================================================
     * THROTTLE
     * ========================================================= */

    StarParts.throttle = function (
        callback,
        delay
    ) {

        let waiting =
            false;


        return function () {

            if (
                waiting
            ) {

                return;

            }


            const context =
                this;

            const args =
                arguments;


            callback.apply(
                context,
                args
            );


            waiting =
                true;


            window.setTimeout(
                function () {

                    waiting =
                        false;

                },
                delay
            );

        };

    };


    /* =========================================================
     * VUE ENHANCEMENT
     * ========================================================= */

    StarParts.initVue = function () {

        /*
         * Jangan memaksa Vue pada seluruh website.
         * Vue hanya diinisialisasi ketika halaman memang
         * mempunyai elemen [data-vue-app].
         */

        const appElements =
            StarParts.$$(
                '[data-vue-app]'
            );


        if (
            !appElements.length
        ) {

            return;

        }


        if (
            typeof window.Vue ===
            'undefined'
        ) {

            console.warn(
                'StarParts Vue enhancement skipped: Vue CDN belum tersedia.'
            );

            return;

        }


        appElements.forEach(
            function (element) {

                if (
                    element.dataset.vueInitialized
                    ===
                    '1'
                ) {

                    return;

                }


                try {

                    /*
                     * Application default.
                     * Halaman dapat menyediakan konfigurasi
                     * melalui window.StarPartsVueFactory.
                     */

                    let app = null;


                    if (
                        typeof
                            window.StarPartsVueFactory
                            ===
                            'function'
                    ) {

                        app =
                            window.StarPartsVueFactory(
                                element,
                                window.Vue
                            );

                    }


                    /*
                     * Jika halaman tidak menyediakan factory,
                     * gunakan Vue sederhana agar elemen tetap
                     * tersedia tanpa mengganggu PHP-rendered HTML.
                     */

                    if (
                        !app
                    ) {

                        const {
                            createApp
                        } =
                            window.Vue;


                        app =
                            createApp(
                                {
                                    mounted:
                                        function () {

                                            this.$el.classList.add(
                                                'vue-ready'
                                            );

                                        }
                                }
                            );

                    }


                    app.mount(
                        element
                    );


                    element.dataset.vueInitialized =
                        '1';

                } catch (error) {

                    console.error(
                        'StarParts Vue initialization error:',
                        error
                    );

                }

            }
        );

    };


    /* =========================================================
     * ARIA / ACCESSIBILITY
     * ========================================================= */

    StarParts.initAccessibility = function () {

        /*
         * External links yang membuka tab baru
         * diberi label tambahan bila belum ada.
         */

        StarParts.$$(
            'a[target="_blank"]'
        ).forEach(
            function (link) {

                if (
                    !link.getAttribute(
                        'aria-label'
                    )
                ) {

                    const text =
                        (
                            link.textContent
                            ||
                            ''
                        ).trim();


                    if (
                        text
                    ) {

                        link.setAttribute(
                            'aria-label',
                            text +
                            ' - membuka tab baru'
                        );

                    }

                }

            }
        );


        /*
         * Gambar tanpa alt dibuat dekoratif.
         */

        StarParts.$$(
            'img'
        ).forEach(
            function (image) {

                if (
                    !image.hasAttribute(
                        'alt'
                    )
                ) {

                    image.setAttribute(
                        'alt',
                        ''
                    );

                }

            }
        );

    };


    /* =========================================================
     * SCROLL STATE
     * ========================================================= */

    StarParts.initScrollState = function () {

        const root =
            document.documentElement;


        const update =
            function () {

                root.style.setProperty(
                    '--sp-scroll-y',
                    String(
                        window.scrollY
                    )
                );

            };


        update();


        StarParts.on(
            window,
            'scroll',
            StarParts.throttle(
                update,
                80
            ),
            {
                passive: true
            }
        );

    };


    /* =========================================================
     * VISIBILITY CHANGE
     * ========================================================= */

    StarParts.initVisibilityHandler = function () {

        StarParts.on(
            document,
            'visibilitychange',
            function () {

                StarParts.dispatch(
                    'starparts:visibility',
                    {
                        visible:
                            !document.hidden
                    }
                );

            }
        );

    };


    /* =========================================================
     * GLOBAL ERROR SAFETY
     * ========================================================= */

    StarParts.initErrorSafety = function () {

        StarParts.on(
            window,
            'error',
            function (event) {

                /*
                 * Jangan membiarkan error satu elemen
                 * menghentikan seluruh UX website.
                 */

                if (
                    event.target
                    &&
                    event.target.tagName
                    &&
                    (
                        event.target.tagName
                            .toLowerCase()
                        ===
                        'img'
                    )
                ) {

                    /*
                     * Ditangani oleh image fallback.
                     */

                    return;

                }

            },
            true
        );

    };


    /* =========================================================
     * INITIALIZATION
     * ========================================================= */

    StarParts.init = function () {

        StarParts.initPreloader();

        StarParts.initHeader();

        StarParts.initMobileMenu();

        StarParts.initMobileSubmenus();

        StarParts.initBackToTop();

        StarParts.initSmoothScroll();

        StarParts.initSwiper();

        StarParts.initAOS();

        StarParts.initCounters();

        StarParts.initLazyMedia();

        StarParts.initImageFallback();

        StarParts.initExternalLinks();

        StarParts.initContactForms();

        StarParts.initInputUX();

        StarParts.initEscapeHandling();

        StarParts.initReveal();

        StarParts.initAccessibility();

        StarParts.initScrollState();

        StarParts.initVisibilityHandler();

        StarParts.initErrorSafety();


        /*
         * Vue diberi sedikit jeda agar CDN Vue yang
         * ditempatkan di footer sudah selesai tersedia.
         */

        window.setTimeout(
            function () {

                StarParts.initVue();

            },
            0
        );


        StarParts.dispatch(
            'starparts:ready'
        );

    };


    /* =========================================================
     * EXPOSE GLOBAL
     * ========================================================= */

    window.StarParts =
        StarParts;


    /* =========================================================
     * START
     * ========================================================= */

    StarParts.ready(
        function () {

            StarParts.init();

        }
    );


})(window, document);
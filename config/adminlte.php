<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    */
    'title' => 'Internet35',
    'title_prefix' => '',
    'title_postfix' => ' | Internet35 Billing',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    */
    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */
    'logo' => '<b>Internet</b>35',
    'logo_img' => 'assets/img/logo.png',
    'logo_img_class' => 'brand-image img-circle elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'Internet35',

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    */
    'usermenu_enabled' => true,
    'usermenu_header' => true,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => true,
    'usermenu_desc' => true,
    'usermenu_profile_url' => true,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    */
    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => true,
    'layout_fixed_navbar' => true,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    */
    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    */
    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */
    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_hierarchical' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    */
    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */
    'use_route_url' => true,
    'dashboard_url' => 'admin.dashboard',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => null,
    'password_reset_url' => 'password.request',
    'password_email_url' => 'password.email',
    'profile_url' => 'admin.profile.index',

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    */
    'enabled_laravel_mix' => false,
    'laravel_mix_css_path' => 'css/app.css',
    'laravel_mix_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    */
    'menu' => [
        // Navbar items
        [
            'type' => 'navbar-search',
            'text' => 'search',
            'topnav_right' => true,
        ],
        [
            'type' => 'fullscreen-widget',
            'topnav_right' => true,
        ],

        // Sidebar items
        [
            'type' => 'sidebar-menu-search',
            'text' => 'search',
        ],
        [
            'text' => 'NAVIGASI UTAMA',
            'classes' => 'nav-header',
        ],
        [
            'text' => 'Dashboard',
            'url' => 'admin/dashboard',
            'icon' => 'fas fa-fw fa-tachometer-alt',
            'active' => ['admin/dashboard*'],
            'can' => 'dashboard.view',
        ],

        // User Management
        [
            'text' => 'USER MANAGEMENT',
            'classes' => 'nav-header',
            'can' => ['users.view', 'roles.view', 'permissions.view'],
        ],
        [
            'text' => 'Manajemen User',
            'icon' => 'fas fa-fw fa-users-cog',
            'can' => ['users.view', 'roles.view', 'permissions.view'],
            'submenu' => [
                [
                    'text' => 'Users',
                    'url' => 'admin/users',
                    'icon' => 'fas fa-fw fa-users',
                    'active' => ['admin/users*'],
                    'can' => 'users.view',
                ],
                [
                    'text' => 'Roles',
                    'url' => 'admin/roles',
                    'icon' => 'fas fa-fw fa-user-tag',
                    'active' => ['admin/roles*'],
                    'can' => 'roles.view',
                ],
                [
                    'text' => 'Permissions',
                    'url' => 'admin/permissions',
                    'icon' => 'fas fa-fw fa-key',
                    'active' => ['admin/permissions*'],
                    'can' => 'permissions.view',
                ],
            ],
        ],

        // Activity & Monitoring
        [
            'text' => 'MONITORING',
            'classes' => 'nav-header',
            'can' => 'activity-logs.view',
        ],
        [
            'text' => 'Activity Logs',
            'url' => 'admin/activity-logs',
            'icon' => 'fas fa-fw fa-history',
            'active' => ['admin/activity-logs*'],
            'can' => 'activity-logs.view',
        ],

        // Network & OLT Management
        [
            'text' => 'MANAJEMEN JARINGAN',
            'classes' => 'nav-header',
        ],
        [
            'text' => 'Manajemen OLT',
            'icon' => 'fas fa-fw fa-server',
            'submenu' => [
                [
                    'text' => 'Daftar OLT',
                    'url' => 'admin/olts',
                    'icon' => 'fas fa-fw fa-server',
                    'active' => ['admin/olts*'],
                    'can' => 'olts.view',
                ],
                [
                    'text' => 'Daftar ONU',
                    'url' => 'admin/onus',
                    'icon' => 'fas fa-fw fa-broadcast-tower',
                    'active' => ['admin/onus*'],
                    'can' => 'onus.view',
                ],
            ],
        ],
        [
            'text' => 'Jaringan Distribusi',
            'icon' => 'fas fa-fw fa-network-wired',
            'submenu' => [
                [
                    'text' => 'ODC',
                    'url' => 'admin/odcs',
                    'icon' => 'fas fa-fw fa-building',
                    'active' => ['admin/odcs*'],
                    'can' => 'odcs.view',
                ],
                [
                    'text' => 'ODP',
                    'url' => 'admin/odps',
                    'icon' => 'fas fa-fw fa-box',
                    'active' => ['admin/odps*'],
                    'can' => 'odps.view',
                ],
            ],
        ],
        [
            'text' => 'Network Map',
            'url' => 'admin/network-map',
            'icon' => 'fas fa-fw fa-map-marked-alt',
            'active' => ['admin/network-map*'],
            'can' => 'network-map.view',
        ],

        // Landing Page Management
        [
            'text' => 'LANDING PAGE',
            'classes' => 'nav-header',
        ],
        [
            'text' => 'Konten Landing',
            'icon' => 'fas fa-fw fa-globe',
            'can' => ['landing.contents.view', 'landing.sliders.view', 'landing.services.view'],
            'submenu' => [
                [
                    'text' => 'Sliders',
                    'url' => 'admin/landing/sliders',
                    'icon' => 'fas fa-fw fa-images',
                    'active' => ['admin/landing/sliders*'],
                    'can' => 'landing.sliders.view',
                ],
                [
                    'text' => 'Services',
                    'url' => 'admin/landing/services',
                    'icon' => 'fas fa-fw fa-concierge-bell',
                    'active' => ['admin/landing/services*'],
                    'can' => 'landing.services.view',
                ],
                [
                    'text' => 'Packages',
                    'url' => 'admin/landing/packages',
                    'icon' => 'fas fa-fw fa-box',
                    'active' => ['admin/landing/packages*'],
                    'can' => 'landing.packages.view',
                ],
                [
                    'text' => 'Testimonials',
                    'url' => 'admin/landing/testimonials',
                    'icon' => 'fas fa-fw fa-quote-left',
                    'active' => ['admin/landing/testimonials*'],
                    'can' => 'landing.testimonials.view',
                ],
                [
                    'text' => 'FAQs',
                    'url' => 'admin/landing/faqs',
                    'icon' => 'fas fa-fw fa-question-circle',
                    'active' => ['admin/landing/faqs*'],
                    'can' => 'landing.faqs.view',
                ],
                [
                    'text' => 'Site Settings',
                    'url' => 'admin/settings',
                    'icon' => 'fas fa-fw fa-cog',
                    'active' => ['admin/settings*'],
                    'can' => 'settings.view',
                ],
            ],
        ],

        // Profile
        [
            'text' => 'AKUN SAYA',
            'classes' => 'nav-header',
        ],
        [
            'text' => 'Profile Saya',
            'url' => 'admin/profile',
            'icon' => 'fas fa-fw fa-user-circle',
            'active' => ['admin/profile*'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    */
    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    */
    'plugins' => [
        'Datatables' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/datatables/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/datatables/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/datatables-plugins/responsive/js/dataTables.responsive.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/datatables-plugins/responsive/js/responsive.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/datatables/css/dataTables.bootstrap4.min.css',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/datatables-plugins/responsive/css/responsive.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/select2/js/select2.full.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/select2/css/select2.min.css',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/sweetalert2/sweetalert2.all.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/sweetalert2/sweetalert2.min.css',
                ],
            ],
        ],
        'Toastr' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/toastr/toastr.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/toastr/toastr.min.css',
                ],
            ],
        ],
        'Leaflet' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                ],
            ],
        ],
        'Cropper' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    */
    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_hierarchical_replace' => false,
            'allow_duplicates' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    */
    'livewire' => false,
];

<?php

declare(strict_types=1);

return [
    'timezone' => [
        'fallback' => env('NEXSTAY_TIMEZONE_FALLBACK', 'Africa/Dar_es_Salaam'),
        'default' => env('APP_TIMEZONE', env('NEXSTAY_TIMEZONE_FALLBACK', 'Africa/Dar_es_Salaam')),
    ],

    'api_docs' => [
        'enabled' => filter_var(env('API_DOCS_ENABLED', env('APP_ENV', 'production') === 'local'), FILTER_VALIDATE_BOOL),
        'default_tenant_host' => env('API_DOCS_TENANT_HOST', 'http://demo.localhost:8000'),
    ],

    'currency' => [
        'default' => env('NEXSTAY_DEFAULT_CURRENCY', 'TZS'),
    ],

    'tax' => [
        'default_rates' => [
            '_default' => env('NEXSTAY_DEFAULT_VAT_RATE', '0.18'),
            '_code' => env('NEXSTAY_DEFAULT_TAX_CODE', 'A'),
            '_inclusive' => filter_var(env('NEXSTAY_TAX_INCLUSIVE', true), FILTER_VALIDATE_BOOLEAN),
            'room_charge' => env('NEXSTAY_ROOM_VAT_RATE', '0.18'),
            'restaurant' => env('NEXSTAY_FB_VAT_RATE', '0.18'),
            'bar' => env('NEXSTAY_BAR_VAT_RATE', '0.18'),
            'pool' => env('NEXSTAY_POOL_VAT_RATE', '0.18'),
            'gym' => env('NEXSTAY_GYM_VAT_RATE', '0.18'),
        ],
    ],

    'facilities' => [
        'pool' => [
            'default_fee' => (float) env('NEXSTAY_POOL_DEFAULT_FEE', 15000),
        ],
        'gym' => [
            'default_fee' => (float) env('NEXSTAY_GYM_DEFAULT_FEE', 10000),
        ],
    ],

    'till' => [
        'variance_threshold' => (float) env('TILL_VARIANCE_THRESHOLD', 2000),
        'require_manager_pin' => filter_var(env('TILL_REQUIRE_MANAGER_PIN', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'reservations' => [
        'payment_mode' => env('NEXSTAY_RESERVATION_PAYMENT_MODE', 'prepaid'),
        'cancellation' => [
            'policy' => env('NEXSTAY_CANCELLATION_POLICY', 'stayed_nights'),
            'refund_percentage' => (float) env('NEXSTAY_CANCELLATION_REFUND_PERCENTAGE', 0),
        ],
    ],

    'reports' => [
        'delivery' => [
            'recipient_email' => env('NEXSTAY_REPORT_EMAIL', ''),
            'send_time' => env('NEXSTAY_REPORT_SEND_TIME', '08:00'),
            'timezone' => env('NEXSTAY_REPORT_TIMEZONE', env('APP_TIMEZONE', env('NEXSTAY_TIMEZONE_FALLBACK', 'Africa/Dar_es_Salaam'))),
        ],
    ],

    'demo' => [
        'tenant_id' => env('DEMO_TENANT_ID', 'demo'),
        'domain' => env('DEMO_TENANT_DOMAIN', 'demo'),
        'admin_username' => env('DEMO_ADMIN_USERNAME', 'admin'),
        'front_desk_username' => env('DEMO_FRONT_DESK_USERNAME', 'frontdesk'),
        'housekeeper_username' => env('DEMO_HOUSEKEEPER_USERNAME', 'housekeeper'),
        'admin_email' => env('DEMO_ADMIN_EMAIL', 'admin@demo.local'),
        'front_desk_email' => env('DEMO_FRONT_DESK_EMAIL', 'frontdesk@demo.local'),
        'housekeeper_email' => env('DEMO_HOUSEKEEPER_EMAIL', 'housekeeper@demo.local'),
        'password' => env('DEMO_PASSWORD', 'NexStay2026!'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant feature packs (Platform Admin controlled)
    |--------------------------------------------------------------------------
    | Keys are stored on the central Tenant as enabled_features (virtual column).
    | Shared F&B permissions unlock when any of restaurant/bar/lounge is enabled.
    */
    'features' => [
        'hotel' => [
            'label' => 'Hotel / Front office',
            'description' => 'Reservations, rooms, guests, folios, night audit',
            'permissions' => [
                'view-reservations',
                'manage-reservations',
                'force-delete-reservations',
                'check-in-guests',
                'check-out-guests',
                'view-guests',
                'manage-guests',
                'view-rooms',
                'manage-rooms',
                'manage-room-status',
                'manage-room-types',
                'manage-rate-plans',
                'view-folios',
                'post-folio-charges',
                'view-availability',
                'view-debts',
                'view-ancillary-services',
                'manage-ancillary-services',
                'view-damage',
                'manage-damage',
                'run-night-audit',
                'view-reports',
            ],
        ],
        'restaurant' => [
            'label' => 'Restaurant',
            'description' => 'Restaurant POS and F&B shared tools',
            'permissions' => [],
        ],
        'bar' => [
            'label' => 'Bar',
            'description' => 'Bar POS and F&B shared tools',
            'permissions' => [],
        ],
        'lounge' => [
            'label' => 'Lounge',
            'description' => 'Lounge POS and F&B shared tools',
            'permissions' => [],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'description' => 'Stock items and purchases',
            'permissions' => [
                'view-inventory',
                'manage-inventory',
                'view-purchases',
                'manage-purchases',
            ],
        ],
        'facilities' => [
            'label' => 'Facilities',
            'description' => 'Pool and gym attendance',
            'permissions' => [
                'view-facility-attendance',
                'record-facility-attendance',
                'view-facility-reports',
            ],
        ],
    ],

    'fb_feature_keys' => ['restaurant', 'bar', 'lounge'],

    'shared_fb_permissions' => [
        'view-outlets',
        'manage-outlets',
        'view-menu',
        'manage-menu',
        'view-orders',
        'view-all-orders',
        'manage-orders',
        'manage-all-orders',
        'manage-own-orders',
        'process-kitchen-orders',
        'view-till',
        'manage-till',
        'view-fb-reports',
        // Beverage/kitchen stock is part of F&B operations even when the
        // standalone Inventory module pack is not explicitly checked.
        'view-inventory',
        'manage-inventory',
    ],

    'always_on_permissions' => [
        'manage-users',
        'manage-roles',
        'view-expenditures',
        'manage-expenditures',
    ],

    /*
    |--------------------------------------------------------------------------
    | HBMS web navigation (tenant UI)
    |--------------------------------------------------------------------------
    | permission: null = any authenticated staff member
    | feature: single module key required
    | features: any of the listed module keys required
    */
    'navigation' => [
        [
            'id' => 'dashboard',
            'label' => 'Overview',
            'route' => 'tenant.dashboard',
            'icon' => 'chart',
            'permission' => null,
        ],
        [
            'id' => 'notifications',
            'label' => 'Notifications',
            'route' => 'tenant.notifications.index',
            'icon' => 'bell',
            'permission' => null,
        ],
        [
            'id' => 'front-office',
            'label' => 'Front office',
            'icon' => 'bed',
            'feature' => 'hotel',
            'children' => [
                ['id' => 'availability', 'label' => 'Availability', 'route' => 'tenant.availability', 'permission' => 'view-availability', 'feature' => 'hotel'],
                ['id' => 'reservations', 'label' => 'Reservations', 'route' => 'tenant.reservations.index', 'permission' => 'view-reservations', 'feature' => 'hotel'],
                ['id' => 'booked-list', 'label' => 'Booked list', 'route' => 'tenant.booked-list.index', 'permission' => 'view-reservations', 'feature' => 'hotel'],
                ['id' => 'time-left', 'label' => 'Time left', 'route' => 'tenant.time-left.index', 'permission' => 'view-reservations', 'feature' => 'hotel'],
                ['id' => 'guests', 'label' => 'Guests', 'route' => 'tenant.guests.index', 'permission' => 'view-guests', 'feature' => 'hotel'],
                ['id' => 'room-types', 'label' => 'Room types', 'route' => 'tenant.room-types.index', 'permission' => 'view-rooms', 'feature' => 'hotel'],
                ['id' => 'rooms', 'label' => 'Rooms', 'route' => 'tenant.rooms.index', 'permission' => 'view-rooms', 'feature' => 'hotel'],
                ['id' => 'rate-plans', 'label' => 'Rate plans', 'route' => 'tenant.rate-plans.index', 'permission' => 'view-reservations', 'feature' => 'hotel'],
                ['id' => 'damage', 'label' => 'Damage', 'route' => 'tenant.damages.index', 'permission' => 'view-damage', 'feature' => 'hotel'],
            ],
        ],
        [
            'id' => 'fb',
            'label' => 'Food & beverage',
            'icon' => 'restaurant',
            'features' => ['restaurant', 'bar', 'lounge'],
            'children' => [
                ['id' => 'restaurant', 'label' => 'Restaurant', 'route' => 'tenant.restaurant.index', 'permission' => 'view-orders', 'feature' => 'restaurant'],
                ['id' => 'bar', 'label' => 'Bar', 'route' => 'tenant.bar.index', 'permission' => 'view-orders', 'feature' => 'bar'],
                ['id' => 'lounge', 'label' => 'Lounge', 'route' => 'tenant.lounge.index', 'permission' => 'view-orders', 'feature' => 'lounge'],
                ['id' => 'fb-orders', 'label' => 'Sales', 'route' => 'tenant.fb.orders.index', 'permission' => 'view-orders', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'shift-mine', 'label' => 'My shift', 'route' => 'tenant.shift.mine', 'permission' => 'view-orders', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'shift-all', 'label' => 'Staff shift', 'route' => 'tenant.shift.all', 'permission' => 'view-fb-reports', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'outlets', 'label' => 'Outlets', 'route' => 'tenant.outlets.index', 'permission' => 'view-outlets', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'menu-categories', 'label' => 'Menu categories', 'route' => 'tenant.menu-categories.index', 'permission' => 'view-menu', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'menu', 'label' => 'Menu items', 'route' => 'tenant.menu-items.index', 'permission' => 'view-menu', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'till', 'label' => 'Till', 'route' => 'tenant.till.index', 'permission' => 'view-till', 'features' => ['restaurant', 'bar', 'lounge']],
            ],
        ],
        [
            'id' => 'inventory-group',
            'label' => 'Inventory',
            'icon' => 'inventory',
            'features' => ['inventory', 'bar', 'restaurant'],
            'children' => [
                ['id' => 'inventory', 'label' => 'Stock items', 'route' => 'tenant.stock-items.index', 'permission' => 'view-inventory', 'features' => ['inventory', 'bar', 'restaurant']],
                ['id' => 'stock-history', 'label' => 'Stock history', 'route' => 'tenant.stock-items.movements', 'permission' => 'view-inventory', 'features' => ['inventory', 'bar', 'restaurant']],
                ['id' => 'purchases', 'label' => 'Purchases', 'route' => 'tenant.purchases.index', 'permission' => 'view-purchases', 'feature' => 'inventory'],
            ],
        ],
        [
            'id' => 'facilities-group',
            'label' => 'Facilities',
            'icon' => 'dumbbell',
            'feature' => 'facilities',
            'children' => [
                ['id' => 'facility-pool', 'label' => 'Swimming Pool', 'route' => 'tenant.facilities.pool', 'permission' => 'view-facility-attendance', 'feature' => 'facilities'],
                ['id' => 'facility-gym', 'label' => 'Gym', 'route' => 'tenant.facilities.gym', 'permission' => 'view-facility-attendance', 'feature' => 'facilities'],
                ['id' => 'facility-pool-report', 'label' => 'Pool report', 'route' => 'tenant.reports.pool-attendance', 'permission' => 'view-facility-reports', 'feature' => 'facilities'],
                ['id' => 'facility-gym-report', 'label' => 'Gym report', 'route' => 'tenant.reports.gym-attendance', 'permission' => 'view-facility-reports', 'feature' => 'facilities'],
            ],
        ],
        [
            'id' => 'finance',
            'label' => 'Finance',
            'icon' => 'wallet',
            'children' => [
                ['id' => 'fb-sales', 'label' => 'F&B sales', 'route' => 'tenant.fb.orders.index', 'permission' => 'view-all-orders', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'debts', 'label' => 'Outstanding debts', 'route' => 'tenant.debts.index', 'permission' => 'view-debts', 'feature' => 'hotel'],
                ['id' => 'ancillary', 'label' => 'Extra services', 'route' => 'tenant.ancillary-services.index', 'permission' => 'view-ancillary-services', 'feature' => 'hotel'],
                ['id' => 'expenditures', 'label' => 'Expenditures', 'route' => 'tenant.expenditures.index', 'permission' => 'view-expenditures'],
            ],
        ],
        [
            'id' => 'reports-group',
            'label' => 'Reports',
            'icon' => 'document',
            'children' => [
                ['id' => 'reports', 'label' => 'Reports hub', 'route' => 'tenant.reports', 'permission_any' => ['view-reports', 'view-fb-reports', 'view-reservations', 'view-facility-reports'], 'features' => ['hotel', 'restaurant', 'bar', 'lounge', 'facilities']],
                ['id' => 'sales-summary-report', 'label' => 'Sales summary', 'route' => 'tenant.reports.sales-summary', 'permission' => 'view-reports', 'feature' => 'hotel'],
                ['id' => 'bar-sales-summary-report', 'label' => 'Bar item sales', 'route' => 'tenant.reports.bar-sales-summary', 'permission' => 'view-fb-reports', 'feature' => 'bar'],
                ['id' => 'lounge-sales-summary-report', 'label' => 'Lounge item sales', 'route' => 'tenant.reports.lounge-sales-summary', 'permission' => 'view-fb-reports', 'feature' => 'lounge'],
                ['id' => 'menu-item-sales-summary-report', 'label' => 'Menu item sales', 'route' => 'tenant.reports.menu-item-sales-summary', 'permission' => 'view-fb-reports', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'occupancy-report', 'label' => 'Occupancy', 'route' => 'tenant.reports.occupancy', 'permission' => 'view-reservations', 'feature' => 'hotel'],
                ['id' => 'room-reservation-reports', 'label' => 'Room reservations finance', 'route' => 'tenant.reports.room-reservations', 'permission' => 'view-reservations', 'feature' => 'hotel'],
                ['id' => 'room-reservations-summary-report', 'label' => 'Room reservations summary', 'route' => 'tenant.reports.room-reservations-summary', 'permission' => 'view-reservations', 'feature' => 'hotel'],
                ['id' => 'room-payments-accounting-reports', 'label' => 'Room payments & accounting', 'route' => 'tenant.reports.room-payments-accounting', 'permission' => 'view-reservations', 'feature' => 'hotel'],
                ['id' => 'payment-summary-report', 'label' => 'Payment collection', 'route' => 'tenant.reports.payment-summary', 'permission' => 'view-reports', 'feature' => 'hotel'],
                ['id' => 'fb-reports', 'label' => 'F&B revenue split', 'route' => 'tenant.reports.fb-revenue', 'permission' => 'view-fb-reports', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'fb-profitability-reports', 'label' => 'F&B profitability', 'route' => 'tenant.reports.fb-profitability', 'permission' => 'view-fb-reports', 'features' => ['restaurant', 'bar', 'lounge']],
            ],
        ],
        [
            'id' => 'admin',
            'label' => 'Administration',
            'icon' => 'users-cog',
            'children' => [
                ['id' => 'users', 'label' => 'Staff', 'route' => 'tenant.users.index', 'permission' => 'manage-users'],
                ['id' => 'roles', 'label' => 'Roles & permissions', 'route' => 'tenant.roles.index', 'permission' => 'manage-roles'],
                ['id' => 'finance-settings', 'label' => 'Finance & tax', 'route' => 'tenant.finance.settings.edit', 'permission' => 'manage-roles'],
                ['id' => 'payment-methods', 'label' => 'Payment methods', 'route' => 'tenant.finance.payment-methods.edit', 'permission' => 'manage-roles'],
                ['id' => 'fb-settings', 'label' => 'F&B settlement', 'route' => 'tenant.finance.fb-settings.edit', 'permission' => 'manage-roles', 'features' => ['restaurant', 'bar', 'lounge']],
                ['id' => 'facility-settings', 'label' => 'Facility fees', 'route' => 'tenant.finance.facility-settings.edit', 'permission' => 'manage-roles', 'feature' => 'facilities'],
            ],
        ],
    ],
];

<?php

declare(strict_types=1);

return [
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
            'timezone' => env('NEXSTAY_REPORT_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
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
    | HBMS web navigation (tenant UI)
    |--------------------------------------------------------------------------
    | permission: null = any authenticated staff member
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
            'children' => [
                ['id' => 'availability', 'label' => 'Availability', 'route' => 'tenant.availability', 'permission' => 'view-availability'],
                ['id' => 'reservations', 'label' => 'Reservations', 'route' => 'tenant.reservations.index', 'permission' => 'view-reservations'],
                ['id' => 'booked-list', 'label' => 'Booked list', 'route' => 'tenant.booked-list.index', 'permission' => 'view-reservations'],
                ['id' => 'time-left', 'label' => 'Time left', 'route' => 'tenant.time-left.index', 'permission' => 'view-reservations'],
                ['id' => 'guests', 'label' => 'Guests', 'route' => 'tenant.guests.index', 'permission' => 'view-guests'],
                ['id' => 'room-types', 'label' => 'Room types', 'route' => 'tenant.room-types.index', 'permission' => 'view-rooms'],
                ['id' => 'rooms', 'label' => 'Rooms', 'route' => 'tenant.rooms.index', 'permission' => 'view-rooms'],
                ['id' => 'rate-plans', 'label' => 'Rate plans', 'route' => 'tenant.rate-plans.index', 'permission' => 'view-reservations'],
                ['id' => 'damage', 'label' => 'Damage', 'route' => 'tenant.damages.index', 'permission' => 'view-damage'],
            ],
        ],
        [
            'id' => 'fb',
            'label' => 'Food & beverage',
            'icon' => 'restaurant',
            'children' => [
                ['id' => 'restaurant', 'label' => 'Restaurant', 'route' => 'tenant.restaurant.index', 'permission' => 'view-orders'],
                ['id' => 'bar', 'label' => 'Bar', 'route' => 'tenant.bar.index', 'permission' => 'view-orders'],
                ['id' => 'lounge', 'label' => 'Lounge', 'route' => 'tenant.lounge.index', 'permission' => 'view-orders'],
                ['id' => 'fb-orders', 'label' => 'Sales', 'route' => 'tenant.fb.orders.index', 'permission' => 'view-orders'],
                ['id' => 'shift-mine', 'label' => 'My shift', 'route' => 'tenant.shift.mine', 'permission' => 'view-orders'],
                ['id' => 'shift-all', 'label' => 'Staff shift', 'route' => 'tenant.shift.all', 'permission' => 'view-fb-reports'],
                ['id' => 'outlets', 'label' => 'Outlets', 'route' => 'tenant.outlets.index', 'permission' => 'view-outlets'],
                ['id' => 'menu-categories', 'label' => 'Menu categories', 'route' => 'tenant.menu-categories.index', 'permission' => 'view-menu'],
                ['id' => 'menu', 'label' => 'Menu items', 'route' => 'tenant.menu-items.index', 'permission' => 'view-menu'],
                ['id' => 'till', 'label' => 'Till', 'route' => 'tenant.till.index', 'permission' => 'view-till'],
            ],
        ],
        [
            'id' => 'inventory-group',
            'label' => 'Inventory',
            'icon' => 'inventory',
            'children' => [
                ['id' => 'inventory', 'label' => 'Stock items', 'route' => 'tenant.stock-items.index', 'permission' => 'view-inventory'],
                ['id' => 'purchases', 'label' => 'Purchases', 'route' => 'tenant.purchases.index', 'permission' => 'view-purchases'],
            ],
        ],
        [
            'id' => 'finance',
            'label' => 'Finance',
            'icon' => 'wallet',
            'children' => [
                ['id' => 'debts', 'label' => 'Outstanding debts', 'route' => 'tenant.debts.index', 'permission' => 'view-debts'],
                ['id' => 'ancillary', 'label' => 'Extra services', 'route' => 'tenant.ancillary-services.index', 'permission' => 'view-ancillary-services'],
                ['id' => 'expenditures', 'label' => 'Expenditures', 'route' => 'tenant.expenditures.index', 'permission' => 'view-expenditures'],
            ],
        ],
        [
            'id' => 'reports-group',
            'label' => 'Reports',
            'icon' => 'document',
            'children' => [
                ['id' => 'reports', 'label' => 'Reports hub', 'route' => 'tenant.reports', 'permission' => 'view-reservations'],
                ['id' => 'occupancy-report', 'label' => 'Occupancy', 'route' => 'tenant.reports.occupancy', 'permission' => 'view-reservations'],
                ['id' => 'room-reservation-reports', 'label' => 'Room reservations finance', 'route' => 'tenant.reports.room-reservations', 'permission' => 'view-reservations'],
                ['id' => 'room-payments-accounting-reports', 'label' => 'Room payments & accounting', 'route' => 'tenant.reports.room-payments-accounting', 'permission' => 'view-reservations'],
                ['id' => 'payment-summary-report', 'label' => 'Payment collection', 'route' => 'tenant.reports.payment-summary', 'permission' => 'view-reports'],
                ['id' => 'fb-reports', 'label' => 'F&B revenue split', 'route' => 'tenant.reports.fb-revenue', 'permission' => 'view-fb-reports'],
                ['id' => 'fb-profitability-reports', 'label' => 'F&B profitability', 'route' => 'tenant.reports.fb-profitability', 'permission' => 'view-fb-reports'],
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
                ['id' => 'fb-settings', 'label' => 'F&B settlement', 'route' => 'tenant.finance.fb-settings.edit', 'permission' => 'manage-roles'],
            ],
        ],
    ],
];

<?php

return [
    'seed_users_enabled' => (bool) env('POS_SEED_USERS', false),

    /*
     * Batas nominal katalog. Mencegah input tidak wajar dan overflow
     * saat nominal dikalikan quantity di fase order (lihat BUSINESS-RULES §4).
     */
    'catalog' => [
        'max_price' => 1_000_000_000,
        'browse_page_size' => 24,
        'admin_page_size' => 15,
    ],

    /*
     * Batas operasional order. Quantity dibatasi untuk mencegah input tidak
     * wajar dan overflow saat dikalikan harga (BUSINESS-RULES §3, §4).
     */
    'orders' => [
        'max_quantity' => 100,
        'notes_max_length' => 500,
        'page_size' => 15,
        'catalog_page_size' => 24,
    ],

    'tables' => [
        'page_size' => 60,
    ],

    'takeaway' => [
        'catalog_page_size' => 24,
        'max_items' => 50,
    ],

    'shifts' => [
        'page_size' => 15,
        'max_opening_cash' => 1_000_000_000,
        'max_actual_cash' => 1_000_000_000_000,
    ],

    'reports' => [
        'top_items_limit' => 10,
    ],

    /*
     * Batas pembayaran. Nominal final tetap dihitung backend dari order item;
     * batas ini hanya mencegah input tidak wajar pada uang diterima tunai.
     */
    'payments' => [
        'max_received_amount' => 1_000_000_000_000,
        'idempotency_key_max_length' => 100,
    ],

    'user_seed_accounts' => [
        [
            'name' => 'Pemilik Rumah Makan',
            'username' => env('POS_OWNER_USERNAME', 'owner'),
            'email' => env('POS_OWNER_EMAIL', 'owner@example.test'),
            'password' => env('POS_OWNER_PASSWORD'),
            'role' => 'owner',
        ],
        [
            'name' => 'Administrator',
            'username' => env('POS_ADMIN_USERNAME', 'admin'),
            'email' => env('POS_ADMIN_EMAIL', 'admin@example.test'),
            'password' => env('POS_ADMIN_PASSWORD'),
            'role' => 'admin',
        ],
        [
            'name' => 'Kasir 01',
            'username' => env('POS_CASHIER_USERNAME', 'kasir01'),
            'email' => env('POS_CASHIER_EMAIL', 'kasir01@example.test'),
            'password' => env('POS_CASHIER_PASSWORD'),
            'role' => 'kasir',
        ],
    ],
];

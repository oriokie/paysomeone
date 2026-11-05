<?php

/**
 * Safety switches for running beside the current paysomeonetowrite.com site.
 *
 * READ_ONLY_LEGACY must stay true on any server that points at the live
 * database. It refuses inserts, updates, and deletes on prox_ tables.
 * SAFE_MODE must stay true until email, SMS, and card/M-Pesa calls are
 * deliberately turned on. Neither switch phones home or checks a licence.
 */
return [

    'read_only' => filter_var(env('READ_ONLY_LEGACY', true), FILTER_VALIDATE_BOOL),

    'safe_mode' => filter_var(env('SAFE_MODE', true), FILTER_VALIDATE_BOOL),

    'table_prefix' => 'prox_',

    'site_id' => (int) env('LEGACY_SITE_ID', 1),

    'admin_site_id' => (int) env('LEGACY_ADMIN_SITE_ID', env('LEGACY_SITE_ID', 1)),

    /*
     * The current order system starts an empty customer list at this id
     * and then takes MAX(customer_id) + 2. Orders start at 11233 and step
     * by ORDER_AUTOINCREMENT (usually 2). Matching that keeps ids from
     * colliding when only one of the two sites is allowed to write.
     */
    'customer_start_id' => 452441,

    'order_start_id' => 11233,

    'order_id_step' => (int) env('LEGACY_ORDER_ID_STEP', 2),

    /*
     * Demo seeders refuse to run unless this is true, and they still refuse
     * if the database name is the live one (tutoress_paysomeone).
     */
    'allow_seed' => filter_var(env('LEGACY_ALLOW_SEED', false), FILTER_VALIDATE_BOOL),

    'live_database' => env('LEGACY_LIVE_DATABASE', 'tutoress_paysomeone'),

    /*
     * Order files live here, outside the web root. Point this at the same
     * uploads folder the current site uses so both sites open the same files.
     * The value stored in prox_order_file.source is the path relative to this folder.
     */
    'uploads_path' => env('LEGACY_UPLOADS_PATH', storage_path('app/legacy-uploads')),

];

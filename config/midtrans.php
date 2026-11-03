<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Midtrans Core API and Snap integrations.
    | Keys are loaded from environment variables to prevent hardcoding.
    |
    */

    'server_key' => env('SERVER_KEY', env('MIDTRANS_SERVER_KEY', 'SB-Mid-server-demo-key')),

    'client_key' => env('CLIENT_KEY', env('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-demo-key')),

    'is_production' => env('IS_PRODUCTION', env('MIDTRANS_IS_PRODUCTION', false)),

    'is_sanitized' => true,

    'is_3ds' => true,

    /*
    |--------------------------------------------------------------------------
    | Payment Simulation Fallback
    |--------------------------------------------------------------------------
    |
    | When enabled, generates QRIS simulation and demo shortcuts so the app
    | can still be demonstrated if the sandbox network fails or credentials
    | are in test mode.
    |
    */
    'payment_simulation' => env('PAYMENT_SIMULATION', true),
];

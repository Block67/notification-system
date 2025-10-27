<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID Configuration
    |--------------------------------------------------------------------------
    |
    | Voluntary Application Server Identification (VAPID) for Web Push
    | Generate keys using: php artisan webpush:vapid
    |
    */

    'subject' => env('WEBPUSH_SUBJECT', 'mailto:your-email@example.com'),
    
    'public_key' => env('WEBPUSH_PUBLIC_KEY'),
    
    'private_key' => env('WEBPUSH_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Push Notification Settings
    |--------------------------------------------------------------------------
    */

    'ttl' => env('WEBPUSH_TTL', 2419200), // 4 weeks default

    'urgency' => env('WEBPUSH_URGENCY', 'normal'), // very-low, low, normal, high

    'topic' => env('WEBPUSH_TOPIC', null),
];
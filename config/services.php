<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // SIMPEL DINSOS — gateway notifikasi outbound
    'notifications' => [
        // Driver utama: log | fonnte | wablas | cloud | email
        //   log    = simpan ke storage (dev / demo)
        //   fonnte = api.fonnte.com (gratis 100/hari, paling praktis)
        //   wablas = wablas.com (trial terbatas)
        //   cloud  = WhatsApp Cloud API resmi Meta (gratis 1000 conversation/bulan)
        //   email  = pakai Laravel Mail (SMTP)
        'driver' => env('NOTIFICATION_DRIVER', 'log'),

        // Auto-fallback ke email kalau WA gagal (untuk notif non-OTP)
        'fallback_email' => env('NOTIFICATION_FALLBACK_EMAIL', false),

        // Fonnte (https://fonnte.com)
        'fonnte_token' => env('FONNTE_TOKEN'),

        // Wablas (https://wablas.com)
        'wablas_token' => env('WABLAS_TOKEN'),
        'wablas_domain' => env('WABLAS_DOMAIN', 'https://solo.wablas.com'),

        // WhatsApp Cloud API resmi Meta (https://developers.facebook.com/docs/whatsapp/cloud-api)
        'whatsapp_token' => env('WHATSAPP_TOKEN'),
        'whatsapp_phone_id' => env('WHATSAPP_PHONE_ID'),

        // Token webhook verifikasi inbound dari gateway
        'webhook_token' => env('NOTIFICATION_WEBHOOK_TOKEN'),
    ],

    // PIN e-sign Operator Pekon (Kepala Pekon) — JANGAN hardcode di kode.
    'esign' => [
        'pekon_pin' => env('ESIGN_PEKON_PIN'),
    ],

    // Stub integrasi Dukcapil
    'dukcapil' => [
        'driver' => env('DUKCAPIL_DRIVER', 'mock'),
        'base_url' => env('DUKCAPIL_BASE_URL'),
        'token' => env('DUKCAPIL_TOKEN'),
    ],

    // Stub integrasi DTSEN
    'dtsen' => [
        'driver' => env('DTSEN_DRIVER', 'mock'),
        'base_url' => env('DTSEN_BASE_URL'),
        'token' => env('DTSEN_TOKEN'),
    ],

    // SP4N Lapor.go.id (Kemenpan RB)
    'lapor' => [
        'driver' => env('LAPOR_DRIVER', 'mock'),
        'base_url' => env('LAPOR_BASE_URL'),
        'token' => env('LAPOR_TOKEN'),
        'instansi_id' => env('LAPOR_INSTANSI_ID'),
    ],

    // BSrE BSSN — Tanda Tangan Elektronik Tersertifikasi
    'bsre' => [
        'driver' => env('BSRE_DRIVER', 'mock'),       // mock | http
        'enabled' => env('BSRE_ENABLED', false),       // aktifkan sign otomatis pada Terbitkan Surat
        'base_url' => env('BSRE_BASE_URL'),
        'user' => env('BSRE_USER'),
        'pass' => env('BSRE_PASS'),
        'passphrase' => env('BSRE_PASSPHRASE'),
    ],

    // Web Push (VAPID)
    'push' => [
        'vapid_public_key' => env('VAPID_PUBLIC_KEY'),
        'vapid_private_key' => env('VAPID_PRIVATE_KEY'),
        'vapid_subject' => env('VAPID_SUBJECT', 'mailto:admin@simpel-dinsos.local'),
    ],

];

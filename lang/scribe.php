<?php

// Teks halaman dokumentasi API (Scribe) dalam Bahasa Indonesia.
return [
    'labels' => [
        'search' => 'Cari',
        'base_url' => 'URL dasar',
    ],

    'auth' => [
        'none' => 'API ini tidak memerlukan autentikasi.',
        'instruction' => [
            'query' => <<<'TEXT'
                Untuk autentikasi, sertakan query parameter **`:parameterName`** pada request.
                TEXT,
            'body' => <<<'TEXT'
                Untuk autentikasi, sertakan parameter **`:parameterName`** di body request.
                TEXT,
            'query_or_body' => <<<'TEXT'
                Untuk autentikasi, sertakan parameter **`:parameterName`** di query string atau body request.
                TEXT,
            'bearer' => <<<'TEXT'
                Untuk autentikasi, sertakan header **`Authorization`** dengan nilai **`"Bearer :placeholder"`**.
                TEXT,
            'basic' => <<<'TEXT'
                Untuk autentikasi, sertakan header **`Authorization`** dengan format **`"Basic {credentials}"`**.
                Nilai `{credentials}` adalah username/id dan password yang digabung dengan titik dua (:),
                lalu di-encode base64.
                TEXT,
            'header' => <<<'TEXT'
                Untuk autentikasi, sertakan header **`:parameterName`** dengan nilai **`":placeholder"`**.
                TEXT,
        ],
        'details' => <<<'TEXT'
            Semua endpoint yang memerlukan login ditandai badge `requires authentication` di bawah.
            TEXT,
    ],

    'headings' => [
        'introduction' => 'Pengantar',
        'auth' => 'Autentikasi request',
    ],

    'endpoint' => [
        'request' => 'Request',
        'headers' => 'Header',
        'url_parameters' => 'Parameter URL',
        'body_parameters' => 'Parameter body',
        'query_parameters' => 'Parameter query',
        'response' => 'Response',
        'response_fields' => 'Kolom response',
        'example_request' => 'Contoh request',
        'example_response' => 'Contoh response',
        'responses' => [
            'binary' => 'Data biner (file)',
            'empty' => 'Response kosong',
        ],
    ],

    'try_it_out' => [
        'open' => 'Coba langsung',
        'cancel' => 'Batal',
        'send' => 'Kirim request',
        'loading' => 'Mengirim...',
        'received_response' => 'Response diterima',
        'request_failed' => 'Request gagal dengan error',
        'error_help' => <<<'TEXT'
            Tips: pastikan koneksi jaringan tersambung dan backend (`php artisan serve`) sedang berjalan.
            Detail error dapat dilihat di console Dev Tools browser.
            TEXT,
    ],

    'links' => [
        'postman' => 'Lihat koleksi Postman',
        'openapi' => 'Lihat spesifikasi OpenAPI',
    ],
];

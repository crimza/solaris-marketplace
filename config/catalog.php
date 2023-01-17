<?php
/**
 * File: catalog.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */
return [
    'application_id' => env('APP_ID', 'ABCDEF'), // id приложения, должно быть уникально для каждого инстанса приложения на сервере,
    'application_api_key' => env('APP_API_KEY', 'APIKEY'), // ключ для доступа к API магазина
    'catalog_encryption_key' => base64_decode('MlLOSekaAtCsLfMUczm+lblKNNeXHHou7NrnslKp+l0='),
    'eos_api_key' => '86fd1620366fda4b8a698382ed77d28f36971201',

    'sync_server' => env('SYNC_SERVER', ''),
    'auth_server' => env('AUTH_SERVER', ''),

    'rates_cache_expires_at' => 30, // сколько максимум минут может жить курс до того, как платёжка отключится
    'shop_expires_at' => 20, // сколько максимум минут может жить шоп до того, как будет считаться недоступным
    'order_quest_time' => 24, // сколько часов показывать квест после покупки
    'preorder_close_time' => 14 * 24,
    'application_title' => env('APP_TITLE', ''), // название сайта в title
    'header_title' => env('APP_HEADER_TITLE', ''), // название сайта в шапке
    'footer_title' => env('APP_FOOTER_TITLE', NULL), // текст в футере

    'api_log_level' => env('API_LOG_LEVEL', 'warning'),
    'img_fetcher_log_level' => env('IMG_FETCHER_LOG_LEVEL', 'warning'),
    'reset_cache_log_level' => env('RESET_CACHE_LOG_LEVEL', 'warning'),

    'exchanges_encryption_key' => '240b7b2bb27db58ba00458139011a3e3',
    'exchanges_api_url' => env('EXCHANGES_API_URL', 'http://wvgwbaeyvhqxsbu6iridblkn45egzw6c4hdme74bpvgwpyaz72w2nfid.onion'),

    'fb_max_opened_tickets' => intval(env('MAX_OPENED_TICKETS', '3')), // максимум открытых тикетов на одном акке

    // tor
    'tord_host' => env('TOR_HOST', '127.0.0.1'),
    'tord_port' => env('TOR_PORT', 9050),
    // доп инстансы, значения через запятую: socks5h://127.0.0.1:9050,socks5h://127.0.0.1:8889
    'tor_hosts' => env('TOR_ADDITIONAL_CLIENTS', ''),

    // таймауты подключения для выкачивания картинок. локально и через тор.
    'guzzle_local_image_fetch_timeout' => env('GUZZLE_LOCAL_IMAGE_FETCH_TIMEOUT', 5),
    'guzzle_onion_image_fetch_timeout' => env('GUZZLE_ONION_IMAGE_FETCH_TIMEOUT', 10),
];
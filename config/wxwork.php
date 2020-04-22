<?php


return [
    // 服务商corpid
    'corpid' => env('WXWORK_CORPID', 'xxxx'),
    // 服务商注册二维码模板id
    'templet_id' => env('WXWORK_TEMPLETID', 'xxxxx'),
    // 服务商密钥
    'provider_secret' => env('WXWORK_PROVIDER_SECRET', 'xxxxx'),
    // 服务商token
    'provider_token' => env('WXWORK_PROVIDER_TOKEN', 'xxxxx'),
    // 服务商encoding_aes_key
    'provider_encoding_aes_key' => env('WXWORK_PROVIDER_ENCODING_AES_KEY', 'xxxxxx'),

    // 应用信息
    'suiteid' => env('WXWORK_SUITEID', 'xxxx'),
    'suite_secret' => env('WXWORK_SUITE_SECRET', 'xxxx'),
    'suite_token' => env('WXWORK_SUITE_TOKEN', 'xxxxx'),
    'suite_encoding_aes_key' => env('WXWORK_SUITE_ENCODING_AES_KEY', 'xxxxxx'),

    // 前端域名
    'front_domain' => env('WXWORK_FRONT_DOMAIN', 'http://venue.com'),
];

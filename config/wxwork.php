<?php


return [
    // 服务商corpid
    'corpid' => env('WXWORK_CORPID', 'ww86b1fd850557439a'),
    // 服务商注册二维码模板id
    'templet_id' => env('WXWORK_TEMPLETID', 'tpl6e2231a7905d592d'),
    // 服务商密钥
    'provider_secret' => env('WXWORK_PROVIDER_SECRET', 'N15GwN0bS9264WOIdx3e4QeWdCUr1-vukyRKdPcsT9XqwwXnmqM__CjDPHnuKvz7'),
    // 服务商token
    'provider_token' => env('WXWORK_PROVIDER_TOKEN', '6s9PENfqeqqQqxyV'),
    // 服务商encoding_aes_key
    'provider_encoding_aes_key' => env('WXWORK_PROVIDER_ENCODING_AES_KEY', 'DVWcHZFaRBmVjAjyNa2CoA182b9buT6ndfo6cnPCBES'),

    // 应用信息
    'suiteid' => env('WXWORK_SUITEID', 'wwc17b0f9a29b16250'),
    'suite_secret' => env('WXWORK_SUITE_SECRET', 'BL5QgaD0f969DPOpwW9f8eAYK9n0KyTOXFrMLfdg38I'),
    'suite_token' => env('WXWORK_SUITE_TOKEN', 'AZGFlU96yE8vTri'),
    'suite_encoding_aes_key' => env('WXWORK_SUITE_ENCODING_AES_KEY', 'uNJFCJ94oexqb393IFSfuVTE4mxyLDpLQk4lmzyFrUU'),
];

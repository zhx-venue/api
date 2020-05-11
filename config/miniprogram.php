<?php

// +----------------------------------------------------------------------
// | 小程序配置设置
// | 为了支持多小程序端，.env小程序配置如下
// +----------------------------------------------------------------------
/**
 * 默认小程序配置如下
[MINIPROGRAM]
APPID = wx33b0ef302fe58a24
SECRET = 6b581c4b18cf5eaa45e11efe3eb5e021

 * 其他的小程序配置如下
[wx33b0ef302fe58a24]
APPID = wx33b0ef302fe58a24
SECRET = 6b581c4b18cf5eaa45e11efe3eb5e021

[wx33b0ef302fe58a25]
APPID = wx33b0ef302fe58a25
SECRET = 6b581c4b18cf5eaa45e11efe3eb5e021
 *
 */

// +----------------------------------------------------------------------
// | 使用时先读取 env('{appid}_APPID')
// | 如果值为null，再使用默认的小程序 config('miniprogram')
// +----------------------------------------------------------------------

return [
    'appid'     => env('MINIPROGRAM_APPID', 'xxxxx'),
    'secret'    => env('MINIPROGRAM_SECRET', 'xxxxxxxxx'),
];

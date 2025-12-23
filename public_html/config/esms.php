<?php
return [
    'guard' => 'esms', // ESMS 登錄所使用的guard
    'allowed_domains' => [/*  '127.0.0.1',  */'esms_mvc.utrust.com.tw', 'esms.moenv.gov.tw'/* , '106.104.136.240', '211.75.1.168' */],
    'passphrase' => 'i9hPK8MN',
    'auth_levels' => [
        'all_areas' => 1, // 可以查看所有區域
        'specific_area' => 2, // 只能查看傳遞的區域
    ],
];

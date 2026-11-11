<?php

$loginUrl = rtrim((string) env('ALAMIN_LOGIN_URL', ''), '/');

return [
    'url' => $loginUrl,
    'portal_url' => $loginUrl === '' ? null : $loginUrl.'/systems',
    'client_id' => env('ALAMIN_LOGIN_CLIENT_ID', 'aidid'),
    'client_secret' => env('ALAMIN_LOGIN_CLIENT_SECRET'),
    'connect_timeout' => (int) env('ALAMIN_LOGIN_CONNECT_TIMEOUT', 3),
    'timeout' => (int) env('ALAMIN_LOGIN_TIMEOUT', 8),
    'require_https' => filter_var(
        env('ALAMIN_LOGIN_REQUIRE_HTTPS', true),
        FILTER_VALIDATE_BOOL,
    ),
];

<?php

$enabled = static fn (string $name, bool $default = false): bool => filter_var(
    env($name, $default),
    FILTER_VALIDATE_BOOL,
);

$baseUrl = rtrim((string) env('ALAMIN_LOGIN_URL', ''), '/');

return [
    'url' => $baseUrl,
    'systems_url' => $baseUrl === '' ? '' : $baseUrl.'/systems',
    'redeem_url' => $baseUrl === '' ? '' : $baseUrl.'/sso/redeem',
    'client_id' => (string) env('ALAMIN_LOGIN_CLIENT_ID', 'nureen'),
    'client_secret' => (string) env('ALAMIN_LOGIN_CLIENT_SECRET', ''),
    'require_https' => $enabled('ALAMIN_LOGIN_REQUIRE_HTTPS', true),
    'connect_timeout' => max(1, (int) env('ALAMIN_LOGIN_CONNECT_TIMEOUT', 3)),
    'timeout' => max(1, (int) env('ALAMIN_LOGIN_TIMEOUT', 8)),
];

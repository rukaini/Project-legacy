<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Al Amin Login (SSO gateway)
    |--------------------------------------------------------------------------
    | Values come from the private .env of this system. Never commit the secret.
    */

    'url' => rtrim((string) env('ALAMIN_LOGIN_URL', ''), '/'),

    // Where logout / failed SSO sends the user. Al Amin Login itself keeps its
    // own session, so the user can pick another system without logging in again.
    'portal_url' => rtrim((string) env('ALAMIN_LOGIN_URL', ''), '/'),

    'client_id' => env('ALAMIN_LOGIN_CLIENT_ID'),
    'client_secret' => env('ALAMIN_LOGIN_CLIENT_SECRET'),

    'connect_timeout' => (int) env('ALAMIN_LOGIN_CONNECT_TIMEOUT', 3),
    'timeout' => (int) env('ALAMIN_LOGIN_TIMEOUT', 5),

    // Set ALAMIN_LOGIN_REQUIRE_HTTPS=false only where Al Amin Login is served over plain HTTP.
    'require_https' => filter_var(env('ALAMIN_LOGIN_REQUIRE_HTTPS', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Roles allowed into THIS system (TYA / Human Resources System)
    |--------------------------------------------------------------------------
    | Only HR is allowed. Supported keys: hr, principal, teacher.
    | Each role lists the identity "source" values Al Amin Login may send for it.
    */

    'allowed_roles' => ['hr'],

    'role_sources' => [
        'hr' => ['hr_administrator', 'hr'],
        'principal' => ['principal'],
        'teacher' => ['teacher'],
    ],
];

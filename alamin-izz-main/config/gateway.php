<?php

use App\Enums\Role;

return [

    /*
    | Al Amin Login (SSO gateway). Secret must only live in the private server
    | environment (.env), never in the repository.
    */
    'url' => env('ALAMIN_LOGIN_URL'),
    'portal_url' => env('ALAMIN_LOGIN_URL'),
    'client_id' => env('ALAMIN_LOGIN_CLIENT_ID'),
    'client_secret' => env('ALAMIN_LOGIN_CLIENT_SECRET'),
    'connect_timeout' => (int) env('ALAMIN_LOGIN_CONNECT_TIMEOUT', 3),
    'timeout' => (int) env('ALAMIN_LOGIN_TIMEOUT', 5),
    'require_https' => filter_var(env('ALAMIN_LOGIN_REQUIRE_HTTPS', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | Map the gateway's "role|source" pair to a local Role value. Deliberately
    | empty until the real values are confirmed: unmapped identities are denied
    | (and their role/source logged) rather than guessed. Example:
    |
    |   'teacher|teacher' => Role::Teacher->value,
    */
    'roles' => [
        'outsider|outsider'  => Role::Outsider->value,    // Outsider — confirmed from gateway log 2026-09-29
        'principal|principal' => Role::GuruBesar->value,  // Principal — confirmed from gateway log 2026-09-29
        'teacher|teacher'    => Role::Teacher->value,      // Teacher — confirmed from gateway log 2026-09-29
        'new_teacher|guru_new' => Role::GuruBaru->value,   // New Teacher — confirmed from gateway log 2026-09-29
        'hr|hr_administrator' => Role::HR->value,          // HR — confirmed from gateway log 2026-09-29
    ],
];

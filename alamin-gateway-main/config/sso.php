<?php

$enabled = static fn (string $name, bool $default = false): bool => filter_var(
    env($name, $default),
    FILTER_VALIDATE_BOOL,
);

return [
    'ttl_seconds' => (int) env('SSO_CODE_TTL', 90),
    'cache_store' => env('SSO_CACHE_STORE', 'file'),
    'require_https' => $enabled('ALAMIN_LOGIN_REQUIRE_HTTPS', true),
    'administrator' => [
        // Identification/protection marker only. This never grants a role.
        'email' => env('ELIT_ADMIN_EMAIL', ''),
        'reset_ttl_seconds' => (int) env('ELIT_ADMIN_RESET_TTL', 900),
        'reset_cache_store' => env('ELIT_ADMIN_RESET_CACHE_STORE', env('CACHE_STORE', 'file')),
    ],
    'systems' => [
        'aidid' => [
            'display_name' => 'Trainer Management System',
            'description' => 'Training administration for Staff EDU and trainers.',
            'enabled' => $enabled('SYSTEM_AIDID_ENABLED', true),
            'url' => env('SYSTEM_AIDID_URL', ''),
            'callback_path' => '/sso/callback',
            'secret' => env('ALAMIN_LOGIN_CLIENT_SECRET_AIDID'),
            'roles' => ['staff_edu', 'trainer'],
            'accent' => 'blue',
        ],
        'nureen' => [
            'display_name' => 'TrainHub',
            'description' => 'Training course administration for Staff EDU.',
            'enabled' => $enabled('SYSTEM_NUREEN_ENABLED'),
            'url' => env('SYSTEM_NUREEN_URL', ''),
            'callback_path' => '/sso/callback',
            'secret' => env('ALAMIN_LOGIN_CLIENT_SECRET_NUREEN'),
            'roles' => ['staff_edu'],
            'accent' => 'violet',
        ],
        'syiqin' => [
            'display_name' => 'Teacher Employment System',
            'description' => 'Teacher placement, observation and employment workspace.',
            'enabled' => $enabled('SYSTEM_SYIQIN_ENABLED'),
            'url' => env('SYSTEM_SYIQIN_URL', ''),
            'callback_path' => '/sso/callback',
            'secret' => env('ALAMIN_LOGIN_CLIENT_SECRET_SYIQIN'),
            'roles' => ['new_teacher', 'principal', 'staff_edu', 'hr', 'observer', 'external_observer'],
            'accent' => 'teal',
        ],
        'izz' => [
            'display_name' => 'EduTrain',
            'description' => 'Professional development and educator training workspace.',
            'enabled' => $enabled('SYSTEM_IZZ_ENABLED'),
            'url' => env('SYSTEM_IZZ_URL', ''),
            'callback_path' => '/sso/callback',
            'secret' => env('ALAMIN_LOGIN_CLIENT_SECRET_IZZ'),
            'roles' => ['teacher', 'new_teacher', 'outsider', 'principal', 'hr'],
            'accent' => 'amber',
        ],
        'tya' => [
            'display_name' => 'Human Resources System',
            'description' => 'Human resource administration for authorized HR officers.',
            'enabled' => $enabled('SYSTEM_TYA_ENABLED'),
            'url' => env('SYSTEM_TYA_URL', ''),
            'callback_path' => '/sso/callback',
            'secret' => env('ALAMIN_LOGIN_CLIENT_SECRET_TYA'),
            'roles' => ['hr'],
            'accent' => 'rose',
        ],
    ],
    'roles' => [
        'teacher' => ['label' => 'Teacher', 'systems' => ['izz']],
        'new_teacher' => ['label' => 'New Teacher', 'systems' => ['syiqin', 'izz']],
        'outsider' => ['label' => 'Public', 'systems' => ['izz']],
        'principal' => ['label' => 'Principal', 'systems' => ['syiqin', 'izz']],
        'staff_edu' => ['label' => 'Staff EDU', 'systems' => ['nureen', 'aidid', 'syiqin']],
        'hr' => ['label' => 'HR', 'systems' => ['syiqin', 'tya', 'izz']],
        'trainer' => ['label' => 'Trainer', 'systems' => ['aidid']],
        'observer' => ['label' => 'Observer', 'systems' => ['syiqin']],
        'external_observer' => ['label' => 'External Observer', 'systems' => ['syiqin']],
    ],
];

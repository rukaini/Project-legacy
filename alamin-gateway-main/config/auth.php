<?php

/*
| The gateway deliberately does not use Laravel's User model, web guard, or
| password broker. CanonicalIdentityService authenticates owner-controlled
| identity records and the resulting portal state lives only in the session.
*/
return [
    'defaults' => [
        'guard' => null,
        'passwords' => null,
    ],
    'guards' => [],
    'providers' => [],
    'passwords' => [],
    'password_timeout' => 10800,
];

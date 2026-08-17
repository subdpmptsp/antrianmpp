<?php

return [
    'initial_admin_password' => env('INITIAL_ADMIN_PASSWORD'),

    'operator_password_rotation_after' => env(
        'OPERATOR_PASSWORD_ROTATION_AFTER',
        '2026-08-17T00:00:00+07:00',
    ),
];

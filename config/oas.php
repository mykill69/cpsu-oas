<?php

return [
    /*
    | Institutional sign-in. Only accounts on this Google Workspace domain that the
    | QA office registered under Accounts can sign in. Leave empty to allow any
    | verified Google account that matches a registered email.
    */
    'allowed_domain' => env('OAS_ALLOWED_DOMAIN', 'cpsu.edu.ph'),

    /*
    | Email + password sign-in. Off once Google sign-in is configured; keep it on
    | only as a break-glass while setting up.
    */
    'allow_password_login' => env('OAS_ALLOW_PASSWORD_LOGIN', false),

    /*
    | The system Google account whose Drive holds every uploaded file.
    */
    'drive_account' => env('OAS_DRIVE_ACCOUNT', 'cpsu_accresys@cpsu.edu.ph'),
    'drive_root_name' => env('OAS_DRIVE_ROOT', 'CPSU OAS Accreditation Files'),

    // Keep a local copy after a file reaches Drive (faster previews, uses disk).
    'drive_keep_local' => env('OAS_DRIVE_KEEP_LOCAL', false),
];

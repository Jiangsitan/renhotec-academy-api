<?php

return [
    'sso_url'               => env('SSO_CENTER_URL', 'https://sso.example.com'),
    'client_id'             => env('SSO_CLIENT_ID', ''),
    'client_secret'         => env('SSO_CLIENT_SECRET', ''),
    'sso_uuid_namespace'    => env('SSO_UUID', '6ba7b810-9dad-11d1-80b4-00c04fd430c8'),
    'auto_create_user'      => env('SSO_AUTO_CREATE_USER', true),
    'auto_sync_user'        => env('SSO_AUTO_SYNC_USER', true),
    'auto_sync_permissions' => env('SSO_AUTO_SYNC_PERMISSIONS', true),
    'redirect_after_login'  => env('SSO_REDIRECT_AFTER_LOGIN', '/'),
    'api_timeout'           => env('SSO_API_TIMEOUT', 30),
];

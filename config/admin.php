<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Bootstrap Credentials
    |--------------------------------------------------------------------------
    |
    | Used by the `app:ensure-admin-user-exists` command to create the single
    | admin account on first boot in environments with no shell access (e.g.
    | Render's free tier). Set ADMIN_PASSWORD explicitly in production; if
    | left unset, a random password is generated and printed once to the
    | command output.
    |
    */

    'email' => env('ADMIN_EMAIL', 'admin@attendease.com'),

    'name' => env('ADMIN_NAME', 'Admin'),

    'password' => env('ADMIN_PASSWORD'),

];

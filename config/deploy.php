<?php

return [

    /*
     * The one-time installer stays off until this is a long random string.
     * After the lock file is written, the installer stays off even if the
     * token is still set. The token is never printed.
     */
    'token' => env('INSTALL_TOKEN', ''),

    'lock' => env('INSTALL_LOCK', storage_path('framework/install.lock')),

    'env_file' => env('DEPLOY_ENV_FILE', base_path('.env')),

];

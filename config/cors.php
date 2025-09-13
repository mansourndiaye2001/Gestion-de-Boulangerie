<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Ici tu définis comment ton API accepte les requêtes cross-origin.
    | En dev, on autorise Angular (http://localhost:4200).
    | En prod, tu pourras mettre l’URL réelle de ton front.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 🔴 Mets l’URL de ton frontend Angular ici
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];

<?php

return [
    'paths' => ['api/*', 'storage/*', 'sanctum/csrf-cookie', '*'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => ['*'], // Untuk development
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => false, // Set false untuk development
];
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Rotas que devem permitir CORS
    'allowed_methods' => ['*'], // Métodos permitidos (GET, POST, etc.)
    'allowed_origins' => ['*'], // Domínios permitidos
    'allowed_origins_patterns' => [], // Padrões de domínios permitidos
    'allowed_headers' => ['*'], // Cabeçalhos permitidos
    'exposed_headers' => [], // Cabeçalhos expostos
    'max_age' => 0, // Tempo de cache do CORS (em segundos)
    'supports_credentials' => false, // Permitir credenciais (cookies, tokens, etc.)
];

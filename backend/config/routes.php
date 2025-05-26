<?php
return [
    'public' => [
        '/api/auth/login',
        '/api/auth/register',
        '/api/auth/forgot-password',
        '/api/auth/reset-password',
        '/api/projects/public',
        '/api/categories',
        '/api/search'
    ],
    'protected' => [
        'user' => [
            '/api/user/profile',
            '/api/user/settings',
            '/api/projects/fund',
            '/api/projects/follow'
        ],
        'creator' => [
            '/api/creator/projects',
            '/api/creator/rewards',
            '/api/creator/backers',
            '/api/creator/analytics'
        ],
        'admin' => [
            '/api/admin/users',
            '/api/admin/projects',
            '/api/admin/reports',
            '/api/admin/settings'
        ]
    ],
    'middleware' => [
        'auth' => 'AuthMiddleware',
        'cors' => 'CorsMiddleware',
        'rate_limit' => 'RateLimitMiddleware'
    ]
]; 
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains pagination settings for different device types.
    | The system will automatically detect the device and apply the
    | appropriate pagination strategy.
    |
    */

    'web' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
        'use_cursor' => false,
    ],

    'mobile' => [
        'default_per_page' => 20,
        'max_per_page' => 50,
        'use_cursor' => true,
    ],

    'tablet' => [
        'default_per_page' => 20,
        'max_per_page' => 75,
        'use_cursor' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Detection
    |--------------------------------------------------------------------------
    |
    | Configure how the system detects device types from User-Agent headers.
    |
    */

    'detection' => [
        'enabled' => true,
        'header' => 'User-Agent',
        'mobile_patterns' => [
            'Mobile',
            'Android',
            'iPhone',
            'iPad',
            'iPod',
            'BlackBerry',
            'Windows Phone',
        ],
        'tablet_patterns' => [
            'iPad',
            'Android.*Tablet',
            'Tablet',
        ],
    ],

];

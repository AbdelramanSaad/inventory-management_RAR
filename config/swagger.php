<?php

return [
    'api' => [
        /*
        |--------------------------------------------------------------------------
        | Edit to set the api's title
        |--------------------------------------------------------------------------
        */
        'title' => 'Inventory Management System API',

        /*
        |--------------------------------------------------------------------------
        | Edit to set the api's version
        |--------------------------------------------------------------------------
        */
        'version' => '1.0.0',

        /*
        |--------------------------------------------------------------------------
        | Edit to set the api's description
        |--------------------------------------------------------------------------
        */
        'description' => 'API documentation for the Inventory Management and Audit System',

        /*
        |--------------------------------------------------------------------------
        | Edit to set the api's base path
        |--------------------------------------------------------------------------
        */
        'base_path' => '/api',

        /*
        |--------------------------------------------------------------------------
        | Security definitions
        |--------------------------------------------------------------------------
        */
        'securityDefinitions' => [
            'JWT' => [
                'type' => 'apiKey',
                'name' => 'Authorization',
                'in' => 'header',
                'description' => 'Enter token in format: Bearer {token}',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Edit to set the api's schemes
        |--------------------------------------------------------------------------
        */
        'schemes' => [
            'http',
            'https',
        ],

        /*
        |--------------------------------------------------------------------------
        | Edit to set the api's consumes
        |--------------------------------------------------------------------------
        */
        'consumes' => [
            'application/json',
            'multipart/form-data',
        ],

        /*
        |--------------------------------------------------------------------------
        | Edit to set the api's produces
        |--------------------------------------------------------------------------
        */
        'produces' => [
            'application/json',
        ],
    ],

    'routes' => [
        /*
        |--------------------------------------------------------------------------
        | Route for accessing api documentation interface
        |--------------------------------------------------------------------------
        */
        'api' => 'api/documentation',

        /*
        |--------------------------------------------------------------------------
        | Route for accessing parsed swagger annotations.
        |--------------------------------------------------------------------------
        */
        'docs' => 'docs',

        /*
        |--------------------------------------------------------------------------
        | Route for Oauth2 authentication callback.
        |--------------------------------------------------------------------------
        */
        'oauth2_callback' => 'api/oauth2-callback',

        /*
        |--------------------------------------------------------------------------
        | Middleware allows to prevent unexpected access to API documentation
        |--------------------------------------------------------------------------
        */
        'middleware' => [
            'api' => [],
            'asset' => [],
            'docs' => [],
            'oauth2_callback' => [],
        ],
    ],

    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Absolute path to location where parsed swagger annotations will be stored
        |--------------------------------------------------------------------------
        */
        'docs' => storage_path('api-docs'),

        /*
        |--------------------------------------------------------------------------
        | File name of the generated json documentation file
        |--------------------------------------------------------------------------
        */
        'docs_json' => 'api-docs.json',

        /*
        |--------------------------------------------------------------------------
        | File name of the generated YAML documentation file
        |--------------------------------------------------------------------------
        */
        'docs_yaml' => 'api-docs.yaml',

        /*
        |--------------------------------------------------------------------------
        | Absolute paths to directory containing the swagger annotations are stored.
        |--------------------------------------------------------------------------
        */
        'annotations' => [
            base_path('app/Http/Controllers/Api'),
            base_path('app/Models'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Absolute path to directory where to export views
        |--------------------------------------------------------------------------
        */
        'views' => base_path('resources/views/vendor/swagger'),

        /*
        |--------------------------------------------------------------------------
        | Edit to set the api's base path
        |--------------------------------------------------------------------------
        */
        'base' => env('L5_SWAGGER_BASE_PATH', null),

        /*
        |--------------------------------------------------------------------------
        | Edit to set path where swagger ui assets should be stored
        |--------------------------------------------------------------------------
        */
        'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
    ],

    'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

    'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),

    'proxy' => false,

    /*
    |--------------------------------------------------------------------------
    | Add constants which can be used in annotations
    |--------------------------------------------------------------------------
    */
    'constants' => [
        'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost'),
    ],
];

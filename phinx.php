<?php

declare(strict_types=1);

$connection = [
    'adapter' => 'mysql',
    'host' => getenv('DB_HOST') ?: 'db',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_0900_as_cs',
];

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => array_merge($connection, [
            'name' => getenv('DB_DATABASE') ?: 'backend',
            'user' => getenv('DB_USERNAME') ?: 'backend',
            'pass' => getenv('DB_PASSWORD') ?: 'backend_dev',
        ]),
        'testing' => array_merge($connection, [
            'name' => getenv('DB_TEST_DATABASE') ?: 'backend_test',
            'user' => getenv('DB_TEST_USERNAME') ?: 'backend_test',
            'pass' => getenv('DB_TEST_PASSWORD') ?: 'backend_test_dev',
        ]),
    ],
    'templates' => [
        'file' => __DIR__ . '/db/migrations/templates/template_migration.php.template',
    ],
    'version_order' => 'creation',
];

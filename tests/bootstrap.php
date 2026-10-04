<?php

declare(strict_types=1);

putenv('APP_ENV=testing');
putenv('CACHE_STORE=array');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$_ENV['CACHE_STORE'] = $_SERVER['CACHE_STORE'] = 'array';
$_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = ':memory:';

if (getenv('DB_CONNECTION') !== 'sqlite' || getenv('DB_DATABASE') !== ':memory:') {
    fwrite(STDERR, "Unsafe test database configuration. Tests require SQLite :memory:.\n");
    exit(1);
}

require dirname(__DIR__).'/vendor/autoload.php';

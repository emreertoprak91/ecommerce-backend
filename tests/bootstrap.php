<?php

/**
 * PHPUnit Bootstrap File
 *
 * This file is loaded before PHPUnit runs any tests.
 * It ensures that test environment variables are properly set.
 */

// Force testing environment variables BEFORE Laravel loads .env
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_KEY'] = 'base64:t4rcbmMae6M4h7XzrELQuApI5M6yL2QMQsGtA/C0Rjk=';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_ENV['CACHE_STORE'] = 'array';
$_ENV['QUEUE_CONNECTION'] = 'sync';
$_ENV['SESSION_DRIVER'] = 'array';

$_SERVER['APP_ENV'] = 'testing';
$_SERVER['APP_KEY'] = 'base64:t4rcbmMae6M4h7XzrELQuApI5M6yL2QMQsGtA/C0Rjk=';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = ':memory:';
$_SERVER['CACHE_STORE'] = 'array';
$_SERVER['QUEUE_CONNECTION'] = 'sync';
$_SERVER['SESSION_DRIVER'] = 'array';

// Also set using putenv for older Laravel compatibility
putenv('APP_ENV=testing');
putenv('APP_KEY=base64:t4rcbmMae6M4h7XzrELQuApI5M6yL2QMQsGtA/C0Rjk=');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('CACHE_STORE=array');
putenv('QUEUE_CONNECTION=sync');
putenv('SESSION_DRIVER=array');

require __DIR__.'/../vendor/autoload.php';

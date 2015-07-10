<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists('Composer\Autoload\ClassLoader', false)) {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
}

// Define the fresque ini path
if (!defined('FRESQUE_INI_PATH')) {
    define('FRESQUE_INI_PATH', realpath('.' . DIRECTORY_SEPARATOR . 'fresque.ini'));
}

putenv('ENV=tests');

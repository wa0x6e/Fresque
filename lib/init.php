<?php
/**
 * Fresque init File
 *
 * Load the autoloader and instantiate Fresque
 *
 * PHP 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Wan Qi Chen <kami@kamisama.me>
 * @copyright     Copyright 2012, Wan Qi Chen <kami@kamisama.me>
 * @link          https://github.com/kamisama/Fresque
 * @package       Fresque
 * @subpackage    Fresque.lib
 * @since         0.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

if (file_exists(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'autoload.php';
} else {
    require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}
require __DIR__ . DIRECTORY_SEPARATOR . 'Fresque.php';

$fresque = new Fresque\Fresque();


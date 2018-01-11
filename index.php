<?php
if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__));
}

define('DS', DIRECTORY_SEPARATOR);

require ROOT . DS . 'vendor' . DS . 'autoload.php';

(new Ypi\Rest)->run(require ROOT . DS . 'conf.php');
// Ypi\App::run(ROOT . DS . 'conf.php');

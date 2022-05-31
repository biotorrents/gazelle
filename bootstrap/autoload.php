<?php
declare(strict_types=1);


/**
 * spl_autoload_register
 *
 * Load classes automatically when they're needed.
 * Temporary fix for class/filename mismatches.
 *
 * @param string $class The class name
 * @see https://www.php.net/manual/en/language.oop5.autoload.php
 */

spl_autoload_register(function (string $class) {
    $app = App::go();

    $path = "{$app->env->SERVER_ROOT}/app/{$class}.php";

    if (!file_exists($path)) {
        switch ($class) {
            case 'TORRENT':
            case 'BENCODE_DICT':
            case 'BENCODE_LIST':
                $name = 'torrent.class';
                break;

            default:
                $name = null;
                break;
        }

        $path = "{$app->env->SERVER_ROOT}/app/{$name}.php";
    }

    if (!empty($name)) {
        require_once $path;
    }
});

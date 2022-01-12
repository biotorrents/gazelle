<?php
declare(strict_types=1);

/**
 * Autoload
 *
 * Load classes automatically when they're needed.
 * The Gazelle convention is classes/lowercase_name.class.php.
 * This will certainly change in the near future!
 *
 * @param string $class The class name
 * @see https://www.php.net/manual/en/language.oop5.autoload.php
 */
spl_autoload_register(function ($class) {
    $ENV = ENV::go();

    $path = "$ENV->SERVER_ROOT/app/$class.php";

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

        $path = "$ENV->SERVER_ROOT/app/$name.php";
    }

    if ($name) {
        require_once $path;
    }
});

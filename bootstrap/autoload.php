<?php

declare(strict_types=1);


/**
 * spl_autoload_register
 *
 * Load classes automatically when they're needed.
 * Temporary fix for class/filename mismatches.
 *
 * @param string $class the class name
 * @see https://www.php.net/manual/en/language.oop5.autoload.php
 */

spl_autoload_register(function (string $class) {
    $app = \Gazelle\App::go();

    $path = "{$app->env->serverRoot}/app/{$class}.php";
    if (!file_exists($path)) {
        switch ($class) {
            case "TORRENT":
            case "BENCODE_DICT":
            case "BENCODE_LIST":
                $name = "torrent.class";
                break;

            case "Int64":
            case "Bencode":
                $name = "bencode.class";
                break;

            case "BencodeDecode":
                $name = "bencodedecode.class";
                break;

            case "BencodeTorrent":
                $name = "bencodetorrent.class";
                break;

            case "RecursiveArrayObject":
                $name = "ENV";
                break;

            default:
                $name = null;
                break;
        }

        $path = "{$app->env->serverRoot}/app/{$name}.php";
    }

    $name ??= null;
    if ($name) {
        require_once $path;
    }
});

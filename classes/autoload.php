<?php
declare(strict_types=1);

/**
 * Autoload
 *
 * Load classes automatically when they're needed.
 * The Gazelle convention is classes/lowercase_name.class.php.
 *
 * @param string $ClassName class name
 * @see https://www.php.net/manual/en/language.oop5.autoload.php
 */
spl_autoload_register(function ($ClassName) {
    $FilePath = SERVER_ROOT . '/classes/' . strtolower($ClassName) . '.class.php';
    #$FilePath = $_SERVER['DOCUMENT_ROOT'] . '/classes/' . strtolower($ClassName) . '.class.php';

    if (!file_exists($FilePath)) {
        // todo: Rename the following classes to conform with the code guidelines
        switch ($ClassName) {
        case 'MASS_USER_BOOKMARKS_EDITOR':
          $FileName = 'mass_user_bookmarks_editor.class';
          break;

        case 'MASS_USER_TORRENTS_EDITOR':
          $FileName = 'mass_user_torrents_editor.class';
          break;

        case 'MASS_USER_TORRENTS_TABLE_VIEW':
          $FileName = 'mass_user_torrents_table_view.class';
          break;

        case 'TEXTAREA_PREVIEW':
          $FileName = 'textarea_preview.class';
          break;

        case 'TORRENT':
        case 'BENCODE_DICT':
        case 'BENCODE_LIST':
          $FileName = 'torrent.class';
          break;

        case 'RecursiveArrayObject':
          $FileName = 'env.class';
          break;

        case 'Parsedown':
          $FileName = 'vendor/Parsedown';
          break;

        case 'ParsedownExtra':
          $FileName = 'vendor/ParsedownExtra';
          break;

        case 'TwitterAPIExchange':
          $FileName = 'vendor/TwitterAPIExchange';
          break;

        default:
          error("Couldn't import class $ClassName");
    }

        $FilePath = SERVER_ROOT . "/classes/$FileName.php";
        #$FilePath = $_SERVER['DOCUMENT_ROOT'] . "/classes/$FileName.php";
    }

    require_once $FilePath;
});

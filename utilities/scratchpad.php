<?php

declare(strict_types=1);


/**
 * scratchpad
 */

$app = Gazelle\App::go();

echo "<pre>";
$foo = new Gazelle\TorrentGroups(39);
$foo->loadCreators();
#$baz = $foo->relationships->torrents;
~d($foo->relationships->creators);
exit;


!d(
    $literature
    /*
    $literature->getCollages(),
    $literature->getCreators(),
    $literature->getTorrentGroups(),
    $literature->getRequests(),
    $literature->getTorrents(),
    $literature->getWiki(),
    */
);

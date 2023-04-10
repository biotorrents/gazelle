<?php

#declare(strict_types=1);

authorize();
if ($UserID !== $app->user->core['id']
 || !Bookmarks::validateType('torrent')) {
    error(403);
}

if ($_POST['type'] === 'torrents') {
    $BU = new MASS_USER_BOOKMARKS_EDITOR();

    if ($_POST['delete']) {
        $BU->mass_remove();
    } elseif ($_POST['update']) {
        $BU->mass_update();
    }
}

Http::redirect("bookmarks.php?type=torrents");

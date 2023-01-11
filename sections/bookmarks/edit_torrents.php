<?php

declare(strict_types=1);

$app = App::go();

/*
$Security = new Security();
$UserID = $Security->checkUser('users_override_paranoia');

$app->dbOld->query("
SELECT
  `Username`
FROM
  `users_main`
WHERE
  `ID` = '$UserID'
");
list($Username) = $app->dbOld->next_record();

View::header(
    'Organize Bookmarks',
    'browse,vendor/jquery.tablesorter.min,sort'
);

$EditType = isset($_GET['type']) ? $_GET['type'] : 'torrents';
list(, $CollageDataList, $TorrentList) = User::get_bookmarks($UserID); // todo: $TorrentList might not have the correct order, use the $GroupIDs instead

$TT = new MASS_USER_TORRENTS_TABLE_VIEW($TorrentList, $CollageDataList, $EditType, 'Organize Torrent Bookmarks');
$TT->render_all();
View::footer();
*/

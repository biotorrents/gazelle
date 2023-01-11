<?php

declare(strict_types=1);

$app = App::go();

// peerupdate.php is apparently shit so this is a crappy bandaid to fix the problem of
// all the cached "0 seeds" on the first search page from peerupdate missing the changes.
// It used to be in a sandbox that I just ran whenever I saw something wrong with
// the first search page, but it only takes like 7ms to run so it's scheduled now.

/*
$FrontPageQ = new SphinxqlQuery();
$FrontPageQ->select('groupid, id, seeders')
  ->order_by('time', 'desc');
$FrontPageQ->from('torrents, delta');
$FrontPageQ->limit(0, 60, 60);

$Results = $FrontPageQ->query()->to_array('id');
$IDs = [];
$Seeds = [];

foreach ($Results as $i) {
    $GroupCache = $app->cacheOld->get_value('torrent_group_'.$i['groupid']);
    if (!$GroupCache) {
        continue;
    }

    $IDs = array_merge($IDs, array_column($GroupCache['d']['Torrents'], 'ID'));
    $Seeds = array_merge($Seeds, array_column($GroupCache['d']['Torrents'], 'Seeders'));
}

$QueryParts = [];
for ($i = 0; $i < sizeof($IDs); $i++) {
    $QueryParts[] = '(ID='.$IDs[$i].' AND Seeders!='.$Seeds[$i].')';
}

$query = 'SELECT GroupID FROM torrents WHERE '.implode(' OR ', $QueryParts);
$app->dbOld->query($query);
if ($app->dbOld->has_results()) {
    foreach ($app->dbOld->collect('GroupID') as $GID) {
        $app->cacheOld->delete_value('torrent_group_'.$GID);
    }
}
*/

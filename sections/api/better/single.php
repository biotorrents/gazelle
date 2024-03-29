<?php

$app = \Gazelle\App::go();

if (($Results = $app->cache->get('better_single_groupids')) === false) {
    $app->dbOld->query("
    SELECT
      t.ID AS TorrentID,
      t.GroupID AS GroupID
    FROM xbt_files_users AS x
      JOIN torrents AS t ON t.ID=x.fid
    WHERE t.Format='FLAC'
    GROUP BY x.fid
    HAVING COUNT(x.uid) = 1
    ORDER BY t.LogScore DESC, t.Time ASC
    LIMIT 30");

    $Results = $app->dbOld->to_pair('GroupID', 'TorrentID', false);
    $app->cache->set('better_single_groupids', $Results, 30 * 60);
}

$Groups = Torrents::get_groups(array_keys($Results));

$JsonResults = [];
foreach ($Results as $GroupID => $FlacID) {
    if (!isset($Groups[$GroupID])) {
        continue;
    }
    $Group = $Groups[$GroupID];
    extract(Torrents::array_group($Group));

    $JsonArtists = [];
    if (count($Artists) > 0) {
        foreach ($Artists as $Artist) {
            $JsonArtists[] = array(
        'id' => (int)$Artist['id'],
        'name' => $Artist['name'],
        'aliasId' => (int)$Artist['aliasid']
      );
        }
    }

    $JsonResults[] = array(
    'torrentId' => (int)$FlacID,
    'groupId' => (int)$GroupID,
    'artist' => $JsonArtists,
    'groupName' => $GroupName,
    'groupYear' => (int)$GroupYear,
    'downloadUrl' => "torrents.php?action=download&id=$FlacID&authkey=".$app->user->extra['AuthKey'].'&torrent_pass='.$app->user->extra['torrent_pass']
  );
}

echo json_encode(
    array(
    'status' => 'success',
    'response' => $JsonResults
  )
);

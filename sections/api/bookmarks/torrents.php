<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

if (!empty($_GET['userid'])) {
    if (!check_perms('users_override_paranoia')) {
        error(403);
    }

    $UserID = $_GET['userid'];
    if (!is_numeric($UserID)) {
        error(404);
    }

    $app->dbOld->query("
    SELECT
      `Username`
    FROM
      `users_main`
    WHERE
      `ID` = '$UserID'
    ");
    list($Username) = $app->dbOld->next_record();
} else {
    $UserID = $app->user->core['id'];
}

$Sneaky = ($UserID !== $app->user->core['id']);
$JsonBookmarks = [];

list($GroupIDs, $CollageDataList, $GroupList) = User::get_bookmarks($UserID);
foreach ($GroupIDs as $GroupID) {
    if (!isset($GroupList[$GroupID])) {
        continue;
    }

    $Group = $GroupList[$GroupID];
    $JsonTorrents = [];

    foreach ($Group['Torrents'] as $Torrent) {
        $JsonTorrents[] = array(
          'id'          => (int) $Torrent['ID'],
          'groupId'     => (int) $Torrent['GroupID'],
          'platform'    => $Torrent['Media'],
          'fileCount'   => (int) $Torrent['FileCount'],
          'freeTorrent' => $Torrent['FreeTorrent'] === 1,
          'size'        => (float) $Torrent['Size'],
          'leechers'    => (int) $Torrent['Leechers'],
          'seeders'     => (int) $Torrent['Seeders'],
          'snatched'    => (int) $Torrent['Snatched'],
          'time'        => $Torrent['Time'],
          'hasFile'     => (int) $Torrent['HasFile']
        );

        /*
        $JsonTorrents[] = array(
          'id'                      => (int) $Torrent['ID'],
          'groupId'                 => (int) $Torrent['GroupID'],
          'media'                   => $Torrent['Media'],
          'format'                  => $Torrent['Format'],
          'encoding'                => $Torrent['Encoding'],
          'remasterYear'            => (int) $Torrent['RemasterYear'],
          'remastered'              => $Torrent['Remastered'] == 1,
          'remasterTitle'           => $Torrent['RemasterTitle'],
          'remasterRecordLabel'     => $Torrent['RemasterRecordLabel'],
          'remasterCatalogueNumber' => $Torrent['RemasterCatalogueNumber'],
          'scene'                   => $Torrent['Scene'] === 1,
          'hasLog'                  => $Torrent['HasLog'] === 1,
          'hasCue'                  => $Torrent['HasCue'] === 1,
          'logScore'                => (float) $Torrent['LogScore'],
          'fileCount'               => (int) $Torrent['FileCount'],
          'freeTorrent'             => $Torrent['FreeTorrent'] === 1,
          'size'                    => (float) $Torrent['Size'],
          'leechers'                => (int) $Torrent['Leechers'],
          'seeders'                 => (int) $Torrent['Seeders'],
          'snatched'                => (int) $Torrent['Snatched'],
          'time'                    => $Torrent['Time'],
          'hasFile'                 => (int) $Torrent['HasFile']
        );
        */
    }

    $JsonBookmarks[] = array(
    'id'          => (int) $Group['ID'],
    'name'        => $Group['Name'],
    'year'        => (int) $Group['Year'],
    'accession'   => $Group['CatalogueNumber'],
    'tagList'     => $Group['TagList'],
    'vanityHouse' => $Group['VanityHouse'] === 1,
    'picture'     => $Group['WikiImage'],
    'torrents'    => $JsonTorrents
  );

    /*
    $JsonBookmarks[] = array(
      'id'              => (int) $Group['ID'],
      'name'            => $Group['Name'],
      'year'            => (int) $Group['Year'],
      'recordLabel'     => $Group['RecordLabel'],
      'catalogueNumber' => $Group['CatalogueNumber'],
      'tagList'         => $Group['TagList'],
      'releaseType'     => $Group['ReleaseType'],
      'vanityHouse'     => $Group['VanityHouse'] === 1,
      'image'           => $Group['WikiImage'],
      'torrents'        => $JsonTorrents
    );
    */
}

print
  json_encode(
      array(
      'status' => 'success',
      'response' => array(
        'bookmarks' => $JsonBookmarks
      )
    )
  );

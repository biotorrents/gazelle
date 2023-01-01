<?php

#declare(strict_types=1);

$app = App::go();

$get = Http::request("get");
$post = Http::request("post");
$server = Http::request("server");


if (!empty($_GET['order_way']) && $_GET['order_way'] === 'asc') {
    $OrderWay = 'asc';
} else {
    $OrderWay = 'desc';
}

if (empty($_GET['order_by']) || !isset(TorrentSearch::$sortOrders[$_GET['order_by']])) {
    $OrderBy = 'time';
} else {
    $OrderBy = $_GET['order_by'];
}

$GroupResults = !isset($_GET['group_results']) || (int) $_GET['group_results'] !== 0;
$Page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;

$Search = new TorrentSearch($GroupResults, $OrderBy, $OrderWay, $Page, TORRENTS_PER_PAGE);
$Results = $Search->query($_GET);

$Groups = $Search->get_groups();
$NumResults = $Search->record_count();

if ($Results === false) {
    json_die('error', 'Search returned an error. Make sure all parameters are valid and of the expected types.');
}

if ($NumResults === 0) {
    json_die('success', [
    'results' => []
  ]);
}

$Bookmarks = Bookmarks::all_bookmarks('torrent');

$JsonGroups = [];
foreach ($Results as $Key => $GroupID) {
    $GroupInfo = $Groups[$GroupID];
    if (empty($GroupInfo['Torrents'])) {
        continue;
    }

    $CategoryID = $GroupInfo['CategoryID'];
    $Artists = $GroupInfo['Artists'];
    $GroupCatalogueNumber = $GroupInfo['CatalogueNumber'];
    $GroupName = $GroupInfo['Name'];

    if ($GroupResults) {
        $Torrents = $GroupInfo['Torrents'];
        $GroupTime = $MaxSize = $TotalLeechers = $TotalSeeders = $TotalSnatched = 0;

        foreach ($Torrents as $T) {
            $GroupTime = max($GroupTime, strtotime($T['Time']));
            $MaxSize = max($MaxSize, $T['Size']);
            $TotalLeechers += $T['Leechers'];
            $TotalSeeders += $T['Seeders'];
            $TotalSnatched += $T['Snatched'];
        }
    } else {
        $TorrentID = $Key;
        $Torrents = [$TorrentID => $GroupInfo['Torrents'][$TorrentID]];
    }

    $TagList = explode(' ', str_replace('_', '.', $GroupInfo['TagList']));
    $JsonArtists = [];
    $DisplayName = '';

    if (!empty($Artists)) {
        $DisplayName = Artists::display_artists($Artists, false, false, false);
        foreach ($Artists as $Artist) {
            $JsonArtists[] = [
              'id' => (int) $Artist['id'],
              'name' => $Artist['name']
            ];
        }
    }

    $JsonTorrents = [];
    foreach ($Torrents as $TorrentID => $Data) {
        // All of the individual torrents in the group

        $JsonTorrents[] = [
          'torrentId'           => (int) $TorrentID,
          'authors'             => $JsonArtists,
          'platform'            => $Data['Media'],
          'format'              => $Data['Container'],
          'license'             => $Data['Codec'],
          'scope'               => $Data['Resolution'],
          'annotated'           => $Data['Censored'],
          'archive'             => $Data['Archive'],
          'fileCount'           => (int) $Data['FileCount'],
          'time'                => $Data['Time'],
          'size'                => (int) $Data['Size'],
          'snatches'            => (int) $Data['Snatched'],
          'seeders'             => (int) $Data['Seeders'],
          'leechers'            => (int) $Data['Leechers'],
          'isFreeleech'         => (int) $Data['FreeTorrent'] === 1,
          'isNeutralLeech'      => (int) $Data['FreeTorrent'] === 2,
          'isPersonalFreeleech' => $Data['PersonalFL'],
          'canUseToken'         => Torrents::can_use_token($Data),
          'hasSnatched'         => $Data['IsSnatched']
        ];
    }

    $JsonGroups[] = [
      'groupId'       => (int) $GroupID,
      'groupName'     => $GroupName,
      'author'        => $DisplayName,
      'picture'       => $GroupInfo['WikiImage'],
      'tags'          => $TagList,
      'bookmarked'    => (in_array($GroupID, $Bookmarks)),
      'groupYear'     => (int) $GroupInfo['Year'],
      'groupTime'     => (int) $GroupTime,
      'accession'     => $GroupInfo['CatalogueNumber'],
      'lab'           => $GroupInfo['Studio'],
      'location'      => $GroupInfo['Series'],
      'maxSize'       => (int) $MaxSize,
      'totalSnatched' => (int) $TotalSnatched,
      'totalSeeders'  => (int) $TotalSeeders,
      'totalLeechers' => (int) $TotalLeechers,
      'torrents'      => $JsonTorrents
    ];
}

json_print('success', [
  'currentPage' => intval($Page),
  'pages' => ceil($NumResults / TORRENTS_PER_PAGE),
  'results' => $JsonGroups
]);

<?
include(SERVER_ROOT.'/sections/torrents/functions.php');

if (!empty($_GET['order_way']) && $_GET['order_way'] == 'asc') {
  $OrderWay = 'asc';
} else {
  $OrderWay = 'desc';
}

if (empty($_GET['order_by']) || !isset(TorrentSearch::$SortOrders[$_GET['order_by']])) {
  $OrderBy = 'time';
} else {
  $OrderBy = $_GET['order_by'];
}

$GroupResults = !isset($_GET['group_results']) || $_GET['group_results'] != '0';
$Page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
$Search = new TorrentSearch($GroupResults, $OrderBy, $OrderWay, $Page, TORRENTS_PER_PAGE);
$Results = $Search->query($_GET);
$Groups = $Search->get_groups();
$NumResults = $Search->record_count();

if ($Results === false) {
  json_die('error', 'Search returned an error. Make sure all parameters are valid and of the expected types.');
}

if ($NumResults == 0) {
  json_die("success", array(
    'results' => array()
  ));
}

$Bookmarks = Bookmarks::all_bookmarks('torrent');

$JsonGroups = array();
foreach ($Results as $Key => $GroupID) {
  $GroupInfo = $Groups[$GroupID];
  if (empty($GroupInfo['Torrents'])) {
    continue;
  }
  $CategoryID = $GroupInfo['CategoryID'];
//  $ExtendedArtists = $GroupInfo['ExtendedArtists'];
  $ExtendedArtists = $GroupInfo['Artists'];
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
    $Torrents = array($TorrentID => $GroupInfo['Torrents'][$TorrentID]);
  }

  $TagList = explode(' ', str_replace('_', '.', $GroupInfo['TagList']));
  $JsonArtists = array();
  if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
    unset($ExtendedArtists[2]);
    unset($ExtendedArtists[3]);
    $DisplayName = Artists::display_artists($ExtendedArtists, false, false, false);
    foreach ($ExtendedArtists[1] as $Artist) {
      $JsonArtists[] = array(
        'id' => (int)$Artist['id'],
        'name' => $Artist['name'],
        'aliasid' => (int)$Artist['id']
      );
    }
  } else {
    $DisplayName = '';
  }

  $JsonTorrents = array();
  foreach ($Torrents as $TorrentID => $Data) {
    // All of the individual torrents in the group

    $JsonTorrents[] = array(
      'torrentId' =>       (int)$TorrentID,
      'artists' =>              $JsonArtists,
      'media' =>                $Data['Media'],
      'container' =>            $Data['Container'],
      'codec' =>                $Data['Codec'],
      'resolution' =>           $Data['Resolution'],
      'audio' =>                $Data['AudioFormat'],
      'lang' =>                 $Data['Language'],
      'subbing' =>              $Data['Subbing'],
      'subber' =>               $Data['Subber'],
      'censored' =>             $Data['Censored'],
      'archive' =>              $Data['Archive'],
      'fileCount' =>       (int)$Data['FileCount'],
      'time' =>                 $Data['Time'],
      'size' =>            (int)$Data['Size'],
      'snatches' =>        (int)$Data['Snatched'],
      'seeders' =>         (int)$Data['Seeders'],
      'leechers' =>        (int)$Data['Leechers'],
      'isFreeleech' =>          $Data['FreeTorrent'] == '1',
      'isNeutralLeech' =>       $Data['FreeTorrent'] == '2',
      'isPersonalFreeleech' =>  $Data['PersonalFL'],
      'canUseToken' =>          Torrents::can_use_token($Data),
      'hasSnatched' =>          $Data['IsSnatched']
    );
  }

  $JsonGroups[] = array(
    'groupId' =>       (int)$GroupID,
    'groupName' =>          $GroupName,
    'artist' =>             $DisplayName,
    'cover' =>              $GroupInfo['WikiImage'],
    'tags' =>               $TagList,
    'bookmarked' =>    (in_array($GroupID, $Bookmarks)),
    'groupYear' =>     (int)$GroupInfo['Year'],
    'groupTime' =>     (int)$GroupTime,
    'catalogue' =>          $GroupInfo['CatalogueNumber'],
    'studio' =>             $GroupInfo['Studio'],
    'series' =>             $GroupInfo['Series'],
    'dlsite' =>             $GroupInfo['DLSiteID'],
    'maxSize' =>       (int)$MaxSize,
    'totalSnatched' => (int)$TotalSnatched,
    'totalSeeders' =>  (int)$TotalSeeders,
    'totalLeechers' => (int)$TotalLeechers,
    'torrents' =>           $JsonTorrents
  );
}

json_print('success', array(
  'currentPage' => intval($Page),
  'pages' => ceil($NumResults / TORRENTS_PER_PAGE),
  'results' => $JsonGroups));

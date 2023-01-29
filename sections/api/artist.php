<?php

#declare(strict_types=1);

$app = App::go();

# todo: Go through line by line

if (!empty($_GET['artistreleases'])) {
    $OnlyArtistReleases = true;
}

if ($_GET['id'] && $_GET['artistname']) {
    json_die('failure', 'bad parameters');
}

$ArtistID = $_GET['id'];
if ($ArtistID && !is_number($ArtistID)) {
    json_die('failure');
}

if (empty($ArtistID)) {
    if (!empty($_GET['artistname'])) {
        $Name = db_string(trim($_GET['artistname']));
        $app->dbOld->query("
      SELECT ArtistID
      FROM artists_alias
      WHERE Name LIKE '$Name'");
        if (!(list($ArtistID) = $app->dbOld->next_record(MYSQLI_NUM, false))) {
            json_die('failure');
        }
        // If we get here, we got the ID!
    }
}

if (!empty($_GET['revisionid'])) { // if they're viewing an old revision
    $RevisionID = $_GET['revisionid'];
    if (!is_number($RevisionID)) {
        error(0);
    }
    $Data = $app->cacheOld->get_value("artist_$ArtistID"."_revision_$RevisionID");
} else { // viewing the live version
    $Data = $app->cacheOld->get_value("artist_$ArtistID");
    $RevisionID = false;
}
if ($Data) {
    list($Name, $Image, $Body) = current($Data);
} else {
    if ($RevisionID) {
        /*
          $sql = "
            SELECT
              a.Name,
              wiki.Image,
              wiki.body,
              a.VanityHouse
            FROM wiki_artists AS wiki
              LEFT JOIN artists_group AS a ON wiki.RevisionID = a.RevisionID
            WHERE wiki.RevisionID = '$RevisionID' ";
        */
        $sql = "
      SELECT
        a.Name,
        wiki.Image,
        wiki.body
      FROM wiki_artists AS wiki
        LEFT JOIN artists_group AS a ON wiki.RevisionID = a.RevisionID
      WHERE wiki.RevisionID = '$RevisionID' ";
    } else {
        /*
          $sql = "
            SELECT
              a.Name,
              wiki.Image,
              wiki.body,
              a.VanityHouse
            FROM artists_group AS a
              LEFT JOIN wiki_artists AS wiki ON wiki.RevisionID = a.RevisionID
            WHERE a.ArtistID = '$ArtistID' ";
        */
        $sql = "
      SELECT
        a.Name,
        wiki.Image,
        wiki.body
      FROM artists_group AS a
        LEFT JOIN wiki_artists AS wiki ON wiki.RevisionID = a.RevisionID
      WHERE a.ArtistID = '$ArtistID' ";
    }
    $sql .= " GROUP BY a.ArtistID";
    $app->dbOld->query($sql);

    if (!$app->dbOld->has_results()) {
        json_die('failure');
    }

    //  list($Name, $Image, $Body, $VanityHouseArtist) = $app->dbOld->next_record(MYSQLI_NUM, array(0));
    list($Name, $Image, $Body) = $app->dbOld->next_record(MYSQLI_NUM, array(0));
}

// Requests
$Requests = [];
if (empty($app->userNew->extra['DisableRequests'])) {
    $Requests = $app->cacheOld->get_value("artists_requests_$ArtistID");
    if (!is_array($Requests)) {
        $app->dbOld->query("
      SELECT
        r.ID,
        r.CategoryID,
        r.Title,
        r.Year,
        r.TimeAdded,
        COUNT(rv.UserID) AS Votes,
        SUM(rv.Bounty) AS Bounty
      FROM requests AS r
        LEFT JOIN requests_votes AS rv ON rv.RequestID = r.ID
        LEFT JOIN requests_artists AS ra ON r.ID = ra.RequestID
      WHERE ra.ArtistID = $ArtistID
        AND r.TorrentID = 0
      GROUP BY r.ID
      ORDER BY Votes DESC");

        if ($app->dbOld->has_results()) {
            $Requests = $app->dbOld->to_array('ID', MYSQLI_ASSOC, false);
        } else {
            $Requests = [];
        }
        $app->cacheOld->cache_value("artists_requests_$ArtistID", $Requests);
    }
}
$NumRequests = count($Requests);

if (($Importances = $app->cacheOld->get_value("artist_groups_$ArtistID")) === false) {
    $app->dbOld->query("
    SELECT DISTINCTROW
      ta.`GroupID`,
      ta.`Importance`,
      tg.`year`
    FROM
      `torrents_artists` AS ta
    JOIN `torrents_group` AS tg
    ON
      tg.`id` = ta.`GroupID`
    WHERE
      ta.`ArtistID` = '$ArtistID'
    ORDER BY
      tg.`year`,
      tg.`Name`
    DESC
    ");

    $GroupIDs = $app->dbOld->collect('GroupID');
    $Importances = $app->dbOld->to_array(false, MYSQLI_BOTH, false);
    $app->cacheOld->cache_value("artist_groups_$ArtistID", $Importances, 0);
} else {
    $GroupIDs = [];
    foreach ($Importances as $Group) {
        $GroupIDs[] = $Group['GroupID'];
    }
}
if (count($GroupIDs) > 0) {
    $TorrentList = Torrents::get_groups($GroupIDs, true, true);
} else {
    $TorrentList = [];
}
$NumGroups = count($TorrentList);

//Get list of used release types
$UsedReleases = [];
foreach ($TorrentList as $GroupID=>$Group) {
    if ($Importances[$GroupID]['Importance'] == '2') {
        $TorrentList[$GroupID]['ReleaseType'] = 1024;
        $GuestAlbums = true;
    }
    if ($Importances[$GroupID]['Importance'] == '3') {
        $TorrentList[$GroupID]['ReleaseType'] = 1023;
        $RemixerAlbums = true;
    }
    if ($Importances[$GroupID]['Importance'] == '4') {
        $TorrentList[$GroupID]['ReleaseType'] = 1022;
        $ComposerAlbums = true;
    }
    if ($Importances[$GroupID]['Importance'] == '7') {
        $TorrentList[$GroupID]['ReleaseType'] = 1021;
        $ProducerAlbums = true;
    }
    if (!in_array($TorrentList[$GroupID]['ReleaseType'], $UsedReleases)) {
        $UsedReleases[] = $TorrentList[$GroupID]['ReleaseType'];
    }
}

if (!empty($GuestAlbums)) {
    $ReleaseTypes[1024] = 'Guest Appearance';
}
if (!empty($RemixerAlbums)) {
    $ReleaseTypes[1023] = 'Remixed By';
}
if (!empty($ComposerAlbums)) {
    $ReleaseTypes[1022] = 'Composition';
}
if (!empty($ProducerAlbums)) {
    $ReleaseTypes[1021] = 'Produced By';
}

reset($TorrentList);

$JsonTorrents = [];
$Tags = [];
$NumTorrents = $NumSeeders = $NumLeechers = $NumSnatches = 0;
foreach ($GroupIDs as $GroupID) {
    if (!isset($TorrentList[$GroupID])) {
        continue;
    }
    $Group = $TorrentList[$GroupID];
    extract(Torrents::array_group($Group));

    foreach ($Artists as &$Artist) {
        $Artist['id'] = (int)$Artist['id'];
        $Artist['aliasid'] = (int)$Artist['aliasid'];
    }

    foreach ($ExtendedArtists as &$ArtistGroup) {
        foreach ($ArtistGroup as &$Artist) {
            $Artist['id'] = (int)$Artist['id'];
            $Artist['aliasid'] = (int)$Artist['aliasid'];
        }
    }

    $Found = Misc::search_array($Artists, 'id', $ArtistID);
    if (isset($OnlyArtistReleases) && empty($Found)) {
        continue;
    }

    $GroupVanityHouse = $Importances[$GroupID]['VanityHouse'];

    $TagList = explode(' ', str_replace('_', '.', $TagList));

    // $Tags array is for the sidebar on the right
    foreach ($TagList as $Tag) {
        if (!isset($Tags[$Tag])) {
            $Tags[$Tag] = array('name' => $Tag, 'count' => 1);
        } else {
            $Tags[$Tag]['count']++;
        }
    }
    $InnerTorrents = [];
    foreach ($Torrents as $Torrent) {
        $NumTorrents++;
        $NumSeeders += $Torrent['Seeders'];
        $NumLeechers += $Torrent['Leechers'];
        $NumSnatches += $Torrent['Snatched'];

        $InnerTorrents[] = array(
      'id' => (int)$Torrent['ID'],
      'groupId' => (int)$Torrent['GroupID'],
      'media' => $Torrent['Media'],
      'format' => $Torrent['Format'],
      'encoding' => $Torrent['Encoding'],
      'remasterYear' => (int)$Torrent['RemasterYear'],
      'remastered' => $Torrent['Remastered'] == 1,
      'remasterTitle' => $Torrent['RemasterTitle'],
      'remasterRecordLabel' => $Torrent['RemasterRecordLabel'],
      'scene' => $Torrent['Scene'] == 1,
      'hasLog' => $Torrent['HasLog'] == 1,
      'hasCue' => $Torrent['HasCue'] == 1,
      'logScore' => (int)$Torrent['LogScore'],
      'fileCount' => (int)$Torrent['FileCount'],
      'freeTorrent' => $Torrent['FreeTorrent'] == 1,
      'size' => (int)$Torrent['Size'],
      'leechers' => (int)$Torrent['Leechers'],
      'seeders' => (int)$Torrent['Seeders'],
      'snatched' => (int)$Torrent['Snatched'],
      'time' => $Torrent['Time'],
      'hasFile' => (int)$Torrent['HasFile']
    );
    }
    $JsonTorrents[] = array(
    'groupId' => (int)$GroupID,
    'groupName' => $GroupName,
    'groupYear' => (int)$GroupYear,
    'groupRecordLabel' => $GroupRecordLabel,
    'groupCatalogueNumber' => $GroupCatalogueNumber,
    'groupCategoryID' => $GroupCategoryID,
    'tags' => $TagList,
    'releaseType' => (int)$ReleaseType,
    'wikiImage' => $WikiImage,
    'groupVanityHouse' => $GroupVanityHouse == 1,
    'hasBookmarked' => Bookmarks::isBookmarked('torrent', $GroupID),
    'artists' => $Artists,
    'extendedArtists' => $ExtendedArtists,
    'torrent' => $InnerTorrents,

  );
}

$JsonRequests = [];
foreach ($Requests as $RequestID => $Request) {
    $JsonRequests[] = array(
    'requestId' => (int)$RequestID,
    'categoryId' => (int)$Request['CategoryID'],
    'title' => $Request['Title'],
    'year' => (int)$Request['Year'],
    'timeAdded' => $Request['TimeAdded'],
    'votes' => (int)$Request['Votes'],
    'bounty' => (int)$Request['Bounty']
  );
}

//notifications disabled by default
$notificationsEnabled = false;
if (check_perms('site_torrents_notify')) {
    if (($Notify = $app->cacheOld->get_value('notify_artists_'.$app->userNew->core['id'])) === false) {
        $app->dbOld->query("
      SELECT ID, Artists
      FROM users_notify_filters
      WHERE UserID = '{{$app->userNew->core['id']}}'
        AND Label = 'Artist notifications'
      LIMIT 1");
        $Notify = $app->dbOld->next_record(MYSQLI_ASSOC, false);
        $app->cacheOld->cache_value('notify_artists_'.$app->userNew->core['id'], $Notify, 0);
    }
    if (stripos($Notify['Artists'], "|$Name|") === false) {
        $notificationsEnabled = false;
    } else {
        $notificationsEnabled = true;
    }
}

// Cache page for later use

if ($RevisionID) {
    $Key = "artist_$ArtistID"."_revision_$RevisionID";
} else {
    $Key = "artist_$ArtistID";
}

$Data = array(array($Name, $Image, $Body));

$app->cacheOld->cache_value($Key, $Data, 3600);

json_die('success', array(
  'id' => (int)$ArtistID,
  'name' => $Name,
  'notificationsEnabled' => $notificationsEnabled,
  'hasBookmarked' => Bookmarks::isBookmarked('artist', $ArtistID),
  'image' => $Image,
  'body' => Text::parse($Body),
  'vanityHouse' => $VanityHouseArtist == 1,
  'tags' => array_values($Tags),
  'statistics' => array(
    'numGroups' => $NumGroups,
    'numTorrents' => $NumTorrents,
    'numSeeders' => $NumSeeders,
    'numLeechers' => $NumLeechers,
    'numSnatches' => $NumSnatches
  ),
  'torrentgroup' => $JsonTorrents,
  'requests' => $JsonRequests
));

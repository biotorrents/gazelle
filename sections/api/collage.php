<?php
#declare(strict_types=1);

define('ARTIST_COLLAGE', 'Artists');

if (empty($_GET['id']) || !is_number($_GET['id'])) {
    json_die('failure', 'bad parameters');
}

$CollageID = $_GET['id'];
$CacheKey = "collage_$CollageID";
$CollageData = $Cache->get_value($CacheKey);

if ($CollageData) {
    list($Name, $Description, $CommentList, $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser, $Updated, $Subscribers) = $CollageData;
} else {
    $DB->query("
    SELECT
      `Name`,
      `Description`,
      `UserID`,
      `Deleted`,
      `CategoryID`,
      `Locked`,
      `MaxGroups`,
      `MaxGroupsPerUser`,
      `Updated`,
      `Subscribers`
    FROM
      `collages`
    WHERE
      `ID` = '$CollageID'
    ");

    if (!$DB->has_results()) {
        json_die("failure");
    }

    list($Name, $Description, $CreatorID, $Deleted, $CollageCategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser, $Updated, $Subscribers) = $DB->next_record(MYSQLI_NUM);
    $CommentList = null;
    $SetCache = true;
}

// todo: Cache this
$DB->query("
SELECT
  `GroupID`
FROM
  `collages_torrents`
WHERE
  `CollageID` = $CollageID
");
$TorrentGroups = $DB->collect('GroupID');

$JSON = array(
  'id'                  => (int) $CollageID,
  'name'                => $Name,
  'description'         => Text::full_format($Description),
  'creatorID'           => (int) $CreatorID,
  'deleted'             => (bool) $Deleted,
  'collageCategoryID'   => (int) $CollageCategoryID,
  'collageCategoryName' => $CollageCats[(int) $CollageCategoryID],
  'locked'              => (bool) $Locked,
  'maxGroups'           => (int) $MaxGroups,
  'maxGroupsPerUser'    => (int) $MaxGroupsPerUser,
  'hasBookmarked'       => Bookmarks::has_bookmarked('collage', $CollageID),
  'subscriberCount'     => (int) $Subscribers,
  'torrentGroupIDList'  => $TorrentGroups
);

if ($CollageCategoryID !== array_search(ARTIST_COLLAGE, $CollageCats)) {
    // Torrent collage
    $TorrentGroups = [];
    $DB->query("
    SELECT
      ct.`GroupID`
    FROM
      `collages_torrents` AS ct
    JOIN `torrents_group` AS tg
    ON
      tg.`ID` = ct.`GroupID`
    WHERE
      ct.`CollageID` = '$CollageID'
    ORDER BY
      ct.`Sort`
    ");

    $GroupIDs = $DB->collect('GroupID');
    $GroupList = Torrents::get_groups($GroupIDs);

    foreach ($GroupIDs as $GroupID) {
        if (!empty($GroupList[$GroupID])) {
            $GroupDetails = Torrents::array_group($GroupList[$GroupID]);
            $TorrentList = [];

            foreach ($GroupDetails['Torrents'] as $Torrent) {
                $TorrentList[] = array(
                  'torrentid'   => (int)$Torrent['ID'],
                  'platform'    => $Torrent['Media'],
                  'fileCount'   => (int)$Torrent['FileCount'],
                  'size'        => (int)$Torrent['Size'],
                  'seeders'     => (int)$Torrent['Seeders'],
                  'leechers'    => (int)$Torrent['Leechers'],
                  'snatched'    => (int)$Torrent['Snatched'],
                  'freeTorrent' => ($Torrent['FreeTorrent'] === 1),
                  'reported'    => (count(Torrents::get_reports((int)$Torrent['ID'])) > 0),
                  'time'        => $Torrent['Time']
                );
            }

            $TorrentGroups[] = array(
              'id'          => $GroupDetails['GroupID'],
              'name'        => $GroupDetails['GroupName'],
              'year'        => $GroupDetails['GroupYear'],
              'categoryId'  => $GroupDetails['GroupCategoryID'],
              'accession'   => $GroupDetails['GroupCatalogueNumber'],
              'vanityHouse' => $GroupDetails['GroupVanityHouse'],
              'tagList'     => $GroupDetails['TagList'],
              'picture'     => $GroupDetails['WikiImage'],
              'torrents'    => $TorrentList
            );
        }
    }
    $JSON['torrentgroups'] = $TorrentGroups;
} else {
    // Artist collage
    $DB->query("
    SELECT
      ca.`ArtistID`,
      ag.`Name`,
      aw.`Image`
    FROM
      `collages_artists` AS ca
    JOIN `artists_group` AS ag
    ON
      ag.`ArtistID` = ca.`ArtistID`
    LEFT JOIN `wiki_artists` AS aw
    ON
      aw.`RevisionID` = ag.`RevisionID`
    WHERE
      ca.`CollageID` = '$CollageID'
    ORDER BY
      ca.`Sort`
    ");

    $Artists = [];
    while (list($ArtistID, $ArtistName, $ArtistImage) = $DB->next_record()) {
        $Artists[] = array(
          'id'      => (int) $ArtistID,
          'name'    => $ArtistName,
          'picture' => $ArtistImage
        );
    }
    $JSON['artists'] = $Artists;
}

if (isset($SetCache)) {
    $CollageData = array(
      $Name,
      $Description,
      $CommentList,
      (bool) $Deleted,
      (int) $CollageCategoryID,
      (int) $CreatorID,
      (bool) $Locked,
      (int) $MaxGroups,
      (int) $MaxGroupsPerUser,
      $Updated,
      (int) $Subscribers
    );
    $Cache->cache_value($CacheKey, $CollageData, 3600);
}

json_print('success', $JSON);

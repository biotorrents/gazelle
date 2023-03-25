<?php

declare(strict_types=1);

/**
 * Upload form
 *
 * This page relies on the TorrentForm class.
 * All it does is call the necessary functions.
 *
 * $Properties, $Err and $UploadForm are set in takeupload.php,
 * and are only used when the form doesn't validate
 * and this page must be called again.
 */

$app = App::go();

# announce and source
#$announceUris = ANNOUNCE_URLS[0];
$announceUris = call_user_func_array("array_merge", ANNOUNCE_URLS);

$passKey = $app->userNew->extra["torrent_pass"];
$sourceKey = User::uploadSource();

# tagList
$query = "select name from tags where tagType = ? order by name";
$tagList = $app->dbNew->column($query, "name", ["genre"]);



# twig template
$app->twig->display("torrents/upload.twig", [
    "title" => "Upload",
    "sidebar" => true,
    "js" => ["upload", "vendor/easymde.min"],
    "css" => ["vendor/easymde.min"],

    # upload form variables
    "newTorrent" => true, # todo: make programmatic
    "announceUris" => $announceUris,
    "passKey" => $passKey,
    "sourceKey" => $sourceKey,
    "tagList" => $tagList,
    "submitType" => "create",

    # todo: this needs to be torrentGroup
    "data" => [
      "categoryId" => null,
      "identifier" => null,
      "groupId" => null,
      "requestId" => null,
      "torrentId" => null,
      "version" => null,
      "title" => null,
      "subject" => null,
      "object" => null,
      "creatorList" => null,
      "workgroup" => null,
      "location" => null,
      "year" => null,
      "license" => null,
      "platform" => null,
      "format" => null,
      "archive" => null,
      "scope" => null,
      "tagList" => null,
      "picture" => null,
      "mirrors" => null,
      "literature" => null,
      "seqhash" => null,
      "groupDescription" => null,
      "torrentDescription" => null,
      "annotated" => null,
      "anonymous" => null,
    ],



]);


exit;












View::header(
    'Upload',
    'upload,vendor/easymde.min',
    'vendor/easymde.min'
);

if (empty($Properties) && !empty($_GET['groupid']) && is_numeric($_GET['groupid'])) {
    $GroupID = $_GET['groupid'];
    $app->dbOld->prepared_query("
      SELECT
        tg.`id` as GroupID,
        tg.`category_id`,
        tg.`title` AS Title,
        tg.`subject`,
        tg.`object` AS TitleJP,
        tg.`year`,
        tg.`workgroup`,
        tg.`location`,
        tg.`identifier`,
        tg.`picture` AS Image,
        tg.`description` AS GroupDescription
      FROM `torrents_group` AS tg
        LEFT JOIN `torrents` AS t ON t.`GroupID` = tg.`id`
      WHERE tg.`id` = '$GroupID'
      GROUP BY tg.`id`
      ");


    if ($app->dbOld->has_results()) {
        list($Properties) = $app->dbOld->to_array(false, MYSQLI_BOTH);
        $UploadForm = $Categories[$Properties['CategoryID'] - 1];
        $Properties['CategoryName'] = $Categories[$Properties['CategoryID'] - 1];
        $Properties['Artists'] = Artists::get_artist($_GET['groupid']);

        $app->dbOld->query("
        SELECT
          GROUP_CONCAT(tags.`Name` SEPARATOR ', ') AS TagList
        FROM
          `torrents_tags` AS tt
        JOIN `tags` ON tags.`ID` = tt.`TagID`
        WHERE
          tt.`GroupID` = '$_GET[groupid]'
        ");
        list($Properties['TagList']) = $app->dbOld->next_record();
    } else {
        unset($_GET['groupid']);
    }

    if (!empty($_GET['requestid']) && is_numeric($_GET['requestid'])) {
        $Properties['RequestID'] = $_GET['requestid'];
    }
} elseif (empty($Properties) && isset($_GET['requestid']) && is_numeric($_GET['requestid'])) {
    $RequestID = $_GET['requestid'];
    $app->dbOld->query("
    SELECT
      `ID` AS RequestID,
      `CategoryID`,
      `Title` AS Title,
      `Title2`,
      `TitleJP` AS TitleJP,
      `CatalogueNumber`,
      `Image`
    FROM
      `requests`
    WHERE
      `ID` = '$RequestID'
    ");
    list($Properties) = $app->dbOld->to_array(false, MYSQLI_BOTH);

    $UploadForm = $Categories[$Properties['CategoryID'] - 1];
    $Properties['CategoryName'] = $Categories[$Properties['CategoryID'] - 1];
    $Properties['Artists'] = Requests::get_artists($_GET['requestid']);
    $Properties['TagList'] = implode(', ', Requests::get_tags($_GET['requestid'])[$_GET['requestid']]);
}

if (!empty($ArtistForm)) {
    $Properties['Artists'] = $ArtistForm;
}

/**
 * TorrentForm
 */
$TorrentForm = new TorrentForm($Properties ?? false, $Err ?? false);

/**
 * Genre tags
 */
$GenreTags = $app->cacheOld->get_value('genre_tags');
if (!$GenreTags) {
    $app->dbOld->query("
    SELECT
      `Name`
    FROM
      `tags`
    WHERE
      `TagType` = 'genre'
    ORDER BY
      `Name`
    ");

    $GenreTags = $app->dbOld->collect('Name');
    $app->cacheOld->cache_value('genre_tags', $GenreTags, 3600 * 6);
}

# Twig based class
$TorrentForm->render();
View::footer();

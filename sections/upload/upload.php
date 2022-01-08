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

View::show_header(
    'Upload',
    'upload,vendor/easymde.min',
    'vendor/easymde.min'
);

if (empty($Properties) && !empty($_GET['groupid']) && is_number($_GET['groupid'])) {
    $GroupID = $_GET['groupid'];
    $DB->prepared_query("
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


    if ($DB->has_results()) {
        list($Properties) = $DB->to_array(false, MYSQLI_BOTH);
        $UploadForm = $Categories[$Properties['CategoryID'] - 1];
        $Properties['CategoryName'] = $Categories[$Properties['CategoryID'] - 1];
        $Properties['Artists'] = Artists::get_artist($_GET['groupid']);

        $DB->query("
        SELECT
          GROUP_CONCAT(tags.`Name` SEPARATOR ', ') AS TagList
        FROM
          `torrents_tags` AS tt
        JOIN `tags` ON tags.`ID` = tt.`TagID`
        WHERE
          tt.`GroupID` = '$_GET[groupid]'
        ");
        list($Properties['TagList']) = $DB->next_record();
    } else {
        unset($_GET['groupid']);
    }

    if (!empty($_GET['requestid']) && is_number($_GET['requestid'])) {
        $Properties['RequestID'] = $_GET['requestid'];
    }
} elseif (empty($Properties) && isset($_GET['requestid']) && is_number($_GET['requestid'])) {
    $RequestID = $_GET['requestid'];
    $DB->query("
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
    list($Properties) = $DB->to_array(false, MYSQLI_BOTH);

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
require_once SERVER_ROOT.'/classes/torrent_form.class.php';
$TorrentForm = new TorrentForm($Properties ?? false, $Err ?? false);

/**
 * Genre tags
 */
$GenreTags = $Cache->get_value('genre_tags');
if (!$GenreTags) {
    $DB->query("
    SELECT
      `Name`
    FROM
      `tags`
    WHERE
      `TagType` = 'genre'
    ORDER BY
      `Name`
    ");
    
    $GenreTags = $DB->collect('Name');
    $Cache->cache_value('genre_tags', $GenreTags, 3600 * 6);
}

# Twig based class
$TorrentForm->render();
View::show_footer();

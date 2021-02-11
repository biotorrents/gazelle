<?php
#declare(strict_types=1);

//**********************************************************************//
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~ Upload form ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//
// This page relies on the TorrentForm class. All it does is call      //
// the necessary functions.                                             //
//----------------------------------------------------------------------//
// $Properties, $Err and $UploadForm are set in takeupload.php, and     //
// are only used when the form doesn't validate and this page must be   //
// called again.                                                        //
//**********************************************************************//

ini_set('max_file_uploads', '100');
View::show_header(
    'Upload',
    'upload,bbcode,vendor/easymde.min',
    'vendor/easymde.min'
);

if (empty($Properties) && !empty($_GET['groupid']) && is_number($_GET['groupid'])) {
    $DB->query('
      SELECT
        tg.ID as GroupID,
        tg.CategoryID,
        tg.Name AS Title,
        tg.Title2,
        tg.NameJP AS TitleJP,
        tg.Year,
        tg.Studio,
        tg.Series,
        tg.CatalogueNumber,
        tg.WikiImage AS Image,
        tg.WikiBody AS GroupDescription
      FROM torrents_group AS tg
        LEFT JOIN torrents AS t ON t.GroupID = tg.ID
      WHERE tg.ID = '.$_GET['groupid'].'
      GROUP BY tg.ID');

    if ($DB->has_results()) {
        list($Properties) = $DB->to_array(false, MYSQLI_BOTH);
        $UploadForm = $Categories[$Properties['CategoryID'] - 1];
        $Properties['CategoryName'] = $Categories[$Properties['CategoryID'] - 1];
        $Properties['Artists'] = Artists::get_artist($_GET['groupid']);

        $DB->query("
          SELECT
            GROUP_CONCAT(tags.Name SEPARATOR ', ') AS TagList
          FROM torrents_tags AS tt
            JOIN tags ON tags.ID = tt.TagID
          WHERE tt.GroupID = '$_GET[groupid]'");
        list($Properties['TagList']) = $DB->next_record();
    } else {
        unset($_GET['groupid']);
    }

    if (!empty($_GET['requestid']) && is_number($_GET['requestid'])) {
        $Properties['RequestID'] = $_GET['requestid'];
    }
} elseif (empty($Properties) && isset($_GET['requestid']) && is_number($_GET['requestid'])) {
    $DB->query('
      SELECT
        ID AS RequestID,
        CategoryID,
        Title AS Title,
        Title2,
        TitleJP AS TitleJP,
        CatalogueNumber,
        Image
      FROM requests
      WHERE ID = '.$_GET['requestid']);

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
      SELECT Name
      FROM tags
      WHERE TagType = 'genre'
        ORDER BY Name");
    
    $GenreTags = $DB->collect('Name');
    $Cache->cache_value('genre_tags', $GenreTags, 3600 * 6);
}

# Page contents
echo $TorrentForm->uploadNotice();
echo $TorrentForm->announceSource();
echo $TorrentForm->error();

# Stuff inside the table layout
echo $TorrentForm->head();
echo $TorrentForm->basicInfo();
echo $TorrentForm->upload_form();
View::show_footer();

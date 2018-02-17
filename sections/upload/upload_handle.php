<?
//****************************************************************************//
//--------------- Take upload ------------------------------------------------//
// This pages handles the backend of the torrent upload function. It checks   //
// the data, and if it all validates, it builds the torrent file, then writes //
// the data to the database and the torrent to the disk.                      //
//****************************************************************************//

// Maximum allowed size for uploaded files.
// http://php.net/upload-max-filesize
ini_set('upload_max_filesize', 2097152); // 2 Mebibytes

ini_set('max_file_uploads', 100);
define('MAX_FILENAME_LENGTH', 180);
include(SERVER_ROOT.'/classes/validate.class.php');
include(SERVER_ROOT.'/classes/feed.class.php');
include(SERVER_ROOT.'/sections/torrents/functions.php');
include(SERVER_ROOT.'/classes/file_checker.class.php');

enforce_login();
authorize();


$Validate = new VALIDATE;
$Feed = new FEED;

define('QUERY_EXCEPTION', true); // Shut up debugging

//*****************************************************************************//
//--------------- Set $Properties array ---------------------------------------//
// This is used if the form doesn't validate, and when the time comes to enter //
// it into the database.                                                       //
// Haha wow god i'm trying to restrict the database to only have fields for    //
// movies and not add anything for other categories but this is fucking dumb   //

$Properties = [];
$Type = $Categories[(int)$_POST['type']];
$TypeID = $_POST['type'] + 1;
$Properties['CategoryID'] = $TypeID;
$Properties['CategoryName'] = $Type;
$Properties['Title'] = $_POST['title'];
$Properties['TitleRJ'] = $_POST['title_rj'];
$Properties['TitleJP'] = $_POST['title_jp'];
$Properties['Year'] = $_POST['year'];

$Properties['Studio'] = isset($_POST['studio']) ? $_POST['studio'] : '';

$Properties['Series'] = isset($_POST['series']) ? $_POST['series'] : '';

$Properties['CatalogueNumber'] = isset($_POST['catalogue']) ? $_POST['catalogue'] : '';
$Properties['Pages'] = isset($_POST['pages']) ? $_POST['pages'] : 0;
$Properties['Container'] = isset($_POST['container']) ? $_POST['container'] : '';

$Properties['Media'] = $_POST['media'];

$Properties['Codec'] = isset($_POST['codec']) ? $_POST['codec'] : '';
if (!($_POST['resolution'] ?? false)) $_POST['resolution'] = $_POST['ressel'] ?? '';
$Properties['Resolution'] = $_POST['resolution'] ?? '';
$Properties['AudioFormat'] = isset($_POST['audioformat']) ? $_POST['audioformat'] : '';
$Properties['Subbing'] = isset($_POST['sub']) ? $_POST['sub'] : '';
$Properties['Language'] = isset($_POST['lang']) ? $_POST['lang'] : '';
$Properties['Subber'] = isset($_POST['subber']) ? $_POST['subber'] : '';
$Properties['DLsiteID'] = (isset($_POST['dlsiteid'])) ? $_POST['dlsiteid'] : '';
$Properties['Censored'] = (isset($_POST['censored'])) ? '1' : '0';
$Properties['Anonymous'] = (isset($_POST['anonymous'])) ? '1' : '0';
$Properties['Archive'] = (isset($_POST['archive']) && $_POST['archive'] != '---') ? $_POST['archive'] : '';
if (isset($_POST['library_image'])) $Properties['LibraryImage'] = $_POST['library_image'];
if (isset($_POST['tags'])) $Properties['TagList'] = implode(',',array_unique(explode(',', str_replace(' ','',$_POST['tags']))));
if (isset($_POST['image'])) $Properties['Image'] = $_POST['image'];

if (isset($_POST['release'])) {
  $Properties['ReleaseGroup'] = $_POST['release'];
}

$Properties['GroupDescription'] = trim($_POST['album_desc']);
$Properties['TorrentDescription'] = $_POST['release_desc'];
$Properties['MediaInfo'] = $_POST['mediainfo'];
$Properties['Screenshots'] = isset($_POST['screenshots']) ? $_POST['screenshots'] : "";

if ($_POST['album_desc']) {
  $Properties['GroupDescription'] = trim($_POST['album_desc']);
} elseif ($_POST['desc']) {
  $Properties['GroupDescription'] = trim($_POST['desc']);
  $Properties['MediaInfo'] = $_POST['mediainfo'];
}

if (isset($_POST['groupid'])) $Properties['GroupID'] = $_POST['groupid'];

if (isset($Properties['GroupID'])) {
  $Properties['Artists'] = Artists::get_artist($Properties['GroupID']);
}

if ($Type == 'Movies' || $Type == 'Manga' || $Type == 'Anime' || $Type == 'Games') {
  if (empty($_POST['idols'])) {
    $Err = "You didn't enter any goils";
  } else {
    $Artists = $_POST['idols'];
  }
} elseif ($Type == 'Other') {
  if (!empty($_POST['idols'])) {
    $Artists = $_POST['idols'];
  }
}

if (!empty($_POST['requestid'])) {
  $RequestID = $_POST['requestid'];
  $Properties['RequestID'] = $RequestID;
}
//******************************************************************************//
//--------------- Validate data in upload form ---------------------------------//

$Validate->SetFields('type', '1', 'inarray', 'Please select a valid type.', array('inarray' => array_keys($Categories)));
switch ($Type) {
  case 'Movies':
  case 'Anime':
    $Validate->SetFields('codec',
      '1','inarray','Please select a valid codec.', array('inarray'=>$Codecs));

    $Validate->SetFields('resolution',
      '1','regex','Please set a valid resolution.', array('regex'=>'/^(SD)|([0-9]+(p|i))|([0-9]K)|([0-9]+x[0-9]+)$/'));

    $Validate->SetFields('audioformat',
      '1','inarray','Please select a valid audio format.', array('inarray'=>$AudioFormats));

    $Validate->SetFields('sub',
      '1','inarray','Please select a valid sub format.', array('inarray'=>$Subbing));

    $Validate->SetFields('censored', '1', 'inarray', 'Set valid censoring', array('inarray'=>array(0, 1)));

  case 'Games':
    $Validate->SetFields('container',
      '1','inarray','Please select a valid container.', array('inarray'=>array_merge($Containers, $ContainersGames)));

  case 'Manga':
    if (!isset($_POST['groupid']) || !$_POST['groupid']) {
      $Validate->SetFields('year',
        '1','number','The year of the original release must be entered.', array('maxlength'=>3000, 'minlength'=>1800));
      if ($Type == 'Manga') {
        $Validate->SetFields('pages',
          '1', 'number', 'That is not a valid page count', array('minlength'=>1));
      }
    }

    $Validate->SetFields('media',
      '1','inarray','Please select a valid media/platform.', array('inarray'=>array_merge($Media, $MediaManga, $Platform)));

    $Validate->SetFields('lang',
      '1','inarray','Please select a valid language.', array('inarray'=>$Languages));

    $Validate->SetFields('release_desc',
      '0','string','The release description has a minimum length of 10 characters.', array('maxlength'=>1000000, 'minlength'=>10));

  default:
    if (!isset($_POST['groupid']) || !$_POST['groupid']) {
      $Validate->SetFields('title',
        '0','string','Title must be between 1 and 300 characters.', array('maxlength'=>300, 'minlength'=>1));
      $Validate->SetFields('title_rj',
        '0','string', 'Romaji Title must be between 1 and 300 characters.', array('maxlength'=>300, 'minlength'=>1));

      $Validate->SetFields('title_jp',
        '0','string','Japanese Title must be between 1 and 512 bytes.', array('maxlength'=>512, 'minlength'=>1));

      $Validate->SetFields('tags',
        '1','string','You must enter at least five tag. Maximum length is 1500 characters.', array('maxlength'=>1500, 'minlength'=>2));

      $Validate->SetFields('image',
        '0','link','The image URL you entered was invalid.', array('maxlength'=>255, 'minlength'=>12));
    }

    $Validate->SetFields('album_desc',
        '1','string','The description has a minimum length of 10 characters.', array('maxlength'=>1000000, 'minlength'=>10));
    $Validate->SetFields('groupid', '0', 'number', 'Group ID was not numeric');
}

$Err = $Validate->ValidateForm($_POST); // Validate the form

if (count(explode(',', $Properties['TagList'])) < 5) {
  $Err = 'You must enter at least 5 tags.';
}

if (!(isset($_POST['title']) || isset($_POST['title_rj']) || isset($_POST['title_jp']))) {
  $Err = 'You must enter at least one title.';
}

$File = $_FILES['file_input']; // This is our torrent file
$TorrentName = $File['tmp_name'];

if (!is_uploaded_file($TorrentName) || !filesize($TorrentName)) {
  $Err = 'No torrent file uploaded, or file is empty.';
} elseif (substr(strtolower($File['name']), strlen($File['name']) - strlen('.torrent')) !== '.torrent') {
  $Err = "You seem to have put something other than a torrent file into the upload field. (".$File['name'].").";
}

//Multiple artists!

$LogName = '';
if (empty($Properties['GroupID']) && empty($ArtistForm)) {
  $ArtistNames = [];
  $ArtistForm = [];
  for ($i = 0; $i < count($Artists); $i++) {
    if (trim($Artists[$i]) != '') {
      if (!in_array($Artists[$i], $ArtistNames)) {
        $ArtistForm[$i] = array('name' => Artists::normalise_artist_name($Artists[$i]));
        array_push($ArtistNames, $ArtistForm[$i]['name']);
      }
    }
  }
  $LogName .= Artists::display_artists($ArtistForm, false, true, false);
} elseif (empty($ArtistForm)) {
  $DB->query("
    SELECT ta.ArtistID, ag.Name
    FROM torrents_artists AS ta
      JOIN artists_group AS ag ON ta.ArtistID = ag.ArtistID
    WHERE ta.GroupID = ".$Properties['GroupID']."
    ORDER BY ag.Name ASC;");
  $ArtistForm = [];
  while (list($ArtistID, $ArtistName) = $DB->next_record(MYSQLI_BOTH, false)) {
    array_push($ArtistForm, array('id' => $ArtistID, 'name' => display_str($ArtistName)));
    array_push($ArtistsUnescaped, array('name' => $ArtistName));
  }
  $LogName .= Artists::display_artists($ArtistsUnescaped, false, true, false);
}


if ($Err) { // Show the upload form, with the data the user entered
  $UploadForm = $Type;
  include(SERVER_ROOT.'/sections/upload/upload.php');
  die();
}

ImageTools::blacklisted($Properties['Image']);

//******************************************************************************//
//--------------- Make variables ready for database input ----------------------//

// Prepared SQL statements do this for us, so there is nothing to do here anymore
$T = $Properties;

//******************************************************************************//
//--------------- Generate torrent file ----------------------------------------//

$Tor = new BencodeTorrent($TorrentName, true);
$PublicTorrent = $Tor->make_private(); // The torrent is now private.
$UnsourcedTorrent = $Tor->make_sourced(); // The torrent now has the source field set.
$InfoHash = pack('H*', $Tor->info_hash());

if (isset($Tor->Dec['encrypted_files'])) {
  $Err = 'This torrent contains an encrypted file list which is not supported here.';
}

// File list and size
list($TotalSize, $FileList) = $Tor->file_list();
$NumFiles = count($FileList);
$TmpFileList = [];
$TooLongPaths = [];
$DirName = (isset($Tor->Dec['info']['files']) ? Format::make_utf8($Tor->get_name()) : '');
check_name($DirName); // check the folder name against the blacklist
foreach ($FileList as $File) {
  list($Size, $Name) = $File;
  // Check file name and extension against blacklist/whitelist
  check_file($Type, $Name);
  // Make sure the filename is not too long
  if (mb_strlen($Name, 'UTF-8') + mb_strlen($DirName, 'UTF-8') + 1 > MAX_FILENAME_LENGTH) {
    $TooLongPaths[] = "$DirName/$Name";
  }
  // Add file info to array
  $TmpFileList[] = Torrents::filelist_format_file($File);
}
if (count($TooLongPaths) > 0) {
  $Names = implode(' <br />', $TooLongPaths);
  $Err = "The torrent contained one or more files with too long a name:<br /> $Names";
}
$FilePath = $DirName;
$FileString = implode("\n", $TmpFileList);
$Debug->set_flag('upload: torrent decoded');

if (!empty($Err)) { // Show the upload form, with the data the user entered
  $UploadForm = $Type;
  include(SERVER_ROOT.'/sections/upload/upload.php');
  die();
}

//******************************************************************************//
//--------------- Start database stuff -----------------------------------------//

$Body = $T['GroupDescription'];

// Trickery
if (!preg_match('/^'.IMAGE_REGEX.'$/i', $T['Image'])) {
  $T['Image'] = '';
}

// Does it belong in a group?
if ($T['GroupID']) {
  $DB->query("
    SELECT
      ID,
      WikiImage,
      WikiBody,
      RevisionID,
      Name,
      Year,
      TagList
    FROM torrents_group
    WHERE id = ?", $T['GroupID']);
  if ($DB->has_results()) {
    // Don't escape tg.Name. It's written directly to the log table
    list($GroupID, $WikiImage, $WikiBody, $RevisionID, $T['Title'], $T['Year'], $T['TagList']) = $DB->next_record(MYSQLI_NUM, array(4));
    $T['TagList'] = str_replace(array(' ', '.', '_'), array(', ', '.', '.'), $T['TagList']);
    if (!$T['Image'] && $WikiImage) {
      $T['Image'] = $WikiImage;
    }
    if (strlen($WikiBody) > strlen($Body)) {
      $Body = $WikiBody;
      if (!$T['Image'] || $T['Image'] == $WikiImage) {
        $NoRevision = true;
      }
    }
    $T['Artist'] = Artists::display_artists(Artists::get_artist($GroupID), false, false);
  }
}
if (!isset($GroupID) || !$GroupID) {
  foreach ($ArtistForm as $Num => $Artist) {
    // The album hasn't been uploaded. Try to get the artist IDs
    $DB->query("
      SELECT
        ArtistID,
        Name
      FROM artists_group
      WHERE Name = ?", $Artist['name']);
    if ($DB->has_results()) {
      while (list($ArtistID, $Name) = $DB->next_record(MYSQLI_NUM, false)) {
        if (!strcasecmp($Artist['name'], $Name)) {
          $ArtistForm[$Num] = ['id' => $ArtistID, 'name' => $Name];
          break;
        }
      }
    }
  }
}

//Needs to be here as it isn't set for add format until now
$LogName .= $T['Title'];

//For notifications--take note now whether it's a new group
$IsNewGroup = !isset($GroupID) || !$GroupID;

//----- Start inserts
if ((!isset($GroupID) || !$GroupID)) {
  //array to store which artists we have added already, to prevent adding an artist twice
  $ArtistsAdded = [];
  foreach ($ArtistForm as $Num => $Artist) {
    if (!isset($Artist['id']) || !$Artist['id']) {
      if (isset($ArtistsAdded[strtolower($Artist['name'])])) {
        $ArtistForm[$Num] = $ArtistsAdded[strtolower($Artist['name'])];
      } else {
        // Create artist
        $DB->query("
          INSERT INTO artists_group (Name)
          VALUES ( ? )", $Artist['name']);
        $ArtistID = $DB->inserted_id();

        $Cache->increment('stats_artist_count');

        $ArtistForm[$Num] = array('id' => $ArtistID, 'name' => $Artist['name']);
        $ArtistsAdded[strtolower($Artist['name'])] = $ArtistForm[$Num];
      }
    }
  }
  unset($ArtistsAdded);
}

if (!isset($GroupID) || !$GroupID) {
  // Create torrent group
  $DB->query("
    INSERT INTO torrents_group
      (CategoryID, Name, NameRJ, NameJP, Year,
      Series, Studio, CatalogueNumber, Pages, Time,
      WikiBody, WikiImage, DLsiteID)
    VALUES
      ( ?, ?, ?, ?, ?,
        ?, ?, ?, ?, NOW(),
        ?, ?, ? )",
    $TypeID, $T['Title'], $T['TitleRJ'], $T['TitleJP'], $T['Year'],
    $T['Series'], $T['Studio'], $T['CatalogueNumber'], $T['Pages'],
    $Body, $T['Image'], $T['DLsiteID']);
  $GroupID = $DB->inserted_id();
  foreach ($ArtistForm as $Num => $Artist) {
    $DB->query("
      INSERT IGNORE INTO torrents_artists (GroupID, ArtistID, UserID)
      VALUES ( ?, ?, ? )", $GroupID, $Artist['id'], $LoggedUser['ID']);
    $Cache->increment('stats_album_count');
    $Cache->delete_value('artist_groups_'.$Artist['id']);
  }
  $Cache->increment('stats_group_count');

  // Add screenshots
  $Screenshots = explode("\n", $T['Screenshots']);
  $Screenshots = array_map("trim", $Screenshots);
  $Screenshots = array_filter($Screenshots, function($s) {
    return preg_match('/^'.IMAGE_REGEX.'$/i', $s);
  });
  $Screenshots = array_unique($Screenshots);
  $Screenshots = array_slice($Screenshots, 0, 10);

  if (!empty($Screenshots)) {
    $Screenshot = '';
    $DB->prepare_query("
      INSERT INTO torrents_screenshots
        (GroupID, UserID, Time, Image)
      VALUES (?, ?, NOW(), ?)", $GroupID, $LoggedUser['ID'], $Screenshot);
    foreach ($Screenshots as $Screenshot) {
      $DB->exec_prepared_query();
    }
  }

} else {
  $DB->query("
    UPDATE torrents_group
    SET Time = NOW()
    WHERE ID = ?", $GroupID);
  $Cache->delete_value("torrent_group_$GroupID");
  $Cache->delete_value("torrents_details_$GroupID");
  $Cache->delete_value("detail_files_$GroupID");
}

// Description
if (!isset($NoRevision) || !$NoRevision) {
  $DB->query("
    INSERT INTO wiki_torrents
      (PageID, Body, UserID, Summary, Time, Image)
    VALUES
      ( ?, ?, ?, 'Uploaded new torrent', NOW(), ? )", $GroupID, $T['GroupDescription'], $LoggedUser['ID'], $T['Image']);
  $RevisionID = $DB->inserted_id();

  // Revision ID
  $DB->query("
    UPDATE torrents_group
    SET RevisionID = ?
    WHERE ID = ?", $RevisionID, $GroupID);
}

// Tags
$Tags = explode(',', $T['TagList']);
if (!$T['GroupID']) {
  foreach ($Tags as $Tag) {
    $Tag = Misc::sanitize_tag($Tag);
    if (!empty($Tag)) {
    $Tag = Misc::get_alias_tag($Tag);
      $DB->query("
        INSERT INTO tags
          (Name, UserID)
        VALUES
          ( ?, ? )
        ON DUPLICATE KEY UPDATE
          Uses = Uses + 1;", $Tag, $LoggedUser['ID']);
      $TagID = $DB->inserted_id();

      $DB->query("
        INSERT INTO torrents_tags
          (TagID, GroupID, UserID)
        VALUES
          ( ?, ?, ? )
        ON DUPLICATE KEY UPDATE TagID=TagID", $TagID, $GroupID, $LoggedUser['ID']);
    }
  }
}


// Use this section to control freeleeches
$DB->query("
  SELECT Name, First, Second
  FROM misc
  WHERE Second = 'freeleech'");
if ($DB->has_results()) {
  $FreeLeechTags = $DB->to_array('Name');
  foreach ($FreeLeechTags as $Tag => $Exp) {
    if ($Tag == 'global' || in_array($Tag, $Tags)) {
      $T['FreeTorrent'] = '1';
      $T['FreeLeechType'] = '3';
      break;
    }
  }
} else {
  $T['FreeTorrent'] = '0';
  $T['FreeLeechType'] = '0';
}

// movie and anime ISOs are neutral leech, and receive a BP bounty
if (($Type == 'Movies' || $Type == 'Anime') && ($T['Container'] == 'ISO' || $T['Container'] == 'M2TS' || $T['Container'] == 'VOB IFO')) {
  $T['FreeTorrent'] = '2';
  $T['FreeLeechType'] = '2';
}

// Torrent
$DB->query("
  INSERT INTO torrents
    (GroupID, UserID, Media, Container, Codec, Resolution,
    AudioFormat, Subbing, Language, Subber, Censored,
    Anonymous, Archive, info_hash, FileCount, FileList, FilePath, Size, Time,
    Description, MediaInfo, FreeTorrent, FreeLeechType)
  VALUES
    ( ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?, ?, ?, NOW(),
      ?, ?, ?, ? )",
  $GroupID, $LoggedUser['ID'], $T['Media'], $T['Container'], $T['Codec'], $T['Resolution'],
  $T['AudioFormat'], $T['Subbing'], $T['Language'], $T['Subber'], $T['Censored'],
  $T['Anonymous'], $T['Archive'], $InfoHash, $NumFiles, $FileString, $FilePath, $TotalSize,
  $T['TorrentDescription'], $T['MediaInfo'], $T['FreeTorrent'], $T['FreeLeechType']);

$Cache->increment('stats_torrent_count');
$TorrentID = $DB->inserted_id();
$Tor->Dec['comment'] = 'https://'.SITE_DOMAIN.'/torrents.php?torrentid='.$TorrentID;

Tracker::update_tracker('add_torrent', [
  'id'          => $TorrentID,
  'info_hash'   => rawurlencode($InfoHash),
  'freetorrent' => $T['FreeTorrent']
]);
$Debug->set_flag('upload: ocelot updated');

// Prevent deletion of this torrent until the rest of the upload process is done
// (expire the key after 10 minutes to prevent locking it for too long in case there's a fatal error below)
$Cache->cache_value("torrent_{$TorrentID}_lock", true, 600);

//give BP if necessary
if (($Type == "Movies" || $Type == "Anime") && ($T['Container'] == "'ISO'" || $T['Container'] == "'M2TS'" || $T['Container'] == "'VOB IFO'")) {
  $BPAmt = (int) 2*($TotalSize / (1024*1024*1024))*1000;

  $DB->query("
    UPDATE users_main
    SET BonusPoints = BonusPoints + ?
    WHERE ID = ?", $BPAmt, $LoggedUser['ID']);

   $DB->query("
    UPDATE users_info
    SET AdminComment = CONCAT(NOW(), ' - Received $BPAmt ".BONUS_POINTS." for uploading a torrent $TorrentID\n\n', AdminComment)
    WHERE UserID = ?", $LoggedUser['ID']);
    $Cache->delete_value('user_info_heavy_'.$LoggedUser['ID']);
    $Cache->delete_value('user_stats_'.$LoggedUser['ID']);
}

// Add to shop freeleeches if necessary
if ($T['FreeLeechType'] == 3) {
// Figure out which duration to use
  $Expiry = 0;
  foreach ($FreeLeechTags as $Tag => $Exp) {
    if ($Tag == 'global' || in_array($Tag, $Tags)) {
      if (((int) $FreeLeechTags[$Tag]['First']) > $Expiry)
        $Expiry = (int) $FreeLeechTags[$Tag]['First'];
    }
  }
  if ($Expiry > 0) {
    $DB->query("
      INSERT INTO shop_freeleeches
        (TorrentID, ExpiryTime)
      VALUES
        (" . $TorrentID . ", FROM_UNIXTIME(" . $Expiry . "))
      ON DUPLICATE KEY UPDATE
        ExpiryTime = FROM_UNIXTIME(UNIX_TIMESTAMP(ExpiryTime) + ($Expiry - FROM_UNIXTIME(NOW())))");
  } else {
    Torrents::freeleech_torrents($TorrentID, 0, 0);
  }
}

//******************************************************************************//
//--------------- Write torrent file -------------------------------------------//

file_put_contents(TORRENT_STORE.$TorrentID.'.torrent', $Tor->encode());
Misc::write_log("Torrent $TorrentID ($LogName) (".number_format($TotalSize / (1024 * 1024), 2).' MB) was uploaded by ' . $LoggedUser['Username']);
Torrents::write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], 'uploaded ('.number_format($TotalSize / (1024 * 1024), 2).' MB)', 0);

Torrents::update_hash($GroupID);
$Debug->set_flag('upload: sphinx updated');

//******************************************************************************//
//---------------------- Recent Uploads ----------------------------------------//

if (trim($T['Image']) != '') {
  $RecentUploads = $Cache->get_value("recent_uploads_$UserID");
  if (is_array($RecentUploads)) {
    do {
      foreach ($RecentUploads as $Item) {
        if ($Item['ID'] == $GroupID) {
          break 2;
        }
      }

      // Only reached if no matching GroupIDs in the cache already.
      if (count($RecentUploads) === 5) {
        array_pop($RecentUploads);
      }
      array_unshift($RecentUploads, array(
            'ID' => $GroupID,
            'Name' => trim($T['Title']),
            'Artist' => Artists::display_artists($ArtistForm, false, true),
            'WikiImage' => trim($T['Image'])));
      $Cache->cache_value("recent_uploads_$UserID", $RecentUploads, 0);
    } while (0);
  }
}

//******************************************************************************//
//------------------------------- Post-processing ------------------------------//
/* Because tracker updates and notifications can be slow, we're
 * redirecting the user to the destination page and flushing the buffers
 * to make it seem like the PHP process is working in the background.
 */

if ($PublicTorrent) {
  View::show_header('Warning');
?>
  <h1>Warning</h1>
  <p><strong>Your torrent has been uploaded - but you must re-download your torrent file from <a href="torrents.php?id=<?=$GroupID?>&torrentid=<?=$TorrentID?>">here</a> because the site modified it to make it private.</strong></p>
<?
  View::show_footer();
} elseif ($UnsourcedTorrent) {
  View::show_header('Warning');
?>
  <h1>Warning</h1>
  <p><strong>Your torrent has been uploaded - but you must re-download your torrent file from <a href="torrents.php?id=<?=$GroupID?>&torrentid=<?=$TorrentID?>">here</a> because the site modified it to add a source flag.</strong></p>
<?
  View::show_footer();
} elseif ($RequestID) {
  header("Location: requests.php?action=takefill&requestid=$RequestID&torrentid=$TorrentID&auth=".$LoggedUser['AuthKey']);
} else {
  header("Location: torrents.php?id=$GroupID&torrentid=$TorrentID");
}
if (function_exists('fastcgi_finish_request')) {
  fastcgi_finish_request();
} else {
  ignore_user_abort(true);
  ob_flush();
  flush();
  ob_start(); // So we don't keep sending data to the client
}

//******************************************************************************//
//--------------------------- IRC announce and feeds ---------------------------//
$Announce = '';

$Announce .= Artists::display_artists($ArtistForm, false);

$Announce .= substr(trim(empty($T['Title']) ? (empty($T['TitleRJ']) ? $T['TitleJP'] : $T['TitleRJ']) : $T['Title']), 0, 100);
$Announce .= ' ';
if ($Type != 'Other') {
  $Announce .= '['.Torrents::torrent_info($T, false, false, false).']';
}
$Title = '['.$T['CategoryName'].'] '.$Announce;

$Announce = "$Title - ".site_url()."torrents.php?id=$GroupID / ".site_url()."torrents.php?action=download&id=$TorrentID";

$Announce .= ' - '.trim($T['TagList']);

// ENT_QUOTES is needed to decode single quotes/apostrophes
send_irc('PRIVMSG '.BOT_ANNOUNCE_CHAN.' '.html_entity_decode($Announce, ENT_QUOTES));
$Debug->set_flag('upload: announced on irc');

// Manage notifications

// For RSS
$Item = $Feed->item($Title, Text::strip_bbcode($Body), 'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id='.$TorrentID, $LoggedUser['Username'], 'torrents.php?id='.$GroupID, trim($T['TagList']));


//Notifications
$SQL = "
  SELECT unf.ID, unf.UserID, torrent_pass
  FROM users_notify_filters AS unf
    JOIN users_main AS um ON um.ID = unf.UserID
  WHERE um.Enabled = '1'";
if (empty($ArtistsUnescaped)) {
  $ArtistsUnescaped = $ArtistForm;
}
if (!empty($ArtistsUnescaped)) {
  $ArtistNameList = [];
  $GuestArtistNameList = [];
  foreach ($ArtistsUnescaped as $Importance => $Artist) {
    $ArtistNameList[] = "Artists LIKE '%|".db_string(str_replace('\\', '\\\\', $Artist['name']), true)."|%'";
  }
  // Don't add notification if >2 main artists or if tracked artist isn't a main artist
  if (count($ArtistNameList) > 2 || $Artist['name'] == 'Various Artists') {
    $SQL .= " AND (ExcludeVA = '0' AND (";
    $SQL .= implode(' OR ', array_merge($ArtistNameList, $GuestArtistNameList));
    $SQL .= " OR Artists = '')) AND (";
  } else {
    $SQL .= " AND (";
    if (!empty($GuestArtistNameList)) {
      $SQL .= "(ExcludeVA = '0' AND (";
      $SQL .= implode(' OR ', $GuestArtistNameList);
      $SQL .= ')) OR ';
    }
    if (count($ArtistNameList) > 0) {
      $SQL .= implode(' OR ', $ArtistNameList);
      $SQL .= " OR ";
    }
    $SQL .= "Artists = '') AND (";
  }
} else {
  $SQL .= "AND (Artists = '') AND (";
}

reset($Tags);
$TagSQL = [];
$NotTagSQL = [];
foreach ($Tags as $Tag) {
  $TagSQL[] = " Tags LIKE '%|".db_string(trim($Tag))."|%' ";
  $NotTagSQL[] = " NotTags LIKE '%|".db_string(trim($Tag))."|%' ";
}
$TagSQL[] = "Tags = ''";
$SQL .= implode(' OR ', $TagSQL);

$SQL .= ") AND !(".implode(' OR ', $NotTagSQL).')';

$SQL .= " AND (Categories LIKE '%|".db_string(trim($Type))."|%' OR Categories = '') ";

if ($T['ReleaseType']) {
  $SQL .= " AND (ReleaseTypes LIKE '%|".db_string(trim($ReleaseTypes[$T['ReleaseType']]))."|%' OR ReleaseTypes = '') ";
} else {
  $SQL .= " AND (ReleaseTypes = '') ";
}

/*
  Notify based on the following:
    1. The torrent must match the formatbitrate filter on the notification
    2. If they set NewGroupsOnly to 1, it must also be the first torrent in the group to match the formatbitrate filter on the notification
*/

if ($T['Format']) {
  $SQL .= " AND (Formats LIKE '%|".db_string(trim($T['Format']))."|%' OR Formats = '') ";
} else {
  $SQL .= " AND (Formats = '') ";
}

if ($_POST['bitrate']) {
  $SQL .= " AND (Encodings LIKE '%|".db_string(trim($_POST['bitrate']))."|%' OR Encodings = '') ";
} else {
  $SQL .= " AND (Encodings = '') ";
}

if ($T['Media']) {
  $SQL .= " AND (Media LIKE '%|".db_string(trim($T['Media']))."|%' OR Media = '') ";
} else {
  $SQL .= " AND (Media = '') ";
}

// Either they aren't using NewGroupsOnly
$SQL .= "AND ((NewGroupsOnly = '0' ";
// Or this is the first torrent in the group to match the formatbitrate filter
$SQL .= ") OR ( NewGroupsOnly = '1' ";
$SQL .= '))';


if ($T['Year']) {
  $SQL .= " AND (('".db_string(trim($T['Year']))."' BETWEEN FromYear AND ToYear)
      OR (FromYear = 0 AND ToYear = 0)) ";
} else {
  $SQL .= " AND (FromYear = 0 AND ToYear = 0) ";
}
$SQL .= " AND UserID != '".$LoggedUser['ID']."' ";

$DB->query("
  SELECT Paranoia
  FROM users_main
  WHERE ID = $LoggedUser[ID]");
list($Paranoia) = $DB->next_record();
$Paranoia = unserialize($Paranoia);
if (!is_array($Paranoia)) {
  $Paranoia = [];
}
if (!in_array('notifications', $Paranoia)) {
  $SQL .= " AND (Users LIKE '%|".$LoggedUser['ID']."|%' OR Users = '') ";
}

$SQL .= " AND UserID != '".$LoggedUser['ID']."' ";
$DB->query($SQL);
$Debug->set_flag('upload: notification query finished');

if ($DB->has_results()) {
  $UserArray = $DB->to_array('UserID');
  $FilterArray = $DB->to_array('ID');

  $InsertSQL = '
    INSERT IGNORE INTO users_notify_torrents (UserID, GroupID, TorrentID, FilterID)
    VALUES ';
  $Rows = [];
  foreach ($UserArray as $User) {
    list($FilterID, $UserID, $Passkey) = $User;
    $Rows[] = "('$UserID', '$GroupID', '$TorrentID', '$FilterID')";
    $Feed->populate("torrents_notify_$Passkey", $Item);
    $Cache->delete_value("notifications_new_$UserID");
  }
  $InsertSQL .= implode(',', $Rows);
  $DB->query($InsertSQL);
  $Debug->set_flag('upload: notification inserts finished');

  foreach ($FilterArray as $Filter) {
    list($FilterID, $UserID, $Passkey) = $Filter;
    $Feed->populate("torrents_notify_{$FilterID}_$Passkey", $Item);
  }
}

// RSS for bookmarks
$DB->query("
  SELECT u.ID, u.torrent_pass
  FROM users_main AS u
    JOIN bookmarks_torrents AS b ON b.UserID = u.ID
  WHERE b.GroupID = $GroupID");
while (list($UserID, $Passkey) = $DB->next_record()) {
  $Feed->populate("torrents_bookmarks_t_$Passkey", $Item);
}

$Feed->populate('torrents_all', $Item);
$Feed->populate('torrents_'.strtolower($Type), $Item);
$Debug->set_flag('upload: notifications handled');

// Clear cache
$Cache->delete_value("torrents_details_$GroupID");
$Cache->delete_value("contest_scores");

// Allow deletion of this torrent now
$Cache->delete_value("torrent_{$TorrentID}_lock");

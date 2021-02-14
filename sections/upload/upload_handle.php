<?php
#declare(strict_types=1);

//****************************************************************************//
//--------------- Take upload ------------------------------------------------//
// This pages handles the backend of the torrent upload function. It checks   //
// the data, and if it all validates, it builds the torrent file, then writes //
// the data to the database and the torrent to the disk.                      //
//****************************************************************************//

include SERVER_ROOT.'/classes/validate.class.php';
include SERVER_ROOT.'/classes/feed.class.php';
include SERVER_ROOT.'/sections/torrents/functions.php';

enforce_login();
authorize();

$ENV = ENV::go();
$Validate = new Validate;
$Feed = new Feed;


//*****************************************************************************//
//--------------- Set $Properties array ---------------------------------------//
// This is used if the form doesn't validate, and when the time comes to enter //
// it into the database.
// todo: Do something about this mess
//****************************************************************************//

$Properties = [];
$Type = $Categories[(int) $_POST['type']];
$TypeID = $_POST['type'] + 1;

$Properties['CategoryID'] = $TypeID;
$Properties['CategoryName'] = $Type;

$Properties['Title'] = $_POST['title']; # Title1
$Properties['Title2'] = $_POST['title_rj']; # Title2
$Properties['TitleJP'] = $_POST['title_jp']; # Title3
$Properties['CatalogueNumber'] = isset($_POST['catalogue']) ? $_POST['catalogue'] : '';

$Properties['Year'] = $_POST['year'];
$Properties['Studio'] = isset($_POST['studio']) ? $_POST['studio'] : '';
$Properties['Series'] = isset($_POST['series']) ? $_POST['series'] : '';
$Properties['Container'] = isset($_POST['container']) ? $_POST['container'] : '';
$Properties['Media'] = $_POST['media'];
$Properties['Codec'] = isset($_POST['codec']) ? $_POST['codec'] : '';

if (!($_POST['resolution'] ?? false)) {
    $_POST['resolution'] = $_POST['ressel'] ?? '';
}

$Properties['Resolution'] = $_POST['resolution'] ?? '';
$Properties['Version'] = $_POST['version'] ?? '';
$Properties['Censored'] = (isset($_POST['censored'])) ? '1' : '0';
$Properties['Anonymous'] = (isset($_POST['anonymous'])) ? '1' : '0';
$Properties['Archive'] = (isset($_POST['archive']) && $_POST['archive'] !== '---') ? $_POST['archive'] : '';

if (isset($_POST['library_image'])) {
    $Properties['LibraryImage'] = $_POST['library_image'];
}

if (isset($_POST['tags'])) {
    $Properties['TagList'] = implode(',', array_unique(explode(',', str_replace(' ', '', $_POST['tags']))));
}

if (isset($_POST['image'])) {
    $Properties['Image'] = $_POST['image'];
}

if (isset($_POST['release'])) {
    $Properties['ReleaseGroup'] = $_POST['release'];
}

$Properties['GroupDescription'] = trim($_POST['album_desc']);
$Properties['TorrentDescription'] = $_POST['release_desc'];
$Properties['Screenshots'] = isset($_POST['screenshots']) ? $_POST['screenshots'] : '';
$Properties['Mirrors'] = isset($_POST['mirrors']) ? $_POST['mirrors'] : '';

if ($_POST['album_desc']) {
    $Properties['GroupDescription'] = trim($_POST['album_desc']);
} elseif ($_POST['desc']) {
    $Properties['GroupDescription'] = trim($_POST['desc']);
}

if (isset($_POST['groupid'])) {
    $Properties['GroupID'] = $_POST['groupid'];
}

if (isset($Properties['GroupID'])) {
    $Properties['Artists'] = Artists::get_artist($Properties['GroupID']);
}

if (empty($_POST['artists'])) {
    $Err = "You didn't enter any creators";
} else {
    $Artists = $_POST['artists'];
}

if (!empty($_POST['requestid'])) {
    $RequestID = $_POST['requestid'];
    $Properties['RequestID'] = $RequestID;
}

//******************************************************************************//
//--------------- Validate data in upload form ---------------------------------//

# Submit button
$Validate->SetFields(
    'type',
    '1',
    'inarray',
    'Please select a valid type.',
    array('inarray' => array_keys($Categories))
);

# torrents_group.CategoryID
$Validate->SetFields(
    'type',
    '1',
    'inarray',
    'Please select a valid type.',
    array('inarray' => array_keys($Categories))
);

# todo: Remove the switch statement
switch ($Type) {
    /*
  case 'Imaging':
    if (!isset($_POST['groupid']) || !$_POST['groupid']) {
        # torrents.Media
        $Validate->SetFields(
            'media',
            '1',
            'inarray',
            'Please select a valid platform.',
            array('inarray' => array_merge($Media, $MediaManga, $Platform))
        );

        # torrents.Container
        $Validate->SetFields(
            'container',
            '1',
            'inarray',
            'Please select a valid format.',
            array('inarray' => array_merge($Containers, $ContainersGames))
        );
    }
break;
*/

default:
    if (!isset($_POST['groupid']) || !$_POST['groupid']) {
        # torrents_group.CatalogueNumber
        $Validate->SetFields(
            'catalogue',
            '0',
            'string',
            'Accession Number must be between 0 and 50 characters.',
            array('maxlength' => 50, 'minlength' => 0)
        );

        # torrents.Version
        $Validate->SetFields(
            'version',
            '0',
            'string',
            'Version must be between 0 and 10 characters.',
            array('maxlength' => 10, 'minlength' => 0)
        );
        
        # torrents_group.Name
        $Validate->SetFields(
            'title',
            '1',
            'string',
            'Torrent Title must be between 10 and 255 characters.',
            array('maxlength' => 255, 'minlength' => 10)
        );

        # torrents_group.Title2
        $Validate->SetFields(
            'title_rj',
            '0',
            'string',
            'Organism must be between 0 and 255 characters.',
            array('maxlength' => 255, 'minlength' => 0)
        );

        # torrents_group.NameJP
        $Validate->SetFields(
            'title_jp',
            '0',
            'string',
            'Strain/Variety must be between 0 and 255 characters.',
            array('maxlength' => 255, 'minlength' => 0)
        );

        # torrents_group.Studio
        $Validate->SetFields(
            'studio',
            '1',
            'string',
            'Department/Lab must be between 0 and 100 characters.',
            array('maxlength' => 100, 'minlength' => 0)
        );

        # torrents_group.Series
        $Validate->SetFields(
            'series',
            '0',
            'string',
            'Location must be between 0 and 100 characters.',
            array('maxlength' => 100, 'minlength' => 0)
        );

        /* todo: Fix the year validation
        # torrents_group.Year
        $Validate->SetFields(
            'year',
            '1',
            'number',
            'The year of the original release must be entered.',
            array('maxlength' => 4, 'minlength' => 4)
        );
        */

        # torrents.Media
        $Validate->SetFields(
            'media',
            '1',
            'inarray',
            'Please select a valid platform.',
            array('inarray' => array_merge(
                $SeqPlatforms,
                $GraphPlatforms,
                $ImgPlatforms,
                $DocPlatforms,
                $RawPlatforms
            ))
        );

        /*
        # torrents.Container
        $Validate->SetFields(
            'container',
            '1',
            'inarray',
            'Please select a valid format.',
            array('inarray' => array_merge($Containers, $ContainersGames))
        );
        */

        # torrents.Resolution
        $Validate->SetFields(
            'resolution',
            '1',
            'string',
            'Scope must be between 4 and 20 characters.',
            array('maxlength' => 20, 'minlength' => 4)
        );
        
        # torrents_group.TagList
        $Validate->SetFields(
            'tags',
            '1',
            'string',
            'You must enter at least five tags. Maximum length is 500 characters.',
            array('maxlength' => 500, 'minlength' => 10)
        );

        # torrents_group.WikiImage
        $Validate->SetFields(
            'image',
            '0',
            'link',
            'The image URL you entered was invalid.',
            array('maxlength' => 255, 'minlength' => 10) # x.yz/a.bc
        );
    }

    # torrents_group.WikiBody
    $Validate->SetFields(
        'album_desc',
        '1',
        'string',
        'The description must be between 100 and 65535 characters.',
        array('maxlength' => 65535, 'minlength' => 100)
    );

    /* todo: Fix the Group ID validation
    # torrents_group.ID
    $Validate->SetFields(
        'groupid',
        '0',
        'number',
        'Group ID was not numeric.'
    );
    */
}

$Err = $Validate->ValidateForm($_POST); // Validate the form

# todo: Move all this validation code to the Validate class
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

// Multiple artists!
$LogName = '';
if (empty($Properties['GroupID']) && empty($ArtistForm)) {
    $ArtistNames = [];
    $ArtistForm = [];
    for ($i = 0; $i < count($Artists); $i++) {
        if (trim($Artists[$i]) !== '') {
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
      WHERE ta.GroupID = ?
      ORDER BY ag.Name ASC", $Properties['GroupID']);

    $ArtistForm = [];
    while (list($ArtistID, $ArtistName) = $DB->next_record(MYSQLI_BOTH, false)) {
        array_push($ArtistForm, array('id' => $ArtistID, 'name' => display_str($ArtistName)));
        array_push($ArtistsUnescaped, array('name' => $ArtistName));
    }
    $LogName .= Artists::display_artists($ArtistsUnescaped, false, true, false);
}

if ($Err) { // Show the upload form, with the data the user entered
    $UploadForm = $Type;
    include SERVER_ROOT.'/sections/upload/upload.php' ;
    error(400, $NoHTML = true);
}

ImageTools::blacklisted($Properties['Image']);

//******************************************************************************//
//--------------- Make variables ready for database input ----------------------//

// Prepared SQL statements do this for us, so there is nothing to do here anymore
$T = $Properties;

//******************************************************************************//
//--------------- Generate torrent file ----------------------------------------//

$Tor = new BencodeTorrent($TorrentName, true);
$PublicTorrent = $Tor->make_private(); // The torrent is now private
$UnsourcedTorrent = $Tor->make_sourced(); // The torrent now has the source field set
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
check_name($DirName); // Check the folder name against the blacklist
foreach ($FileList as $File) {
    list($Size, $Name) = $File;
    // Check file name and extension against blacklist/whitelist
    check_file($Type, $Name);
    // Make sure the filename is not too long
    if (mb_strlen($Name, 'UTF-8') + mb_strlen($DirName, 'UTF-8') + 1 > 255) { # MAX_FILENAME_LENGTH
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
    error();
}

//******************************************************************************//
//--------------- Autofill format and archive ----------------------------------//

if ($T['Container'] === 'Autofill') {
    # torrents.Container
    $T['Container'] = $Validate->ParseExtensions(
        # $FileList
        $Tor->file_list(),

        # $Category
        $T['CategoryName'],

        # $FileTypes
        $T['FileTypes'],
    );
}

if ($T['Archive'] === 'Autofill') {
    # torrents.Archive
    $T['Archive'] = $Validate->ParseExtensions(
        # $FileList
        $Tor->file_list(),

        # $Category
        array_keys($Archives),

        # $FileTypes
        array_merge($Archives),
    );
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
            if (!$T['Image'] || $T['Image'] === $WikiImage) {
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

// Needs to be here as it isn't set for add format until now
$LogName .= $T['Title'];

// For notifications. Take note now whether it's a new group
$IsNewGroup = !isset($GroupID) || !$GroupID;

//----- Start inserts
if ((!isset($GroupID) || !$GroupID)) {
    // Array to store which artists we have added already, to prevent adding an artist twice
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
    $DB->query(
        "
      INSERT INTO torrents_group
        (CategoryID, Name, Title2, NameJP, Year,
        Series, Studio, CatalogueNumber, Time,
        WikiBody, WikiImage)
      VALUES
        ( ?, ?, ?, ?, ?,
          ?, ?, ?, NOW(),
          ?, ? )",
        $TypeID,
        $T['Title'],
        $T['Title2'],
        $T['TitleJP'],
        $T['Year'],
        $T['Series'],
        $T['Studio'],
        $T['CatalogueNumber'],
        $Body,
        $T['Image']
    );

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
    // todo: Clear DB_MYSQL::exec_prepared_query() errors
    $Screenshots = explode("\n", $T['Screenshots']);
    $Screenshots = array_map('trim', $Screenshots);

    $Screenshots = array_filter($Screenshots, function ($s) {
        return preg_match('/^'.$ENV->DOI_REGEX.'$/i', $s);
    });

    $Screenshots = array_unique($Screenshots);
    $Screenshots = array_slice($Screenshots, 0, 10);

    # Add optional web seeds similar to screenshots
    # Support an arbitrary and limited number of sources
    $Mirrors = explode("\n", $T['Mirrors']);
    $Mirrors = array_map('trim', $Mirrors);

    $Mirrors = array_filter($Mirrors, function ($s) {
        return preg_match('/^'.URL_REGEX.'$/i', $s);
    });

    $Mirrors = array_unique($Mirrors);
    $Mirrors = array_slice($Mirrors, 0, 2);

    # Downgrade TLS on resource URIs
    # Required for BEP 19 compatibility
    $Mirrors = str_ireplace('tps://', 'tp://', $Mirrors);

    # Perform the DB inserts here
    # Screenshots (Publications)
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

    # Mirrors
    if (!empty($Mirrors)) {
        $Mirror = '';
        $DB->prepare_query("
          INSERT INTO torrents_mirrors
            (GroupID, UserID, Time, URI)
          VALUES (?, ?, NOW(), ?)", $GroupID, $LoggedUser['ID'], $Mirror);

        foreach ($Mirrors as $Mirror) {
            $DB->exec_prepared_query();
        }
    }

    # Main if/else
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
$T['FreeTorrent'] = '0';
$T['FreeLeechType'] = '0';

$DB->query("
  SELECT Name, First, Second
  FROM misc
  WHERE Second = 'freeleech'");

if ($DB->has_results()) {
    $FreeLeechTags = $DB->to_array('Name');
    foreach ($FreeLeechTags as $Tag => $Exp) {
        if ($Tag === 'global' || in_array($Tag, $Tags)) {
            $T['FreeTorrent'] = '1';
            $T['FreeLeechType'] = '3';
            break;
        }
    }
}

// Torrents over a size in bytes are neutral leech
// Download doesn't count, upload does
if (($TotalSize > 10737418240)) { # 10 GiB
    $T['FreeTorrent'] = '2';
    $T['FreeLeechType'] = '2';
}

// Torrent
$DB->query(
    "
  INSERT INTO torrents
    (GroupID, UserID, Media, Container, Codec, Resolution,
    Version, Censored,
    Anonymous, Archive, info_hash, FileCount, FileList, FilePath, Size, Time,
    Description, FreeTorrent, FreeLeechType)
  VALUES
    ( ?, ?, ?, ?, ?, ?,
      ?, ?,
      ?, ?, ?, ?, ?, ?, ?, NOW(),
      ?, ?, ? )",
    $GroupID,
    $LoggedUser['ID'],
    $T['Media'],
    $T['Container'],
    $T['Codec'],
    $T['Resolution'],
    $T['Version'],
    $T['Censored'],
    $T['Anonymous'],
    $T['Archive'],
    $InfoHash,
    $NumFiles,
    $FileString,
    $FilePath,
    $TotalSize,
    $T['TorrentDescription'],
    $T['FreeTorrent'],
    $T['FreeLeechType']
);

$TorrentID = $DB->inserted_id();
$Cache->increment('stats_torrent_count');
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

// Give BP if necessary
// todo: Repurpose this
if (($Type === "Movies" || $Type === "Anime") && ($T['Container'] === 'ISO' || $T['Container'] === 'M2TS' || $T['Container'] === 'VOB IFO')) {
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
if ($T['FreeLeechType'] === 3) {
    // Figure out which duration to use
    $Expiry = 0;

    foreach ($FreeLeechTags as $Tag => $Exp) {
        if ($Tag === 'global' || in_array($Tag, $Tags)) {
            if (((int) $FreeLeechTags[$Tag]['First']) > $Expiry) {
                $Expiry = (int) $FreeLeechTags[$Tag]['First'];
            }
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

if (trim($T['Image']) !== '') {
    $RecentUploads = $Cache->get_value("recent_uploads_$UserID");
    if (is_array($RecentUploads)) {
        do {
            foreach ($RecentUploads as $Item) {
                if ($Item['ID'] === $GroupID) {
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
    View::show_header('Warning'); ?>
<h1>Warning</h1>
<p>
    <strong>Your torrent has been uploaded but you must re-download your torrent file from
        <a
            href="torrents.php?id=<?=$GroupID?>&torrentid=<?=$TorrentID?>">here</a>
        because the site modified it to make it private.</strong>
</p>
<?php
  View::show_footer();
} elseif ($UnsourcedTorrent) {
    View::show_header('Warning'); ?>
<h1>Warning</h1>
<p>
    <strong>Your torrent has been uploaded but you must re-download your torrent file from
        <a
            href="torrents.php?id=<?=$GroupID?>&torrentid=<?=$TorrentID?>">here</a>
        because the site modified it to add a source flag.</strong>
</p>
<?php
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

# Category and title
$Announce .= '['.$T['CategoryName'].'] ';
$Announce .= substr(trim(empty($T['Title']) ? (empty($T['Title2']) ? $T['TitleJP'] : $T['Title2']) : $T['Title']), 0, 100);
$Announce .= ' ';

# Exploded for sanity
$Announce .= '[ '
    . Torrents::torrent_info(
        $Data = $T,
        $ShowMedia = true,
        $ShowEdition = false,
        $HTMLy = false
    )
    . ' ]';

# Twitter stuff
# Grab $Announce here
/*
$Tweet = $Announce;
$TweetTags =
implode(
    ' ',
    # Add hashtags
    preg_replace(
        '/^\s* /', # remove space in "* /"
        '#',
        # Trim raw explosion
        array_filter(
            explode(
                ',',
                # todo: Get validated output
                $T['TagList']
            ),
            'trim'
        )
    )
);
$Tweet .= $TweetTags;
*/

# Tags and link
$Announce .= ' - '.trim($T['TagList']);
$Announce .= ' - '.site_url()."torrents.php?id=$GroupID&torrentid=$TorrentID";
$Announce .= ' - '.site_url()."torrents.php?action=download&id=$TorrentID";

# Unused
#$Title = '['.$T['CategoryName'].'] '.$Announce;
#$Announce .= Artists::display_artists($ArtistForm, false);

// ENT_QUOTES is needed to decode single quotes/apostrophes
send_irc(ANNOUNCE_CHAN, html_entity_decode($Announce, ENT_QUOTES));
$Debug->set_flag('upload: announced on irc');

/**
 * Manage motifications
 */
// For RSS
$Item = $Feed->item(
    $Announce,
    Text::strip_bbcode($Body),
    'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id='.$TorrentID,
    $Properties['Anonymous'] ? 'Anonymous' : $LoggedUser['Username'],
    'torrents.php?id='.$GroupID,
    trim($T['TagList'])
);

// Notifications
$SQL = "
SELECT
  unf.`ID`,
  unf.`UserID`,
  `torrent_pass`
FROM
  `users_notify_filters` AS unf
JOIN `users_main` AS um
ON
  um.`ID` = unf.`UserID`
WHERE
  um.`Enabled` = '1'
";

# Creators
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
    /*
    if (count($ArtistNameList) > 2 || $Artist['name'] === 'Various Artists') {
        $SQL .= " AND (ExcludeVA = '0' AND (";
        $SQL .= implode(' OR ', array_merge($ArtistNameList, $GuestArtistNameList));
        $SQL .= " OR Artists = '')) AND (";
    } else {
    */

    $SQL .= " AND (";

    /*
    if (!empty($GuestArtistNameList)) {
        $SQL .= "(ExcludeVA = '0' AND (";
        $SQL .= implode(' OR ', $GuestArtistNameList);
        $SQL .= ')) OR ';
    }
    */

    if (count($ArtistNameList) > 0) {
        $SQL .= implode(' OR ', $ArtistNameList);
        $SQL .= " OR ";
    }
    $SQL .= "Artists = '') AND (";
#}
} else {
    $SQL .= "AND (Artists = '') AND (";
}

# Tags
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

/*
if ($T['ReleaseType']) {
    $SQL .= " AND (ReleaseTypes LIKE '%|".db_string(trim($ReleaseTypes[$T['ReleaseType']]))."|%' OR ReleaseTypes = '') ";
} else {
    $SQL .= " AND (ReleaseTypes = '') ";
}
*/

/*
  Notify based on the following:
    1. The torrent must match the formatbitrate filter on the notification
    2. If they set NewGroupsOnly to 1, it must also be the first torrent in the group to match the formatbitrate filter on the notification
*/

/*
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
*/

// Either they aren't using NewGroupsOnly
$SQL .= "AND ((NewGroupsOnly = '0' ";
// Or this is the first torrent in the group to match the formatbitrate filter
$SQL .= ") OR ( NewGroupsOnly = '1' ";
$SQL .= '))';

/*
if ($T['Year']) {
    $SQL .= " AND (('".db_string(trim($T['Year']))."' BETWEEN FromYear AND ToYear)
      OR (FromYear = 0 AND ToYear = 0)) ";
} else {
    $SQL .= " AND (FromYear = 0 AND ToYear = 0) ";
}
*/

$SQL .= " AND UserID != '".$LoggedUser['ID']."' ";

$DB->query("
SELECT
  `Paranoia`
FROM
  `users_main`
WHERE
  `ID` = $LoggedUser[ID]
");

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
      INSERT IGNORE INTO `users_notify_torrents` (`UserID`, `GroupID`, `TorrentID`, `FilterID`)
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
SELECT
  u.`ID`,
  u.`torrent_pass`
FROM
  `users_main` AS u
JOIN `bookmarks_torrents` AS b
ON
  b.`UserID` = u.`ID`
WHERE
  b.`GroupID` = '$GroupID'
");
  
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

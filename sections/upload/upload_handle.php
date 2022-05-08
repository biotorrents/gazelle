<?php
#declare(strict_types=1);

/**
 * Take upload
 *
 * This pages handles the backend of the torrent upload function.
 * It checks the data, and if it all validates, it builds the torrent file,
 * then writes the data to the database and the torrent to the disk.
 */

$ENV = ENV::go();
$debug = Debug::go();

$Feed = new Feed;
$Validate = new Validate;

require_once "$ENV->SERVER_ROOT/classes/feed.class.php";
require_once "$ENV->SERVER_ROOT/classes/validate.class.php";
require_once "$ENV->SERVER_ROOT/sections/torrents/functions.php";

enforce_login();
authorize();


/**
 * Set $Properties array
 *
 * This is used if the form doesn't validate,
 * and when the time comes to enter it into the database.
 *
 * todo: Do something about this mess
 */

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
$Properties['Seqhash'] = isset($_POST['seqhash']) ? $_POST['seqhash'] : '';

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


/**
 * Validate data in upload form
 */

# Submit button
$Validate->SetFields(
    'type',
    '1',
    'inarray',
    'Please select a valid type.',
    array('inarray' => array_keys($Categories))
);

# torrents_group.category_id
$Validate->SetFields(
    'type',
    '1',
    'inarray',
    'Please select a valid type.',
    array('inarray' => array_keys($Categories))
);

if (!$_POST['groupid']) {
    # torrents_group.identifier
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
        
    # torrents_group.title
    $Validate->SetFields(
        'title',
        '1',
        'string',
        'Torrent Title must be between 10 and 255 characters.',
        array('maxlength' => 255, 'minlength' => 10)
    );

    # torrents_group.subject
    $Validate->SetFields(
        'title_rj',
        '0',
        'string',
        'Organism must be between 0 and 255 characters.',
        array('maxlength' => 255, 'minlength' => 0)
    );

    # torrents_group.object
    $Validate->SetFields(
        'title_jp',
        '0',
        'string',
        'Strain/Variety must be between 0 and 255 characters.',
        array('maxlength' => 255, 'minlength' => 0)
    );

    # torrents_group.workgroup
    $Validate->SetFields(
        'studio',
        '1',
        'string',
        'Department/Lab must be between 0 and 100 characters.',
        array('maxlength' => 100, 'minlength' => 0)
    );

    # torrents_group.location
    $Validate->SetFields(
        'series',
        '0',
        'string',
        'Location must be between 0 and 100 characters.',
        array('maxlength' => 100, 'minlength' => 0)
    );

    /* todo: Fix the year validation
    # torrents_group.year
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
        array('inarray' => $ENV->META->Platforms)
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
        
    # torrents_group.tag_list
    $Validate->SetFields(
        'tags',
        '1',
        'string',
        'You must enter at least five tags. Maximum length is 500 characters.',
        array('maxlength' => 500, 'minlength' => 10)
    );

    # torrents_group.picture
    $Validate->SetFields(
        'image',
        '0',
        'link',
        'The image URL you entered was invalid.',
        array('maxlength' => 255, 'minlength' => 10) # x.yz/a.bc
    );
}

# torrents_group.description
$Validate->SetFields(
    'album_desc',
    '1',
    'string',
    'The description must be between 100 and 65535 characters.',
    array('maxlength' => 65535, 'minlength' => 100)
);

/* todo: Fix the Group ID validation
# torrents_group.id
$Validate->SetFields(
    'groupid',
    '0',
    'number',
    'Group ID was not numeric.'
);
*/

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
    $db->query("
      SELECT ta.ArtistID, ag.Name
      FROM torrents_artists AS ta
        JOIN artists_group AS ag ON ta.ArtistID = ag.ArtistID
      WHERE ta.GroupID = ?
      ORDER BY ag.Name ASC", $Properties['GroupID']);

    $ArtistForm = [];
    while (list($ArtistID, $ArtistName) = $db->next_record(MYSQLI_BOTH, false)) {
        array_push($ArtistForm, array('id' => $ArtistID, 'name' => esc($ArtistName)));
        array_push($ArtistsUnescaped, array('name' => $ArtistName));
    }
    $LogName .= Artists::display_artists($ArtistsUnescaped, false, true, false);
}

if ($Err) { // Show the upload form, with the data the user entered
    $UploadForm = $Type;
    require_once SERVER_ROOT.'/sections/upload/upload.php' ;
    error(400, $NoHTML = true);
}

ImageTools::blacklisted($Properties['Image']);


/**
 * Make variables ready for database input
 *
 * Prepared SQL statements do this for us,
 * so there is nothing to do here anymore.
 */
$T = $Properties;


/**
 * Generate torrent file
 */

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
$DirName = (isset($Tor->Dec['info']['files']) ? Text::utf8($Tor->get_name()) : '');
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
$debug['upload']->info('torrent decoded');

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
        array_keys($ENV->META->Formats->Archives),

        # $FileTypes
        array_merge($ENV->META->Formats->Archives),
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
    $db->prepared_query("
    SELECT
      `id`,
      `picture`,
      `description`,
      `revision_id`,
      `title`,
      `year`,
      `tag_list`
    FROM
      `torrents_group`
    WHERE
      `id` = ?
    ", $T['GroupID']);


    if ($db->has_results()) {
        // Don't escape tg.title. It's written directly to the log table
        list($GroupID, $WikiImage, $WikiBody, $RevisionID, $T['Title'], $T['Year'], $T['TagList']) = $db->next_record(MYSQLI_NUM, array(4));
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
        $db->query("
          SELECT
            ArtistID,
            Name
          FROM artists_group
            WHERE Name = ?", $Artist['name']);

        if ($db->has_results()) {
            while (list($ArtistID, $Name) = $db->next_record(MYSQLI_NUM, false)) {
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
                $db->query("
                  INSERT INTO artists_group (Name)
                  VALUES ( ? )", $Artist['name']);

                $ArtistID = $db->inserted_id();
                $cache->increment('stats_artist_count');

                $ArtistForm[$Num] = array('id' => $ArtistID, 'name' => $Artist['name']);
                $ArtistsAdded[strtolower($Artist['name'])] = $ArtistForm[$Num];
            }
        }
    }
    unset($ArtistsAdded);
}

if (!isset($GroupID) || !$GroupID) {
    // Create torrent group
    $db->query(
        "
      INSERT INTO torrents_group
        (`category_id`, `title`, `subject`, `object`, `year`,
        `location`, `workgroup`, `identifier`, `timestamp`,
        `description`, `picture`)
      VALUES
        ( ?, ?, ?, ?, ?,
          ?, ?, ?, NOW(),
          ?, ? )",
        $T['CategoryID'],
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

    $GroupID = $db->inserted_id();
    foreach ($ArtistForm as $Num => $Artist) {
        $db->query("
          INSERT IGNORE INTO torrents_artists (GroupID, ArtistID, UserID)
          VALUES ( ?, ?, ? )", $GroupID, $Artist['id'], $user['ID']);

        $cache->increment('stats_album_count');
        $cache->delete_value('artist_groups_'.$Artist['id']);
    }
    $cache->increment('stats_group_count');


    /**
     * DOI numbers
     *
     * Add optional citation info.
     * todo: Query Semantic Scholar in the scheduler.
     * THESE ARE ASSOCIATED WITH TORRENT GROUPS.s
     */
    if (!empty($T['Screenshots'])) {
        $Screenshots = $Validate->textarea2array($T['Screenshots'], $ENV->DOI_REGEX);
        $Screenshots = array_slice($Screenshots, 0, 10);

        foreach ($Screenshots as $Screenshot) {
            $db->query("
            INSERT INTO `literature`
            (`group_id`, `user_id`, `timestamp`, `doi`)
          VALUES (?, ?, NOW(), ?)", $GroupID, $user['ID'], $Screenshot);
        }
    }
}

# Main if/else
else {
    $db->query("
      UPDATE torrents_group
      SET `timestamp` = NOW()
        WHERE `id` = ?", $GroupID);

    $cache->delete_value("torrent_group_$GroupID");
    $cache->delete_value("torrents_details_$GroupID");
    $cache->delete_value("detail_files_$GroupID");
}

// Description
if (!isset($NoRevision) || !$NoRevision) {
    $db->query("
      INSERT INTO wiki_torrents
        (PageID, Body, UserID, Summary, Time, Image)
      VALUES
        ( ?, ?, ?, 'Uploaded new torrent', NOW(), ? )", $GroupID, $T['GroupDescription'], $user['ID'], $T['Image']);
    $RevisionID = $db->inserted_id();

    // Revision ID
    $db->prepared_query("
    UPDATE
      `torrents_group`
    SET
      `revision_id` = '$RevisionID'
    WHERE
      `id` = '$GroupID'
    ");
}

// Tags
$Tags = explode(',', $T['TagList']);
if (!$T['GroupID']) {
    foreach ($Tags as $Tag) {
        $Tag = Misc::sanitize_tag($Tag);
        if (!empty($Tag)) {
            $Tag = Misc::get_alias_tag($Tag);
            $db->query("
            INSERT INTO tags
              (Name, UserID)
            VALUES
              ( ?, ? )
            ON DUPLICATE KEY UPDATE
              Uses = Uses + 1;", $Tag, $user['ID']);
            $TagID = $db->inserted_id();

            $db->query("
            INSERT INTO torrents_tags
              (TagID, GroupID, UserID)
            VALUES
              ( ?, ?, ? )
            ON DUPLICATE KEY UPDATE TagID=TagID", $TagID, $GroupID, $user['ID']);
        }
    }
}

// Use this section to control freeleeches
$T['FreeTorrent'] = '0';
$T['FreeLeechType'] = '0';

$db->query("
  SELECT Name, First, Second
  FROM misc
  WHERE Second = 'freeleech'");

if ($db->has_results()) {
    $FreeLeechTags = $db->to_array('Name');
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
$db->query(
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
    $user['ID'],
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

$TorrentID = $db->inserted_id();
$cache->increment('stats_torrent_count');
$Tor->Dec['comment'] = 'https://'.SITE_DOMAIN.'/torrents.php?torrentid='.$TorrentID;


/**
 * Mirrors
 *
 * Add optional web seeds and IPFS/Dat mirrors.
 * Support an arbitrary and limited number of sources.
 * THESE ARE ASSOCIATED WITH INDIVIDUAL TORRENTS.
 */

if (!empty($T['Mirrors'])) {
    $Mirrors = $Validate->textarea2array($T['Mirrors'], $ENV->URL_REGEX);
    $Screenshots = array_slice($Screenshots, 0, 5);

    foreach ($Mirrors as $Mirror) {
        $db->query(
            "
        INSERT INTO `torrents_mirrors`
          (`torrent_id`, `user_id`, `timestamp`, `uri`)
        VALUES (?, ?, NOW(), ?)",
            $TorrentID,
            $user['ID'],
            $Mirror
        );
    }
}


/**
 * Seqhash
 *
 * Elementary Seqhash support
 */
if ($ENV->FEATURE_BIOPHP && !empty($T['Seqhash'])) {
    $BioIO = new \BioPHP\IO();
    $BioSeqhash = new \BioPHP\Seqhash();

    $Parsed = $BioIO->readFasta($T['Seqhash']);
    foreach ($Parsed as $Parsed) {
        try {
            # todo: Trim sequences in \BioPHP\Transform->normalize()
            $Trimmed = preg_replace('/\s+/', '', $Parsed['sequence']);
            $Seqhash = $BioSeqhash->hash(
                $Trimmed,
                $_POST['seqhash_meta1'],
                $_POST['seqhash_meta2'],
                $_POST['seqhash_meta3']
            );

            $db->query(
                "
            INSERT INTO `bioinformatics`
              (`torrent_id`, `user_id`, `timestamp`,
               `name`, `seqhash`)
            VALUES (?, ?, NOW(), ?, ?)",
                $TorrentID,
                $user['ID'],
                $Parsed['name'],
                $Seqhash
            );
        } catch (Exception $Err) {
            $UploadForm = $Type;
            require_once SERVER_ROOT.'/sections/upload/upload.php' ;
            error($Err->getMessage(), $NoHTML = true);
        }
    }
}


/**
 * Update tracker
 */
Tracker::update_tracker('add_torrent', [
  'id'          => $TorrentID,
  'info_hash'   => rawurlencode($InfoHash),
  'freetorrent' => $T['FreeTorrent']
]);
$debug['upload']->info('ocelot updated');

// Prevent deletion of this torrent until the rest of the upload process is done
// (expire the key after 10 minutes to prevent locking it for too long in case there's a fatal error below)
$cache->cache_value("torrent_{$TorrentID}_lock", true, 600);

// Give BP if necessary
// todo: Repurpose this
if (($Type === "Movies" || $Type === "Anime") && ($T['Container'] === 'ISO' || $T['Container'] === 'M2TS' || $T['Container'] === 'VOB IFO')) {
    $BPAmt = (int) 2*($TotalSize / (1024*1024*1024))*1000;

    $db->query("
      UPDATE users_main
      SET BonusPoints = BonusPoints + ?
        WHERE ID = ?", $BPAmt, $user['ID']);

    $db->query("
      UPDATE users_info
      SET AdminComment = CONCAT(NOW(), ' - Received $BPAmt ".BONUS_POINTS." for uploading a torrent $TorrentID\n\n', AdminComment)
        WHERE UserID = ?", $user['ID']);

    $cache->delete_value('user_info_heavy_'.$user['ID']);
    $cache->delete_value('user_stats_'.$user['ID']);
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
        $db->query("
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

$FileName = "$ENV->TORRENT_STORE/$TorrentID.torrent";
file_put_contents($FileName, $Tor->encode());
chmod($FileName, 0400);

Misc::write_log("Torrent $TorrentID ($LogName) (".Text::float($TotalSize / (1024 * 1024), 2).' MB) was uploaded by ' . $user['Username']);
Torrents::write_group_log($GroupID, $TorrentID, $user['ID'], 'uploaded ('.Text::float($TotalSize / (1024 * 1024), 2).' MB)', 0);

Torrents::update_hash($GroupID);
$debug['upload']->info('sphinx updated');

//******************************************************************************//
//---------------------- Recent Uploads ----------------------------------------//

if (trim($T['Image']) !== '') {
    $RecentUploads = $cache->get_value("recent_uploads_$UserID");
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
            $cache->cache_value("recent_uploads_$UserID", $RecentUploads, 0);
        } while (0);
    }
}


/**
 * Post-processing
 *
 * Because tracker updates and notifications can be slow, we're redirecting the user to the destination page
 * and flushing the buffers to make it seem like the PHP process is working in the background.
 */

if ($PublicTorrent) {
    View::header('Warning'); ?>
<h1>Warning</h1>
<p>
    <strong>Your torrent has been uploaded but you must re-download your torrent file from
        <a
            href="torrents.php?id=<?=$GroupID?>&torrentid=<?=$TorrentID?>">here</a>
        because the site modified it to make it private.</strong>
</p>
<?php
  View::footer();
} elseif ($UnsourcedTorrent) {
    View::header('Warning'); ?>
<h1>Warning</h1>
<p>
    <strong>Your torrent has been uploaded but you must re-download your torrent file from
        <a
            href="torrents.php?id=<?=$GroupID?>&torrentid=<?=$TorrentID?>">here</a>
        because the site modified it to add a source flag.</strong>
</p>
<?php
  View::footer();
} elseif ($RequestID) {
    header("Location: requests.php?action=takefill&requestid=$RequestID&torrentid=$TorrentID&auth=".$user['AuthKey']);
} else {
    Http::redirect("torrents.php?id=$GroupID&torrentid=$TorrentID");
}

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    ignore_user_abort(true);
    ob_flush();
    flush();
    ob_start(); // So we don't keep sending data to the client
}


/**
 * IRC announce and feeds
 */

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
$debug['upload']->info('announced on irc');

/**
 * Manage motifications
 */
// For RSS
$Item = $Feed->item(
    $Announce,
    $Body,
    'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id='.$TorrentID,
    $Properties['Anonymous'] ? 'Anonymous' : $user['Username'],
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

    $SQL .= " AND (";

    if (count($ArtistNameList) > 0) {
        $SQL .= implode(' OR ', $ArtistNameList);
        $SQL .= " OR ";
    }
    $SQL .= "Artists = '') AND (";
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
  Notify based on the following:
    1. The torrent must match the formatbitrate filter on the notification
    2. If they set NewGroupsOnly to 1, it must also be the first torrent in the group to match the formatbitrate filter on the notification
*/

if ($T['Format']) {
    $SQL .= " AND (Formats LIKE '%|".db_string(trim($T['Format']))."|%' OR Formats = '') ";
} else {
    $SQL .= " AND (Formats = '') ";
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

$SQL .= " AND UserID != '".$user['ID']."' ";

$db->query("
SELECT
  `Paranoia`
FROM
  `users_main`
WHERE
  `ID` = $user[ID]
");

list($Paranoia) = $db->next_record();
$Paranoia = unserialize($Paranoia);

if (!is_array($Paranoia)) {
    $Paranoia = [];
}

if (!in_array('notifications', $Paranoia)) {
    $SQL .= " AND (Users LIKE '%|".$user['ID']."|%' OR Users = '') ";
}

$SQL .= " AND UserID != '".$user['ID']."' ";
$db->query($SQL);
$debug['upload']->info('notification query finished');

if ($db->has_results()) {
    $UserArray = $db->to_array('UserID');
    $FilterArray = $db->to_array('ID');

    $InsertSQL = '
      INSERT IGNORE INTO `users_notify_torrents` (`UserID`, `GroupID`, `TorrentID`, `FilterID`)
      VALUES ';

    $Rows = [];
    foreach ($UserArray as $User) {
        list($FilterID, $UserID, $Passkey) = $User;
        $Rows[] = "('$UserID', '$GroupID', '$TorrentID', '$FilterID')";
        $Feed->populate("torrents_notify_$Passkey", $Item);
        $cache->delete_value("notifications_new_$UserID");
    }

    $InsertSQL .= implode(',', $Rows);
    $db->query($InsertSQL);
    $debug['upload']->info('notification inserts finished');

    foreach ($FilterArray as $Filter) {
        list($FilterID, $UserID, $Passkey) = $Filter;
        $Feed->populate("torrents_notify_{$FilterID}_$Passkey", $Item);
    }
}

// RSS for bookmarks
$db->query("
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
  
while (list($UserID, $Passkey) = $db->next_record()) {
    $Feed->populate("torrents_bookmarks_t_$Passkey", $Item);
}

$Feed->populate('torrents_all', $Item);
$Feed->populate('torrents_'.strtolower($Type), $Item);
$debug['upload']->info('notifications handled');

# Clear cache
$cache->delete_value("torrents_details_$GroupID");
$cache->delete_value("contest_scores");

# Allow deletion of this torrent now
$cache->delete_value("torrent_{$TorrentID}_lock");

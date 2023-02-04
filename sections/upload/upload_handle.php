<?php

declare(strict_types=1);


/**
 * upload form handling
 *
 * this should remain as its own dedicated script
 * it does a lot of stuff in a specific order:
 *
 * 1. checks csrf, collects form data, validates it
 * 2. formats the data to be suitable for the database
 * 3. writes all this metadata to the database
 * 4. does some special stuff with custom fields
 * 5. writes a proper (private) .torrent file to disk
 * 6. does some cachery to populate recent uploads
 * 7. sings from the rooftops, announces on channels
 */


$app = App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

authorize();
enforce_login();

# request vars
$post = Http::query("post");
$files = Http::query("files");

# gazelle libraries
$feed = new Feed();
$validate = new Validate();


/**
 * collect the form data
 *
 * Http::query automagically escapes all this as strings
 * also, we're a good boi and we use parameterized queries
 * thank god for null coalescing, it cleans this up a lot
 */

$data = [];

# basic info
$data["categoryId"] = $post["categoryId"] ?? null;
$data["torrentFile"] = $files["torrentFile"] ?? null;

# torrent group
$data["creatorList"] = $post["creatorList"] ?? null;
$data["groupDescription"] = $post["groupDescription"] ?? null;
$data["identifier"] = $post["identifier"] ?? null;
$data["literature"] = $post["literature"] ?? null;
$data["location"] = $post["location"] ?? null;
$data["object"] = $post["object"] ?? null;
$data["picture"] = $post["picture"] ?? null;
$data["subject"] = $post["subject"] ?? null;
$data["tagList"] = $post["tagList"] ?? null;
$data["title"] = $post["title"] ?? null;
$data["version"] = $post["version"] ?? null;
$data["workgroup"] = $post["workgroup"] ?? null;
$data["year"] = $post["year"] ?? null;

# single torrent
$data["annotated"] = $post["annotated"] ?? null;
$data["anonymous"] = $post["anonymous"] ?? null;
$data["archive"] = $post["archive"] ?? null;
$data["format"] = $post["format"] ?? null;
$data["license"] = $post["license"] ?? null;
$data["mirrors"] = $post["mirrors"] ?? null;
$data["platform"] = $post["platform"] ?? null;
$data["scope"] = $post["scope"] ?? null;
$data["torrentDescription"] = $post["torrentDescription"] ?? null;

# seqhash
$data["seqhashAlphabet"] = $post["seqhashAlphabet"] ?? null;
$data["seqhashHelix"] = $post["seqhashHelix"] ?? null;
$data["seqhashSequence"] = $post["seqhashSequence"] ?? null;
$data["seqhashShape"] = $post["seqhashShape"] ?? null;

# freeleech
$data["freeleechReason"] = $post["freeleechReason"] ?? null;
$data["freeleechType"] = $post["freeleechType"] ?? null;

# hidden fields
$data["groupId"] = $post["groupId"] ?? null;
$data["requestId"] = $post["requestId"] ?? null;
$data["torrentId"] = $post["torrentId"] ?? null;

# get creators (unsure if needed)
if ($data["groupId"]) {
    $data["creatorList"] = Artists::get_artist($data["groupId"]);
}


/**
 * validate the form data
 */

# categoryId
if ($data["categoryId"]) {
    $validate->setField("categoryId", [
        "errorMessage" => "Please select a valid category",
        "inArray" => $app->env->CATS->array_keys(),
        "maxLength" => $data["maxLength"] ?? null,
        "minLength" => $data["minLength"] ?? null,
        "regex" => $data["regex"] ?? null,
        "required" => true,
        "type" => $data["type"] ?? null,
    ]);
}

/** TODO: START HERE */



/**
 * Validate data in upload form
 */

# Submit button
$validate->SetFields(
    'type',
    '1',
    'inarray',
    'Please select a valid type.',
    array('inarray' => array_keys($Categories))
);

# torrents_group.category_id
$validate->SetFields(
    'type',
    '1',
    'inarray',
    'Please select a valid type.',
    array('inarray' => array_keys($Categories))
);

if (!$_POST['groupid']) {
    # torrents_group.identifier
    $validate->SetFields(
        'catalogue',
        '0',
        'string',
        'Accession Number must be between 0 and 50 characters.',
        array('maxlength' => 50, 'minlength' => 0)
    );

    # torrents.Version
    $validate->SetFields(
        'version',
        '0',
        'string',
        'Version must be between 0 and 10 characters.',
        array('maxlength' => 10, 'minlength' => 0)
    );

    # torrents_group.title
    $validate->SetFields(
        'title',
        '1',
        'string',
        'Torrent Title must be between 10 and 255 characters.',
        array('maxlength' => 255, 'minlength' => 10)
    );

    # torrents_group.subject
    $validate->SetFields(
        'title_rj',
        '0',
        'string',
        'Organism must be between 0 and 255 characters.',
        array('maxlength' => 255, 'minlength' => 0)
    );

    # torrents_group.object
    $validate->SetFields(
        'title_jp',
        '0',
        'string',
        'Strain/Variety must be between 0 and 255 characters.',
        array('maxlength' => 255, 'minlength' => 0)
    );

    # torrents_group.workgroup
    $validate->SetFields(
        'studio',
        '1',
        'string',
        'Department/Lab must be between 0 and 100 characters.',
        array('maxlength' => 100, 'minlength' => 0)
    );

    # torrents_group.location
    $validate->SetFields(
        'series',
        '0',
        'string',
        'Location must be between 0 and 100 characters.',
        array('maxlength' => 100, 'minlength' => 0)
    );

    /* todo: Fix the year validation
    # torrents_group.year
    $validate->SetFields(
        'year',
        '1',
        'number',
        'The year of the original release must be entered.',
        array('maxlength' => 4, 'minlength' => 4)
    );
    */

    # torrents.Media
    $validate->SetFields(
        'media',
        '1',
        'inarray',
        'Please select a valid platform.',
        array('inarray' => $app->env->META->Platforms)
    );

    /*
    # torrents.Container
    $validate->SetFields(
        'container',
        '1',
        'inarray',
        'Please select a valid format.',
        array('inarray' => array_merge($Containers, $ContainersGames))
    );
    */

    # torrents.Resolution
    $validate->SetFields(
        'resolution',
        '1',
        'string',
        'Scope must be between 4 and 20 characters.',
        array('maxlength' => 20, 'minlength' => 4)
    );

    # torrents_group.tag_list
    $validate->SetFields(
        'tags',
        '1',
        'string',
        'You must enter at least five tags. Maximum length is 500 characters.',
        array('maxlength' => 500, 'minlength' => 10)
    );

    # torrents_group.picture
    $validate->SetFields(
        'image',
        '0',
        'link',
        'The image URL you entered was invalid.',
        array('maxlength' => 255, 'minlength' => 10) # x.yz/a.bc
    );
}

# torrents_group.description
$validate->SetFields(
    'album_desc',
    '1',
    'string',
    'The description must be between 100 and 65535 characters.',
    array('maxlength' => 65535, 'minlength' => 100)
);

/* todo: Fix the Group ID validation
# torrents_group.id
$validate->SetFields(
    'groupid',
    '0',
    'number',
    'Group ID was not numeric.'
);
*/

$Err = $validate->ValidateForm($_POST); // Validate the form

# todo: Move all this validation code to the Validate class
if (count(explode(',', $data['TagList'])) < 5) {
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
if (empty($data['GroupID']) && empty($ArtistForm)) {
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
    $app->dbOld->query("
      SELECT ta.ArtistID, ag.Name
      FROM torrents_artists AS ta
        JOIN artists_group AS ag ON ta.ArtistID = ag.ArtistID
      WHERE ta.GroupID = ?
      ORDER BY ag.Name ASC", $data['GroupID']);

    $ArtistForm = [];
    while (list($ArtistID, $ArtistName) = $app->dbOld->next_record(MYSQLI_BOTH, false)) {
        array_push($ArtistForm, array('id' => $ArtistID, 'name' => Text::esc($ArtistName)));
        array_push($ArtistsUnescaped, array('name' => $ArtistName));
    }
    $LogName .= Artists::display_artists($ArtistsUnescaped, false, true, false);
}

if ($Err) { // Show the upload form, with the data the user entered
    $UploadForm = $Type;
    require_once serverRoot.'/sections/upload/upload.php' ;
    error(400, $NoHTML = true);
}

ImageTools::blacklisted($data['Image']);


/**
 * Make variables ready for database input
 *
 * Prepared SQL statements do this for us,
 * so there is nothing to do here anymore.
 */
$T = $data;


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
$app->debug['upload']->info('torrent decoded');

if (!empty($Err)) { // Show the upload form, with the data the user entered
    $UploadForm = $Type;
    include(serverRoot.'/sections/upload/upload.php');
    error();
}

//******************************************************************************//
//--------------- Autofill format and archive ----------------------------------//

if ($T['Container'] === 'Autofill') {
    # torrents.Container
    $T['Container'] = $validate->ParseExtensions(
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
    $T['Archive'] = $validate->ParseExtensions(
        # $FileList
        $Tor->file_list(),

        # $Category
        array_keys($app->env->META->Formats->Archives),

        # $FileTypes
        array_merge($app->env->META->Formats->Archives),
    );
}

//******************************************************************************//
//--------------- Start database stuff -----------------------------------------//

$Body = $T['GroupDescription'];

// Trickery
if (!preg_match($app->env->regexImage, $T['Image'])) {
    $T['Image'] = '';
}

// Does it belong in a group?
if ($T['GroupID']) {
    $app->dbOld->prepared_query("
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


    if ($app->dbOld->has_results()) {
        // Don't escape tg.title. It's written directly to the log table
        list($GroupID, $WikiImage, $WikiBody, $RevisionID, $T['Title'], $T['Year'], $T['TagList']) = $app->dbOld->next_record(MYSQLI_NUM, array(4));
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
        $app->dbOld->query("
          SELECT
            ArtistID,
            Name
          FROM artists_group
            WHERE Name = ?", $Artist['name']);

        if ($app->dbOld->has_results()) {
            while (list($ArtistID, $Name) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
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
                $app->dbOld->query("
                  INSERT INTO artists_group (Name)
                  VALUES ( ? )", $Artist['name']);

                $ArtistID = $app->dbOld->inserted_id();
                $app->cacheOld->increment('stats_artist_count');

                $ArtistForm[$Num] = array('id' => $ArtistID, 'name' => $Artist['name']);
                $ArtistsAdded[strtolower($Artist['name'])] = $ArtistForm[$Num];
            }
        }
    }
    unset($ArtistsAdded);
}

if (!isset($GroupID) || !$GroupID) {
    // Create torrent group
    $app->dbOld->query(
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

    $GroupID = $app->dbOld->inserted_id();
    foreach ($ArtistForm as $Num => $Artist) {
        $app->dbOld->query("
          INSERT IGNORE INTO torrents_artists (GroupID, ArtistID, UserID)
          VALUES ( ?, ?, ? )", $GroupID, $Artist['id'], $app->userNew->core['id']);

        $app->cacheOld->increment('stats_album_count');
        $app->cacheOld->delete_value('artist_groups_'.$Artist['id']);
    }
    $app->cacheOld->increment('stats_group_count');


    /**
     * DOI numbers
     *
     * Add optional citation info.
     * todo: Query Semantic Scholar in the scheduler.
     * THESE ARE ASSOCIATED WITH TORRENT GROUPS.s
     */
    if (!empty($T['Screenshots'])) {
        $Screenshots = $validate->textarea2array($T['Screenshots'], $app->env->regexDoi);
        $Screenshots = array_slice($Screenshots, 0, 10);

        foreach ($Screenshots as $Screenshot) {
            $app->dbOld->query("
            INSERT INTO `literature`
            (`group_id`, `user_id`, `timestamp`, `doi`)
          VALUES (?, ?, NOW(), ?)", $GroupID, $app->userNew->core['id'], $Screenshot);
        }
    }
}

# Main if/else
else {
    $app->dbOld->query("
      UPDATE torrents_group
      SET `timestamp` = NOW()
        WHERE `id` = ?", $GroupID);

    $app->cacheOld->delete_value("torrent_group_$GroupID");
    $app->cacheOld->delete_value("torrents_details_$GroupID");
    $app->cacheOld->delete_value("detail_files_$GroupID");
}

// Description
if (!isset($NoRevision) || !$NoRevision) {
    $app->dbOld->query("
      INSERT INTO wiki_torrents
        (PageID, Body, UserID, Summary, Time, Image)
      VALUES
        ( ?, ?, ?, 'Uploaded new torrent', NOW(), ? )", $GroupID, $T['GroupDescription'], $app->userNew->core['id'], $T['Image']);
    $RevisionID = $app->dbOld->inserted_id();

    // Revision ID
    $app->dbOld->prepared_query("
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
            $app->dbOld->query("
            INSERT INTO tags
              (Name, UserID)
            VALUES
              ( ?, ? )
            ON DUPLICATE KEY UPDATE
              Uses = Uses + 1;", $Tag, $app->userNew->core['id']);
            $TagID = $app->dbOld->inserted_id();

            $app->dbOld->query("
            INSERT INTO torrents_tags
              (TagID, GroupID, UserID)
            VALUES
              ( ?, ?, ? )
            ON DUPLICATE KEY UPDATE TagID=TagID", $TagID, $GroupID, $app->userNew->core['id']);
        }
    }
}

// Use this section to control freeleeches
$T['FreeTorrent'] = '0';
$T['FreeLeechType'] = '0';

$app->dbOld->query("
  SELECT Name, First, Second
  FROM misc
  WHERE Second = 'freeleech'");

if ($app->dbOld->has_results()) {
    $FreeLeechTags = $app->dbOld->to_array('Name');
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
$app->dbOld->query(
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
    $app->userNew->core['id'],
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

$TorrentID = $app->dbOld->inserted_id();
$app->cacheOld->increment('stats_torrent_count');
$Tor->Dec['comment'] = 'https://'.siteDomain.'/torrents.php?torrentid='.$TorrentID;


/**
 * Mirrors
 *
 * Add optional web seeds and IPFS/Dat mirrors.
 * Support an arbitrary and limited number of sources.
 * THESE ARE ASSOCIATED WITH INDIVIDUAL TORRENTS.
 */

if (!empty($T['Mirrors'])) {
    $Mirrors = $validate->textarea2array($T['Mirrors'], $app->env->regexUri);
    $Screenshots = array_slice($Screenshots, 0, 5);

    foreach ($Mirrors as $Mirror) {
        $app->dbOld->query(
            "
        INSERT INTO `torrents_mirrors`
          (`torrent_id`, `user_id`, `timestamp`, `uri`)
        VALUES (?, ?, NOW(), ?)",
            $TorrentID,
            $app->userNew->core['id'],
            $Mirror
        );
    }
}


/**
 * Seqhash
 *
 * Elementary Seqhash support
 */
if ($app->env->FEATURE_BIOPHP && !empty($T['Seqhash'])) {
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

            $app->dbOld->query(
                "
            INSERT INTO `bioinformatics`
              (`torrent_id`, `user_id`, `timestamp`,
               `name`, `seqhash`)
            VALUES (?, ?, NOW(), ?, ?)",
                $TorrentID,
                $app->userNew->core['id'],
                $Parsed['name'],
                $Seqhash
            );
        } catch (Exception $Err) {
            $UploadForm = $Type;
            require_once serverRoot.'/sections/upload/upload.php' ;
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
$app->debug['upload']->info('ocelot updated');

// Prevent deletion of this torrent until the rest of the upload process is done
// (expire the key after 10 minutes to prevent locking it for too long in case there's a fatal error below)
$app->cacheOld->cache_value("torrent_{$TorrentID}_lock", true, 600);

// Give BP if necessary
// todo: Repurpose this
if (($Type === "Movies" || $Type === "Anime") && ($T['Container'] === 'ISO' || $T['Container'] === 'M2TS' || $T['Container'] === 'VOB IFO')) {
    $BPAmt = (int) 2*($TotalSize / (1024*1024*1024))*1000;

    $app->dbOld->query("
      UPDATE users_main
      SET BonusPoints = BonusPoints + ?
        WHERE ID = ?", $BPAmt, $app->userNew->core['id']);

    $app->dbOld->query("
      UPDATE users_info
      SET AdminComment = CONCAT(NOW(), ' - Received $BPAmt ".bonusPoints." for uploading a torrent $TorrentID\n\n', AdminComment)
        WHERE UserID = ?", $app->userNew->core['id']);

    $app->cacheOld->delete_value('user_info_heavy_'.$app->userNew->core['id']);
    $app->cacheOld->delete_value('user_stats_'.$app->userNew->core['id']);
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
        $app->dbOld->query("
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

$FileName = "$app->env->torrentStore/$TorrentID.torrent";
file_put_contents($FileName, $Tor->encode());
chmod($FileName, 0400);

Misc::write_log("Torrent $TorrentID ($LogName) (".Text::float($TotalSize / (1024 * 1024), 2).' MB) was uploaded by ' . $app->userNew->core['username']);
Torrents::write_group_log($GroupID, $TorrentID, $app->userNew->core['id'], 'uploaded ('.Text::float($TotalSize / (1024 * 1024), 2).' MB)', 0);

Torrents::update_hash($GroupID);
$app->debug['upload']->info('sphinx updated');

//******************************************************************************//
//---------------------- Recent Uploads ----------------------------------------//

if (trim($T['Image']) !== '') {
    $RecentUploads = $app->cacheOld->get_value("recent_uploads_$UserID");
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
            $app->cacheOld->cache_value("recent_uploads_$UserID", $RecentUploads, 0);
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
    header("Location: requests.php?action=takefill&requestid=$RequestID&torrentid=$TorrentID&auth=".$app->userNew->extra['AuthKey']);
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
$app->debug['upload']->info('announced on irc');

/**
 * Manage motifications
 */
// For RSS
$Item = $feed->item(
    $Announce,
    $Body,
    'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id='.$TorrentID,
    $data['Anonymous'] ? 'Anonymous' : $app->userNew->core['username'],
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

$SQL .= " AND UserID != '".$app->userNew->core['id']."' ";

$app->dbOld->query("
SELECT
  `Paranoia`
FROM
  `users_main`
WHERE
  `ID` = {$app->userNew->core['id']}
");

list($Paranoia) = $app->dbOld->next_record();
$Paranoia = unserialize($Paranoia);

if (!is_array($Paranoia)) {
    $Paranoia = [];
}

if (!in_array('notifications', $Paranoia)) {
    $SQL .= " AND (Users LIKE '%|".$app->userNew->core['id']."|%' OR Users = '') ";
}

$SQL .= " AND UserID != '".$app->userNew->core['id']."' ";
$app->dbOld->query($SQL);
$app->debug['upload']->info('notification query finished');

if ($app->dbOld->has_results()) {
    $UserArray = $app->dbOld->to_array('UserID');
    $FilterArray = $app->dbOld->to_array('ID');

    $InsertSQL = '
      INSERT IGNORE INTO `users_notify_torrents` (`UserID`, `GroupID`, `TorrentID`, `FilterID`)
      VALUES ';

    $Rows = [];
    foreach ($UserArray as $User) {
        list($FilterID, $UserID, $Passkey) = $User;
        $Rows[] = "('$UserID', '$GroupID', '$TorrentID', '$FilterID')";
        $feed->populate("torrents_notify_$Passkey", $Item);
        $app->cacheOld->delete_value("notifications_new_$UserID");
    }

    $InsertSQL .= implode(',', $Rows);
    $app->dbOld->query($InsertSQL);
    $app->debug['upload']->info('notification inserts finished');

    foreach ($FilterArray as $Filter) {
        list($FilterID, $UserID, $Passkey) = $Filter;
        $feed->populate("torrents_notify_{$FilterID}_$Passkey", $Item);
    }
}

// RSS for bookmarks
$app->dbOld->query("
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

while (list($UserID, $Passkey) = $app->dbOld->next_record()) {
    $feed->populate("torrents_bookmarks_t_$Passkey", $Item);
}

$feed->populate('torrents_all', $Item);
$feed->populate('torrents_'.strtolower($Type), $Item);
$app->debug['upload']->info('notifications handled');

# Clear cache
$app->cacheOld->delete_value("torrents_details_$GroupID");
$app->cacheOld->delete_value("contest_scores");

# Allow deletion of this torrent now
$app->cacheOld->delete_value("torrent_{$TorrentID}_lock");

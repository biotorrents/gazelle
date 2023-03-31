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

$app = \Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
#Http::csrf();

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
$data["categoryId"] = Esc::int($post["categoryId"] ?? null);
$data["torrentFile"] = $_FILES["torrentFile"] ?? null; # todo: make Http::query() recursive
#$data["torrentFile"] = $files["torrentFile"] ?? null;

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
$data["year"] = Esc::int($post["year"] ?? null);

# single torrent
$data["annotated"] = Esc::bool($post["annotated"] ?? null);
$data["anonymous"] = Esc::bool($post["anonymous"] ?? null);
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
$data["freeleechReason"] = Esc::int($post["freeleechReason"] ?? null);
$data["freeleechType"] = Esc::int($post["freeleechType"] ?? null);

# hidden fields
$data["groupId"] = Esc::int($post["groupId"] ?? null);
$data["requestId"] = Esc::int($post["requestId"] ?? null);
$data["torrentId"] = Esc::int($post["torrentId"] ?? null);

# get creators (unsure if needed)
if ($data["groupId"]) {
    $data["creatorList"] = Artists::get_artist($data["groupId"]);
}


/**
 * validate the form data
 */

# categoryId
$validate->setField("categoryId", [
    "inArray" => $app->env->CATS->array_keys(),
    "required" => true,
    "type" => "integer",
]);

# torrentFile
$validate->setField("torrentFile", [
    "required" => true,
    "type" => "torrentFile",
]);

/** */

# creatorList
$validate->setField("creatorList", [
    "maxLength" => 65535,
    "required" => true,
    "type" => "string",
]);

# groupDescription
$validate->setField("groupDescription", [
    "maxLength" => 65535,
    "required" => true,
    "type" => "string",
]);

# identifier
$validate->setField("identifier", [
    "maxLength" => 64,
    "type" => "string",
]);

# literature
$validate->setField("literature", [
    "maxLength" => 512,
    "type" => "literature",
]);

# location
$validate->setField("location", [
    "maxLength" => 128,
    "type" => "string",
]);

# object
$validate->setField("object", [
    "type" => "string",
]);

# picture
$validate->setField("picture", [
    "type" => "url",
]);

# subject
$validate->setField("subject", [
    "type" => "string",
]);

# tagList
$validate->setField("tagList", [
    "required" => true,
    "type" => "tagList",
]);

# title
$validate->setField("title", [
    "minLength" => 16,
    "required" => true,
    "type" => "string",
]);

# version
$validate->setField("version", [
    "maxLength" => 32,
    "type" => "string",
]);

# workgroup
$validate->setField("workgroup", [
    "maxLength" => 128,
    "required" => true,
    "type" => "string",
]);

# year
$validate->setField("year", [
    "required" => true,
    "type" => "year",
]);

/** */

# annotated
$validate->setField("annotated", [
    "type" => "boolean",
]);

# anonymous
$validate->setField("anonymous", [
    "type" => "boolean",
]);

# archive
$validate->setField("archive", [
    "inArray" => $app->env->META->Formats->Archives->array_keys(),
    "maxLength" => 32,
    "required" => true,
    "type" => "string",
]);

# format
$validate->setField("format", [
    "maxLength" => 32,
    "required" => true,
    "type" => "string",
]);

# license
$validate->setField("license", [
    "inArray" => $app->env->META->Licenses->toArray(),
    "maxLength" => 32,
    "required" => true,
    "type" => "string",
]);

# mirrors
$validate->setField("mirrors", [
    "maxLength" => 512,
    "type" => "mirrors",
]);

# platform
$validate->setField("platform", [
    #"inArray" => $app->env->META->Platforms->toArray(),
    "maxLength" => 32,
    "required" => true,
    "type" => "string",
]);

# scope
$validate->setField("scope", [
    #"inArray" => $app->env->META->Scopes->toArray(),
    "maxLength" => 32,
    "required" => true,
    "type" => "string",
]);

# torrentDescription
$validate->setField("torrentDescription", [
    "maxLength" => 65535,
    "type" => "string",
]);

/** */

# seqhashAlphabet
$validate->setField("seqhashAlphabet", [
    "inArray" => ["dna", "rna", "protein"],
    "type" => "string",
]);

# seqhashHelix
$validate->setField("seqhashHelix", [
    "inArray" => ["doubleStranded", "singleStranded"],
    "type" => "string",
]);

# seqhashSequence
$validate->setField("seqhashSequence", [
    "maxLength" => 65535,
    "type" => "string",
]);

# seqhashShape
$validate->setField("seqhashShape", [
    "inArray" => ["linear", "circular"],
    "type" => "string",
]);

/** */

# freeleechReason
$validate->setField("freeleechReason", [
    "type" => "integer",
]);

# freeleechType
$validate->setField("freeleechType", [
    "type" => "integer",
]);

/** */

# groupId
$validate->setField("groupId", [
    "type" => "integer",
]);

# requestId
$validate->setField("requestId", [
    "type" => "integer",
]);

# torrentId
$validate->setField("torrentId", [
    "type" => "integer",
]);

/** */

# validate the whole form
$validate->allFields($data);
#!d($validate->errors);exit;

# image trickery
ImageTools::blacklisted($data["picture"]);
if (!preg_match("/{$app->env->regexImage}/i", $data["picture"])) {
    $data["picture"] = null;
}


/**
 * torrent file validation
 */

# this is our torrent file
$torrentFile = $_FILES["torrentFile"] ??= null;
$torrentFile["tmp_name"] ??= null;

# torrent bencode
try {
    /*
    # todo: upgrade to orpheus libraries
    $bencode = new OrpheusNET\BencodeTorrent\Bencode();
    $bencode->decodeFile($torrentFile["tmp_name"]);
    */

    $torrent = new BencodeTorrent($torrentFile["tmp_name"], true);
    #!d($torrent);exit;

    # private and source fields
    $publicTorrent = $torrent->make_private();
    $unsourcedTorrent = $torrent->make_sourced();
    $infoHash = pack('H*', $torrent->info_hash());

    # validate torrent data and get info
    $torrentData = $validate->bencoded($torrent);
    #!d($torrentData);exit;
} catch (Throwable $e) {
    $validate->errors["torrentFile"] = $e->getMessage();
}

# there are errors, bail out with data
if (!empty($validate->errors)) {
    $data = $data;
    $errors = $validate->errors;

    #require_once "{$app->env->serverRoot}/sections/upload/upload.php";
    #exit;
}


/**
 * ad hoc field normalization
 */

# multiple artists!
$data["creatorList"] = explode("\n", $data["creatorList"]);
if (empty($data["groupId"])) {
    foreach ($data["creatorList"] as $key => $value) {
        # escape and normalize
        $data["creatorList"][$key] = Esc::string($value);
        $data["creatorList"][$key] = Artists::normalise_artist_name($value);
    }
}

/*
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
*/

# literature list
$data["literature"] = explode("\n", $data["literature"]);
$data["literature"] = array_slice($data["literature"], 0, 10);

$data["literature"] = array_filter($data["literature"]);
$data["literature"] = array_unique($data["literature"]);

# tag list
foreach ($data["tagList"] as $key => $value) {
    $data["tagList"][$key] = Illuminate\Support\Str::slug($value, ".");
    $data["tagList"][$key] = Misc::sanitize_tag($value); # gazelle
    $data["tagList"][$key] = Misc::get_alias_tag($value); # gazelle
}

$data["tagList"] = array_filter($data["tagList"]);
$data["tagList"] = array_unique($data["tagList"]);

# mirror list
$data["mirrors"] = explode("\n", $data["mirrors"]);
$data["mirrors"] = array_slice($data["mirrors"], 0, 2);

$data["mirrors"] = array_filter($data["mirrors"]);
$data["mirrors"] = array_unique($data["mirrors"]);

# debug
#!d($data);exit;


/**
 * database stuff
 * this should be a transaction
 */

# key shorthand variables
$categoryId = $data["categoryId"];
$categoryName = $app->env->CATS->{$categoryId}->Name;

$groupId = $data["groupId"] ?? null;
$revisionId = $data["revisionId"] ?? null;

# does it belong in a group?
if ($groupId) {
    $query = "
        select id, picture, description, revision_id, title, year, tag_list
        from torrents_group where id = ?
    ";
    $row = $app->dbNew->row($query, [$groupId]);

    if ($row) {
        # tagList
        $data["tagList"] = str_replace([" ", ".", "_"], [", ", ".", "."], $row["tag_list"]);

        # picture
        if (!$data["picture"] && $row["picture"]) {
            $data["picture"] = $row["picture"];
        }

        # description
        if (strlen($row["description"]) > strlen($data["groupDescription"])) {
            #$data["groupDescription"] = $row["description"]; # ?
            if (!$data["picture"] || $data["picture"] === $row["picture"]) {
                $noRevision = true;
            }
        }

        # probably unnecesary
        #$data["creator"] = Artists::display_artists(Artists::get_artist($groupId), false, false);
    }
}

# it doesn't belong in a group
if (!$groupId) {
    # the torrent hasn't been uploaded, try to get the creator ids
    foreach ($data["creatorList"] as $key => $creator) {
        $query = "select artistId, name from artists_group where name = ?";
        $row = $app->dbNew->row($query, [$creator]);
        #!d($row);

        if ($row && !strcasecmp($creator, $row["name"])) {
            $data["creatorList"][$key] = [ "id" => $row["artistId"], "name" => $row["name"] ];
        }
    }
}

# insert into artists_group if no groupId
if (!$groupId) {
    foreach ($data["creatorList"] as $creator) {
        $query = "insert ignore into artists_group (name) values (?)";
        $app->dbNew->do($query, [ $creator["name"] ]);

        $app->cacheNew->increment("stats_artist_count");
    }
}

# create a new torrents_group entry if no groupId
if (!$groupId) {
    $query = "
        insert into torrents_group
            (category_id, title, subject, object, year,
            location, workgroup, identifier, timestamp,
            description, picture)
        values
            (:category_id, :title, :subject, :object, :year,
            :location, :workgroup, :identifier, now(),
            :description, :picture)
    ";

    $variables = [
        "category_id" => $data["categoryId"],
        "title" => $data["title"],
        "subject" => $data["subject"],
        "object" => $data["object"],
        "year" => $data["year"],
        "location" => $data["location"],
        "workgroup" => $data["workgroup"],
        "identifier" => $data["identifier"],
        "description" => $data["groupDescription"],
        "picture" => $data["picture"],
    ];

    $app->dbNew->do($query, $variables);
    $groupId = $app->dbNew->lastInsertId();

    # add creators to the group
    foreach ($data["creatorList"] as $id => $name) {
        $query = "insert ignore into torrents_artists (groupId, artistId, userId) values (?, ?, ?)";
        $app->dbNew->do($query, [ $groupId, $id, $app->user->core["id"] ]);

        $app->cacheNew->increment("stats_album_count");
        $app->cacheNew->delete("artist_groups_{$id}");
    }

    $app->cacheNew->increment("stats_group_count");

    # now add the doi numbers
    # semantic scholar crawls these via cron
    if (!empty($data["literature"])) {
        foreach ($data["literature"] as $literature) {
            $query = "insert into literature (group_id, user_id, timestamp, doi) values (?, ?, now(), ?)";
            $app->dbNew->do($query, [ $groupId, $app->user->core["id"], $literature]);
        }
    }
}

# update an existing torrents_group
if ($groupId) {
    $query = "update torrents_group set timestamp = now() where id = ?";
    $app->dbNew->do($query, [$groupId]);

    $app->cacheNew->delete("torrent_group_{$groupId}");
    $app->cacheNew->delete("torrents_details_{$groupId}");
    $app->cacheNew->delete("detail_files_{$groupId}");
}

# description if not noRevision
$noRevision ??= null;
if (!$noRevision) {
    $query = "
        insert into wiki_torrents (pageId, body, userId, summary, time, image)
        values (:pageId, :body, :userId, :summary, now(), :image)
    ";

    $variables = [
        "pageId" => $groupId,
        "body" => $data["groupDescription"],
        "userId" => $app->user->core["id"],
        "summary" => "uploaded new torrent",
        "image" => $data["picture"],
    ];

    $app->dbNew->do($query, $variables);
    $revisionId = $app->dbNew->lastInsertId();

    # revisionId
    $query = "update torrents_group set revision_id = ? where id = ?";
    $app->dbNew->do($query, [$revisionId, $groupId]);
}

# tagList if no groupId
if (!$groupId) {
    foreach ($data["tagList"] as $tag) {
        $query = "
            insert into tags (name, userId) values (?, ?)
            on duplicate key update uses = uses + 1
        ";

        $app->dbNew->do($query, [ $tag, $app->user->core["id"] ]);
        $tagId = $app->dbNew->lastInsertId();

        # torrents_tags
        $query = "insert into torrents_tags (tagId, groupId, userId) values (?, ?, ?)";
        $app->dbNew->do($query, [ $tagId, $groupId, $app->user->core["id"] ]);
    }
}

# torrents over a size in bytes are neutral leech
# download doesn't count, but upload does
$neutralLeechThreshold = 1024 * 1024 * 1024 * 1024 * 10; # gigabytes
if ($torrentData["dataSize"] > $neutralLeechThreshold) {
    $data["freeleechType"] = 2;
    $data["freeleechReason"] = 2;
}

/*
# todo: add to shop freeleeches if necessary
if ($data["freeleechReason"] === 3) {
    # figure out which duration to use
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
            (" . $torrentId . ", FROM_UNIXTIME(" . $Expiry . "))
          ON DUPLICATE KEY UPDATE
            ExpiryTime = FROM_UNIXTIME(UNIX_TIMESTAMP(ExpiryTime) + ($Expiry - FROM_UNIXTIME(NOW())))");
    } else {
        Torrents::freeleech_torrents($torrentId, 0, 0);
    }
}
*/

# the torrent entry itself
$query = "
    insert into torrents
        (groupId, userId, media, container, codec,
        resolution, version, censored, anonymous, archive,
        info_hash, fileCount, fileList, filePath, size,
        time, description, freeTorrent, freeLeechType)
    values
        (:groupId, :userId, :media, :container, :codec,
        :resolution, :version, :censored, :anonymous, :archive,
        :info_hash, :fileCount, :fileList, :filePath, :size,
        now(), :description, :freeTorrent, :freeLeechType)
";

$variables = [
    "groupId" => $groupId,
    "userId" => $app->user->core["id"],
    "media" => $data["platform"],
    "container" => $data["format"],
    "codec" => $data["license"],

    "resolution" => $data["scope"],
    "version" => $data["version"],
    "censored" => $data["annotated"],
    "anonymous" => $data["anonymous"],
    "archive" => $data["archive"],

    "info_hash" => $infoHash,
    "fileCount" => $torrentData["fileCount"],
    "fileList" => $torrentData["fileList"],
    "filePath" => $torrentData["directoryName"],
    "size" => $torrentData["dataSize"],

    "description" => $data["torrentDescription"],
    "freeTorrent" => $data["freeleechType"],
    "freeLeechType" => $data["freeleechReason"],
];

$app->dbNew->do($query, $variables);
$torrentId = $app->dbNew->lastInsertId();

$app->cacheNew->increment("stats_torrent_count");
$torrent->Dec["comment"] = "https://{$app->env->siteDomain}/torrents.php?torrentId={$torrentId}";

# http/ftp data mirrors (web seeds)
# todo: support ipfs/dat
if (!empty($data["mirrors"])) {
    foreach ($data["mirrors"] as $mirror) {
        $query = "insert into torrents_mirrors (torrent_id, user_id, timestamp, uri) values (?, ?, now(), ?)";
        $app->dbNew->do($query, [$torrentId, $app->user->core["id"], $mirror]);
    }
}

/*
# todo: seqhash
if ($app->env->enableBioPhp && !empty($data['Seqhash'])) {
    $BioIO = new \BioPHP\IO();
    $BioSeqhash = new \BioPHP\Seqhash();

    $Parsed = $BioIO->readFasta($data['Seqhash']);
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
                $torrentId,
                $app->user->core['id'],
                $Parsed['name'],
                $Seqhash
            );
        } catch (Throwable $Err) {
            $UploadForm = $Type;
            require_once serverRoot.'/sections/upload/upload.php' ;
            error($Err->getMessage(), $NoHTML = true);
        }
    }
}
*/


/**
 * update the tracker
 */

Tracker::update_tracker("add_torrent", [
   "id" => $torrentId,
   "info_hash" => rawurlencode($infoHash),
   "freetorrent" => $data["freeleechType"]
 ]);

# prevent deletion of this torrent until the rest of the upload process is done
# (expire the key after 10 minutes to prevent locking it for too long in case there's a fatal error below)
$app->cacheNew->set("torrent_{$torrentId}_lock", true, 600);
$app->debug["messages"]->info("ocelot updated");


/**
 * write the torrent file
 */

# coerce type
$groupId = intval($groupId);

# write to disk
$fileName = "{$app->env->torrentStore}/{$torrentId}.torrent";
file_put_contents($fileName, $torrent->encode());
chmod($fileName, 0400);

# update site logs
$torrentLogMessage = "Torrent {$torrentId} - {$data["title"]} - "
    . Text::float($torrentData["dataSize"] / (1024 * 1024), 2)
    ." MB - uploaded by {$app->user->core["username"]}";
Misc::write_log($torrentLogMessage);

$groupLogMessage = "uploaded " . Text::float($torrentData["dataSize"] / (1024 * 1024), 2) . " MB";
Torrents::write_group_log($groupId, $torrentId, $app->user->core["id"], $groupLogMessage, 0);

# update hash
Torrents::update_hash($groupId);
$app->debug["messages"]->info("manticore updated");


/**
 * recent uploads
 */
if ($data["picture"]) {
    $recentUploads = $app->cacheNew->get("recent_uploads_{$app->user->core["id"]}");
    if (is_array($recentUploads)) {
        do {
            foreach ($recentUploads as $item) {
                if ($item["ID"] === $groupId) {
                    break 2;
                }
            }

            # only reached if no matching groupIds in the cache already
            if (count($recentUploads) === 5) {
                array_pop($recentUploads);
            }

            array_unshift($recentUploads, [
                "ID" => $GroupID,
                "Name" => $data["title"],
                "Artist" => Artists::display_artists($data["creatorList"], false, true),
                "WikiImage" => $data["picture"],
            ]);
            $app->cacheNew->set("recent_uploads_{$app->user->core["id"]}", $recentUploads, 0);
        } while (0);
    }
}


/**
 * post-processing
 *
 * because tracker updates and notifications can be slow,
 * we're redirecting the user to the destination page and flushing the buffers,
 * to make it seem like the PHP process is working in the background
 */

/*
if ($publicTorrent) {
   View::header('Warning'); ?>
<h1>Warning</h1>
<p>
   <strong>Your torrent has been uploaded but you must re-download your torrent file from
       <a
           href="torrents.php?id=<?=$GroupID?>&torrentid=<?=$torrentId?>">here</a>
       because the site modified it to make it private.</strong>
</p>
<?php
 View::footer();
} elseif ($unsourcedTorrent) {
   View::header('Warning'); ?>
<h1>Warning</h1>
<p>
   <strong>Your torrent has been uploaded but you must re-download your torrent file from
       <a
           href="torrents.php?id=<?=$GroupID?>&torrentid=<?=$torrentId?>">here</a>
       because the site modified it to add a source flag.</strong>
</p>
<?php
 View::footer();
} elseif ($RequestID) {
   header("Location: requests.php?action=takefill&requestid=$RequestID&torrentid=$torrentId&auth=".$app->user->extra['AuthKey']);
} else {
   Http::redirect("torrents.php?id=$GroupID&torrentid=$torrentId");
}

if (function_exists('fastcgi_finish_request')) {
   fastcgi_finish_request();
} else {
   ignore_user_abort(true);
   ob_flush();
   flush();
   ob_start(); // So we don't keep sending data to the client
}
*/


/**
 * announce on different channels
 */

# construct message
$torrentInfo = [
    "Censored" => $data["annotated"],
    "IsLeeching" => false,
    "IsSeeding" => false,
    "IsSnatched" => false,
    "FreeTorrent" => $data["freeleechType"],
    "PersonalFL" => false,
];

$announceMessage = "[{$categoryName}]"
    . " " . Illuminate\Support\Str::limit($data["title"]) . " "
    . "[ " . Torrents::torrent_info($torrentInfo, true, false, false) . " ]"
    . " - " . trim(implode(", ", $data["tagList"]))
    . " - " . site_url() . "/torrents.php?id={$groupId}&torrentId={$torrentId}"
    . " - " . site_url() . "/torrents.php?action=download&id={$torrentId}";

/*
# announce on irc
# ENT_QUOTES is needed to decode single quotes/apostrophes
Announce::irc($announceMessage);
$app->debug["messages"]->info("announced on irc");
*/

# announce on slack
Announce::slack($announceMessage);
$app->debug["messages"]->info("announced on slack");

# announce on rss
$item = $feed->item(
    $announceMessage,
    $data["groupDescription"],
    "/torrents.php?action=download&authkey=[[AUTHKEY]]&torrent_pass=[[PASSKEY]]&id={$torrentId}",
    $data["anonymous"] ? "Anonymous" : $app->user->core["username"],
    "/torrents.php?id={$groupId}",
    trim(implode(", ", $data["tagList"]))
);


/**
 * manage notifications
 */


/** TODO: START HERE */
exit;



/** ALL THE STUFF AFTER POST-PROCESSING */


/**
 * Manage motifications
 */
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

if ($data['Format']) {
    $SQL .= " AND (Formats LIKE '%|".db_string(trim($data['Format']))."|%' OR Formats = '') ";
} else {
    $SQL .= " AND (Formats = '') ";
}

if ($data['Media']) {
    $SQL .= " AND (Media LIKE '%|".db_string(trim($data['Media']))."|%' OR Media = '') ";
} else {
    $SQL .= " AND (Media = '') ";
}

// Either they aren't using NewGroupsOnly
$SQL .= "AND ((NewGroupsOnly = '0' ";
// Or this is the first torrent in the group to match the formatbitrate filter
$SQL .= ") OR ( NewGroupsOnly = '1' ";
$SQL .= '))';

if ($data['Year']) {
    $SQL .= " AND (('".db_string(trim($data['Year']))."' BETWEEN FromYear AND ToYear)
      OR (FromYear = 0 AND ToYear = 0)) ";
} else {
    $SQL .= " AND (FromYear = 0 AND ToYear = 0) ";
}


$SQL .= " AND UserID != '".$app->user->core['id']."' ";
$app->dbOld->query($SQL);
$app->debug["messages"]->info('notification query finished');

if ($app->dbOld->has_results()) {
    $UserArray = $app->dbOld->to_array('UserID');
    $FilterArray = $app->dbOld->to_array('ID');

    $InsertSQL = '
      INSERT IGNORE INTO `users_notify_torrents` (`UserID`, `GroupID`, `TorrentID`, `FilterID`)
      VALUES ';

    $Rows = [];
    foreach ($UserArray as $User) {
        list($FilterID, $UserID, $Passkey) = $User;
        $Rows[] = "('$UserID', '$GroupID', '$torrentId', '$FilterID')";
        $feed->populate("torrents_notify_$Passkey", $item);
        $app->cacheNew->delete("notifications_new_$UserID");
    }

    $InsertSQL .= implode(',', $Rows);
    $app->dbOld->query($InsertSQL);
    $app->debug["messages"]->info('notification inserts finished');

    foreach ($FilterArray as $Filter) {
        list($FilterID, $UserID, $Passkey) = $Filter;
        $feed->populate("torrents_notify_{$FilterID}_$Passkey", $item);
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
    $feed->populate("torrents_bookmarks_t_$Passkey", $item);
}

$feed->populate('torrents_all', $item);
$feed->populate('torrents_'.strtolower($Type), $item);
$app->debug["messages"]->info('notifications handled');

# Clear cache
$app->cacheNew->delete("torrents_details_$GroupID");
$app->cacheNew->delete("contest_scores");

# Allow deletion of this torrent now
$app->cacheNew->delete("torrent_{$torrentId}_lock");

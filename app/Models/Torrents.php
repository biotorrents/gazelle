<?php

declare(strict_types=1);


/**
 * Gazelle\Torrents
 */

namespace Gazelle;

class Torrents extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "torrents"; # resource name
    protected ?string $table = "torrents"; # database table

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "GroupID" => "groupId",
        "UserID" => "userId",
        "media" => "platform",
        "container" => "format",
        "codec" => "license",
        "resolution" => "scope",
        "version" => "version",
        "Censored" => "isAnnotated",
        "Anonymous" => "isAnonymous",
        "info_hash" => "infoHash",
        "FileCount" => "fileCount",
        "FileList" => "fileList",
        "FilePath" => "filePath",
        "Size" => "dataSize",
        "Leechers" => "leecherCount",
        "Seeders" => "seederCount",
        "last_action" => "lastAction",
        "FreeTorrent" => "freeleech",
        "FreeLeechType" => "freeleechType",
        #"Time" => "createdAt",
        "Description" => "description",
        "Snatched" => "snatchCount",
        "balance" => "balance",
        "LastReseedRequest" => "lastReseedRequest",
        "archive" => "archive",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];

    /**
     * wishlist database schema
     *
     * create table torrents
     * (
     *     ID             int(10) auto_increment primary key,
     *     GroupID        int(10)                                 not null,
     *     TorrentType    enum ('anime', 'music')                 not null,
     *     info_hash      blob                                    not null,
     *     Leechers       int(6)                  default 0       not null,
     *     Seeders        int(6)                  default 0       not null,
     *     last_action    int                     default 0       not null,
     *     Snatched       int unsigned            default 0       not null,
     *     DownMultiplier float                   default 1       not null,
     *     UpMultiplier   float                   default 1       not null,
     *     Status         int                     default 0       not null,
     *     constraint InfoHash unique (info_hash (20))
     * );
     */
    /*
    protected array $wishfulMaps = [
        # keys
        "id" => "id", # chihaya
        "groupId" => "groupId", # chihaya
        "userId" => "userId",

        # metadata
        "platform" => "platform",
        "format" => "format",
        "version" => "version",
        "scope" => "scope",
        "license" => "license",
        "archive" => "archive",
        "description" => "description",
        "isAnnotated" => "isAnnotated",
        "isAnonymous" => "isAnonymous",

        # files
        "info_hash" => "infoHash", # chihaya
        "filePath" => "filePath",
        "fileCount" => "fileCount",
        "fileList" => "fileList",
        "dataSize" => "dataSize",

        # tracker
        "torrentType" => "torrentType", # chihaya
        "status" => "status", # chihaya
        "seeders" => "seederCount", # chihaya
        "leechers" => "leecherCount", # chihaya
        "snatched" => "snatchCount", # chihaya
        "last_action" => "lastAction", # chihaya
        "upMultiplier" => "upMultiplier", # chihaya
        "downMultiplier" => "downMultiplier", # chihaya

        # freeleech
        "freeleechStatus" => "freeleechStatus",
        "freeleechType" => "freeleechType",
        "balance" => "balance",
        "lastReseedRequest" => "lastReseedRequest",

        # dates
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];
    */

    # cache settings
    private string $cachePrefix = "torrents:";
    private string $cacheDuration = "1 hour";

    # hex for ÷, must be the same as phrase_boundary in manticore.conf
    public const FILELIST_DELIM = 0xF7;

    # how often we want to update users' snatch lists
    public const SNATCHED_UPDATE_INTERVAL = 3600;

    # how long after a torrent download we want to update a user's snatch lists
    public const SNATCHED_UPDATE_AFTERDL = 300;


    /** crud */


    /**
     * read
     *
     * @param int|string $id
     */
    public function read(int|string $id = null): void
    {
        $app = App::go();

        # is it an info hash?
        $infoHash = ctype_xdigit(strval($id)) && strlen(strval($id)) === 40;
        if ($infoHash) {
            $query = "select id from torrents where hex(info_hash) = ?";
            $id = $app->dbNew->single($query, [$id]);

            if (!$id) {
                throw new Exception("not found");
            }
        }

        # parent read
        parent::read($id);

        # explode the fileList
        $fileList = explode("÷", $this->attributes->fileList ?? "");
        $fileData = [];

        foreach ($fileList as $file) {
            if (empty($file)) {
                continue;
            }

            $fileArray = explode(" ", $file);
            $fileData[] = [
                "ext" => trim($fileArray[0]),
                "size" => str_replace("s", "", trim($fileArray[1])),
                "name" => trim($fileArray[2]),
            ];
        }

        $this->attributes->fileList = $fileData;
    }


    /**
     * relationships
     *
     * @return ?array
     */
    public function relationships(): ?array
    {
        return [
            TorrentGroups::$type => $this->relatedTorrentGroups(),
        ];
    }


    /**
     * relatedTorrentGroups
     *
     * @return array
     */
    private function relatedTorrentGroups(): array
    {
        $app = \Gazelle\App::go();

        $query = "select torrents_group.id from torrents_group join torrents on torrents.groupId = torrents_group.id where torrents.id = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => TorrentGroups::$type];
        }

        return $data;
    }


    /** legacy */


    /**
      * get_groups
      *
      * Function to get data and torrents for an array of GroupIDs. Order of keys doesn't matter
      *
      * @param array $GroupIDs
      * @param boolean $Return if false, nothing is returned. For priming cache.
      * @param boolean $GetArtists if true, each group will contain the result of
      *  \Gazelle\Creators::get_artists($GroupID), in result[$GroupID]['ExtendedArtists']
      * @param boolean $Torrents if true, each group contains a list of torrents, in result[$GroupID]['Torrents']
      *
      * @return array each row of the following format:
      * GroupID => (
      *  ID
      *  Name
      *  Year
      *  RecordLabel
      *  CatalogueNumber
      *  TagList
      *  ReleaseType
      *  VanityHouse
      *  WikiImage
      *  CategoryID
      *  Torrents => {
      *    ID => {
      *      GroupID, Media, Format, Encoding, RemasterYear, Remastered,
      *      RemasterTitle, RemasterRecordLabel, RemasterCatalogueNumber, Scene,
      *      HasLog, HasCue, LogScore, FileCount, FreeTorrent, Size, Leechers,
      *      Seeders, Snatched, Time, HasFile, PersonalFL, IsSnatched
      *    }
      *  }
      *  Artists => {
      *    {
      *      id, name, aliasid // Only main artists
      *    }
      *  }
      *  ExtendedArtists => {
      *    [1-6] => { // See documentation on \Gazelle\Creators::get_artists
      *      id, name, aliasid
      *    }
      *  }
      *  Flags => {
      *    IsSnatched
      *  }
      */
    public static function get_groups($GroupIDs, $Return = true, $GetArtists = true, $Torrents = true)
    {
        $app = \Gazelle\App::go();

        $data = [];
        foreach ($GroupIDs as $groupId) {
            $data[$groupId] = new \Gazelle\TorrentGroups($groupId);
        }

        foreach ($data as $group) {
            if (!$group->id) {
                unset($data[$group->id]);
            }
        }

        return $data;

        /** */

        $app = \Gazelle\App::go();

        $GroupIDs ??= [];

        $Found = $NotFound = array_fill_keys($GroupIDs, false);
        $Key = $Torrents ? 'torrent_group_' : 'torrent_group_light_';

        foreach ($GroupIDs as $i => $GroupID) {
            if (!is_numeric($GroupID)) {
                unset($GroupIDs[$i], $Found[$GroupID], $NotFound[$GroupID]);
                continue;
            }

            $Data = $app->cache->get($Key . $GroupID, true);
            if (!empty($Data) && is_array($Data) && $Data['ver'] === $app->cache->groupVersion) {
                unset($NotFound[$GroupID]);
                $Found[$GroupID] = $Data['d'];
            }
        }

        // Make sure there's something in $GroupIDs, otherwise the SQL will break
        if (count($GroupIDs) === 0) {
            return [];
        }

        /**
         * Changing any of these attributes returned will cause very large, very dramatic site-wide chaos.
         * Do not change what is returned or the order thereof without updating:
         * torrents, artists, collages, bookmarks, better, the front page,
         * and anywhere else the get_groups function is used.
         * Update self::array_group(), too.
         */

        if (count($NotFound) > 0) {
            $IDs = implode(',', array_keys($NotFound));
            $NotFound = [];
            $QueryID = $app->dbOld->get_query_id();

            $app->dbOld->prepared_query("
            SELECT
              `id`,
              `title`,
              `subject`,
              `object`,
              `year`,
              `identifier`,
              `workgroup`,
              `location`,
              `tags`,
              `picture`,
              `categoryId`
            FROM
              `torrents_group`
            WHERE
              `id` IN($IDs)
            ");

            while ($Group = $app->dbOld->next_record(MYSQLI_ASSOC, true)) {
                $NotFound[$Group['id']] = $Group;
                $NotFound[$Group['id']]['Torrents'] = [];
                $NotFound[$Group['id']]['Artists'] = [];
            }
            $app->dbOld->set_query_id($QueryID);

            if ($Torrents) {
                $QueryID = $app->dbOld->get_query_id();

                $app->dbOld->query("
                SELECT
                  torrents.id,
                  `GroupID`,
                  `Media`,
                  `Container`,
                  `Codec`,
                  `Resolution`,
                  `Version`,
                  `Censored`,
                  `Archive`,
                  `FileCount`,
                  `FreeTorrent`,
                  `Size`,
                  `Leechers`,
                  `Seeders`,
                  `Snatched`,
                  `Time`,
                  f.`ExpiryTime`,
                  torrents.id AS `HasFile`,
                  `FreeLeechType`,
                  HEX(`info_hash`) AS `info_hash`
                FROM
                  `torrents`
                LEFT JOIN `shop_freeleeches` AS f
                ON
                  f.`TorrentID` = torrents.id
                WHERE
                  `GroupID` IN($IDs)
                ORDER BY
                  `GroupID`,
                  `Media`,
                  `Container`,
                  `Codec`,
                  torrents.id
                ");

                while ($Torrent = $app->dbOld->next_record(MYSQLI_ASSOC, true)) {
                    $NotFound[$Torrent['GroupID']]['Torrents'][$Torrent['id']] = $Torrent;
                }
                $app->dbOld->set_query_id($QueryID);
            }

            foreach ($NotFound as $GroupID => $GroupInfo) {
                $app->cache->set($Key . $GroupID, array('ver' => $app->cache->groupVersion, 'd' => $GroupInfo), 0);
            }

            $Found = $NotFound + $Found;
        }

        // Filter out orphans (elements that are === false)
        $Found = array_filter($Found);

        if ($GetArtists) {
            $Artists = \Gazelle\Creators::get_artists($GroupIDs);
        } else {
            $Artists = [];
        }

        if ($Return) { // If we're interested in the data, and not just caching it
            foreach ($Artists as $GroupID => $Data) {
                if (!isset($Found[$GroupID])) {
                    continue;
                }
                $Found[$GroupID]['Artists'] = $Data;
            }

            // Fetch all user specific torrent properties
            if ($Torrents) {
                foreach ($Found as &$Group) {
                    !d($Group["tags"]);
                    exit;

                    $Group['Flags'] = array('IsSnatched' => false, 'IsSeeding' => false, 'IsLeeching' => false);
                    if (!empty($Group['Torrents'])) {
                        foreach ($Group['Torrents'] as &$Torrent) {
                            self::torrent_properties($Torrent, $Group['Flags']);
                        }
                    }
                }
            }

            return $Found;
        }
    }


    /**
     * array_group
     *
     * Returns a reconfigured array from a Torrent Group
     *
     * Use this with extract() instead of the volatile list($GroupID, ...)
     * Then use the variables $GroupID, $GroupName, etc
     *
     * @example  extract(\Gazelle\Torrents::array_group($SomeGroup));
     * @param array $Group torrent group
     * @return array Re-key'd array
     */
    public static function array_group(array &$Group)
    {
        return array(
          'id' => $Group['id'],
          'title' => $Group['title'],
          'subject' => $Group['subject'],
          'object' => $Group['object'],
          'year' => $Group['year'],
          'categoryId' => $Group['categoryId'],
          'identifier' => $Group['identifier'],
          'workgroup' => $Group['workgroup'],
          'location' => $Group['location'],
          'GroupFlags' => ($Group['Flags'] ?? ''),
          'tags' => json_decode($Group['tags'] ?? []),
          'picture' => $Group['picture'],
          'Torrents' => $Group['Torrents'],
          'Artists' => $Group['Artists']
        );
    }


    /**
     * torrent_properties
     *
     * Supplements a torrent array with information that only concerns certain users and therefore cannot be cached
     *
     * @param array $Torrent torrent array preferably in the form used by \Gazelle\Torrents::get_groups() or TorrentFunctions::get_group_info()
     * @param int $TorrentID
     */
    public static function torrent_properties(&$Torrent, &$Flags)
    {
        # FL Token
        $Torrent['PersonalFL'] = empty($Torrent['FreeTorrent']) && self::has_token($Torrent['ID']);

        # Snatched
        if ($Torrent['IsSnatched'] = self::has_snatched($Torrent['ID'])) {
            $Flags['IsSnatched'] = true;
        } else {
            $Flags['IsSnatched'] = false;
        }

        # Seeding
        if ($Torrent['IsSeeding'] = self::is_seeding($Torrent['ID'])) {
            $Flags['IsSeeding'] = true;
        } else {
            $Flags['IsSeeding'] = false;
        }

        # Leeching
        if ($Torrent['IsLeeching'] = self::is_leeching($Torrent['ID'])) {
            $Flags['IsLeeching'] = true;
        } else {
            $Flags['IsLeeching'] = false;
        }
    }


    /**
     * write_group_log
     *
     * Write to the group log.
     *
     * @param int $GroupID
     * @param int $TorrentID
     * @param int $UserID
     * @param string $Message
     * @param boolean $Hidden Currently does fuck all.
     *
     * todo: Fix that
     */
    public static function write_group_log($GroupID, $TorrentID, $UserID, $Message, $Hidden)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        INSERT INTO `group_log`(
          `GroupID`,
          `TorrentID`,
          `UserID`,
          `Info`,
          `Time`,
          `Hidden`
        )
        VALUES(
          '$GroupID',
          '$TorrentID',
          '$UserID',
          '$Message',
          NOW(),
          '$Hidden'
        )
        ");
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * delete_torrent
     *
     * Delete a torrent.
     *
     * @param int $ID The ID of the torrent to delete.
     * @param int $GroupID Set it if you have it handy, to save a query. Otherwise, it will be found.
     * @param string $OcelotReason The deletion reason for ocelot to report to users.
     */
    public static function delete_torrent($ID, $GroupID = 0, $OcelotReason = -1)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        if (!$GroupID) {
            $app->dbOld->query("
            SELECT GroupID, UserID
            FROM torrents
              WHERE ID = '$ID'");
            list($GroupID, $UploaderID) = $app->dbOld->next_record();
        }
        if (empty($UserID)) {
            $app->dbOld->query("
            SELECT UserID
            FROM torrents
              WHERE ID = '$ID'");
            list($UserID) = $app->dbOld->next_record();
        }

        $RecentUploads = $app->cache->get("recent_uploads_$UserID");
        if (is_array($RecentUploads)) {
            foreach ($RecentUploads as $Key => $Recent) {
                if ($Recent['ID'] == $GroupID) {
                    $app->cache->delete("recent_uploads_$UserID");
                }
            }
        }

        $app->dbOld->query("
        SELECT info_hash
        FROM torrents
          WHERE ID = $ID");
        list($InfoHash) = $app->dbOld->next_record(MYSQLI_BOTH, false);
        $app->dbOld->query("
        DELETE FROM torrents
          WHERE ID = $ID");
        \TrackerOld::update_tracker('delete_torrent', array('info_hash' => rawurlencode($InfoHash), 'id' => $ID, 'reason' => $OcelotReason));

        $app->cache->decrement('stats_torrent_count');

        $app->dbOld->query("
        SELECT COUNT(ID)
        FROM torrents
          WHERE GroupID = '$GroupID'");
        list($Count) = $app->dbOld->next_record();

        if ($Count == 0) {
            \Gazelle\Torrents::delete_group($GroupID);
        } else {
            \Gazelle\Torrents::update_hash($GroupID);
        }

        // Torrent notifications
        $app->dbOld->query("
        SELECT UserID
        FROM users_notify_torrents
          WHERE TorrentID = '$ID'");
        while (list($UserID) = $app->dbOld->next_record()) {
            $app->cache->delete("notifications_new_$UserID");
        }
        $app->dbOld->query("
        DELETE FROM users_notify_torrents
          WHERE TorrentID = '$ID'");

        $app->dbOld->query("
        UPDATE reportsv2
        SET
          Status = 'Resolved',
          LastChangeTime = NOW(),
          ModComment = 'Report already dealt with (torrent deleted)'
          WHERE TorrentID = ?
          AND Status != 'Resolved'", $ID);
        $Reports = $app->dbOld->affected_rows();
        if ($Reports) {
            $app->cache->decrement('num_torrent_reportsv2', $Reports);
        }

        unlink($app->env->torrentStore . '/' . $ID . '.torrent');
        $app->dbOld->query("
        DELETE FROM torrents_bad_tags
          WHERE TorrentID = ?", $ID);
        $app->dbOld->query("
        DELETE FROM torrents_bad_folders
          WHERE TorrentID = ?", $ID);
        $app->dbOld->query("
        DELETE FROM torrents_bad_files
          WHERE TorrentID = ?", $ID);

        $app->dbOld->query("
        DELETE FROM shop_freeleeches
          WHERE TorrentID = ?", $ID);
        $FLs = $app->dbOld->affected_rows();
        if ($FLs) {
            $app->cache->delete('shop_freeleech_list');
        }

        $app->cache->delete("torrent_download_$ID");
        $app->cache->delete("torrent_group_$GroupID");
        $app->cache->delete("torrents_details_$GroupID");
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * delete_group
     *
     * Delete a group, called after all of its torrents have been deleted.
     * IMPORTANT: Never call this unless you're certain the group is no longer used by any torrents
     *
     * @param int $GroupID
     */
    public static function delete_group($GroupID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();

        Misc::write_log("Group $GroupID automatically deleted (No torrents have this group).");

        $app->dbOld->prepared_query("
        SELECT
          `categoryId`
        FROM
          `torrents_group`
        WHERE
          `id` = '$GroupID'
        ");

        list($Category) = $app->dbOld->next_record();

        # todo: Check strict equality here
        if ($Category === 1) {
            $app->cache->decrement('stats_album_count');
        }
        $app->cache->decrement('stats_group_count');

        // Collages
        $app->dbOld->query("
        SELECT CollageID
        FROM collages_torrents
          WHERE GroupID = ?", $GroupID);
        if ($app->dbOld->has_results()) {
            $CollageIDs = $app->dbOld->collect('CollageID');
            $app->dbOld->query("
            UPDATE collages
            SET NumTorrents = NumTorrents - 1
              WHERE ID IN (" . implode(', ', $CollageIDs) . ')');
            $app->dbOld->query("
            DELETE FROM collages_torrents
              WHERE GroupID = ?", $GroupID);

            foreach ($CollageIDs as $CollageID) {
                $app->cache->delete("collage_$CollageID");
            }
            $app->cache->delete("torrent_collages_$GroupID");
        }

        // Artists
        // Collect the artist IDs and then wipe the torrents_artist entry
        $app->dbOld->query("
        SELECT ArtistID
        FROM torrents_artists
          WHERE GroupID = ?", $GroupID);
        $Artists = $app->dbOld->collect('ArtistID');

        $app->dbOld->query("
        DELETE FROM torrents_artists
          WHERE GroupID = ?", $GroupID);

        foreach ($Artists as $ArtistID) {
            if (empty($ArtistID)) {
                continue;
            }
            // Get a count of how many groups or requests use the artist ID
            $app->dbOld->query("
            SELECT COUNT(ag.ArtistID)
            FROM artists_group AS ag
              LEFT JOIN requests_artists AS ra ON ag.ArtistID = ra.ArtistID
              WHERE ra.ArtistID IS NOT NULL
              AND ag.ArtistID = ?", $ArtistID);
            list($ReqCount) = $app->dbOld->next_record();
            $app->dbOld->query("
            SELECT COUNT(ag.ArtistID)
            FROM artists_group AS ag
              LEFT JOIN torrents_artists AS ta ON ag.ArtistID = ta.ArtistID
              WHERE ta.ArtistID IS NOT NULL
              AND ag.ArtistID = ?", $ArtistID);
            list($GroupCount) = $app->dbOld->next_record();
            if (($ReqCount + $GroupCount) == 0) {
                //The only group to use this artist
                \Gazelle\Creators::delete_artist($ArtistID);
            } else {
                //Not the only group, still need to clear cache
                $app->cache->delete("artist_groups_$ArtistID");
            }
        }

        // Requests
        $app->dbOld->query("
        SELECT ID
        FROM requests
          WHERE GroupID = ?", $GroupID);
        $Requests = $app->dbOld->collect('ID');
        $app->dbOld->query("
        UPDATE requests
        SET GroupID = NULL
          WHERE GroupID = ?", $GroupID);
        foreach ($Requests as $RequestID) {
            $app->cache->delete("request_$RequestID");
        }

        // Comments
        \Gazelle\Conversations::delete_page('torrents', $GroupID);

        $app->dbOld->prepared_query("
        DELETE
        FROM
          `torrents_group`
        WHERE
          `id` = '$GroupID'
        ");


        $app->dbOld->prepared_query("
        DELETE
        FROM
          `torrents_tags`
        WHERE
          `GroupID` = '$GroupID'
        ");


        $app->dbOld->prepared_query("
        DELETE
        FROM
          `bookmarks_torrents`
        WHERE
          `GroupID` = '$GroupID'
        ");


        $app->dbOld->prepared_query("
        DELETE
        FROM
          `wiki_torrents`
        WHERE
          `PageID` = '$GroupID'
        ");


        $app->cache->delete("torrents_details_$GroupID");
        $app->cache->delete("torrent_group_$GroupID");
        $app->cache->delete("groups_artists_$GroupID");
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * update_hash
     *
     * Update the cache to keep everything up-to-date.
     *
     * @param int $GroupID
     */
    public static function update_hash(int $GroupID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();

        /*
        $app->dbOld->prepared_query("
        UPDATE
          `torrents_group`
        SET
          `tags` =(
          SELECT
          REPLACE
            (
              GROUP_CONCAT(tags.Name SEPARATOR ' '),
              '.',
              '_'
            )
          FROM
            `torrents_tags` AS t
          INNER JOIN `tags` ON tags.`ID` = t.`TagID`
          WHERE
            t.`GroupID` = '$GroupID'
          GROUP BY
            t.`GroupID`
        )
        WHERE
          `ID` = '$GroupID'
        ");
        */


        // Fetch album artists
        $app->dbOld->prepared_query("
        SELECT GROUP_CONCAT(ag.`Name` separator ' ')
        FROM `torrents_artists` AS `ta`
          JOIN `artists_group` AS ag ON ag.`ArtistID` = ta.`ArtistID`
          WHERE ta.`GroupID` = '$GroupID'
        GROUP BY ta.`GroupID`
        ");


        if ($app->dbOld->has_results()) {
            list($ArtistName) = $app->dbOld->next_record(MYSQLI_NUM, false);
        } else {
            $ArtistName = '';
        }

        $app->cache->delete("torrents_details_$GroupID");
        $app->cache->delete("torrent_group_$GroupID");
        $app->cache->delete("torrent_group_light_$GroupID");

        $ArtistInfo = \Gazelle\Creators::get_artist($GroupID);
        $app->cache->delete("groups_artists_$GroupID");
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * regenerate_filelist
     *
     * Regenerate a torrent's file list from its meta data,
     * update the database record and clear relevant cache keys
     *
     * @param int $TorrentID
     */
    public static function regenerate_filelist($TorrentID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();

        $app->dbOld->query("
        SELECT GroupID
        FROM torrents
          WHERE ID = ?", $TorrentID);
        if ($app->dbOld->has_results()) {
            list($GroupID) = $app->dbOld->next_record(MYSQLI_NUM, false);
            $Contents = file_get_contents($app->env->torrentStore . '/' . $TorrentID . '.torrent');
            if (\Misc::is_new_torrent($Contents)) {
                $Tor = new \BencodeTorrent($Contents);
                $FilePath = (isset($Tor->Dec['info']['files']) ? \Gazelle\Text::utf8($Tor->get_name()) : '');
            } else {
                $Tor = new \TORRENT(unserialize(base64_decode($Contents)), true);
                $FilePath = (isset($Tor->Val['info']->Val['files']) ? \Gazelle\Text::utf8($Tor->get_name()) : '');
            }
            list($TotalSize, $FileList) = $Tor->file_list();
            foreach ($FileList as $File) {
                $TmpFileList[] = self::filelist_format_file($File);
            }
            $FileString = implode("\n", $TmpFileList);
            $app->dbOld->query(
                "
        UPDATE torrents
        SET Size = ?, FilePath = ?, FileList = ?
          WHERE ID = ?",
                $TotalSize,
                $FilePath,
                $FileString,
                $TorrentID
            );
            $app->cache->delete("torrents_details_$GroupID");
        }
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * filelist_delim
     *
     * Return UTF-8 encoded string to use as file delimiter in torrent file lists
     */
    public static function filelist_delim()
    {
        static $FilelistDelimUTF8;
        if (isset($FilelistDelimUTF8)) {
            return $FilelistDelimUTF8;
        }
        return $FilelistDelimUTF8 = utf8_encode(chr(self::FILELIST_DELIM));
    }


    /**
     * filelist_format_file
     *
     * Create a string that contains file info in a format that's easy to use for Sphinx
     *
     * @param array $File (File size, File name)
     * @return string with the format .EXT sSIZEs NAME DELIMITER
     */
    public static function filelist_format_file($File)
    {
        list($Size, $Name) = $File;
        $Name = \Gazelle\Text::utf8(strtr($Name, "\n\r\t", '   '));
        $ExtPos = strrpos($Name, '.');
        // Should not be $ExtPos !== false. Extensionless files that start with a . should not get extensions
        $Ext = ($ExtPos ? trim(substr($Name, $ExtPos + 1)) : '');
        return sprintf("%s s%ds %s %s", ".$Ext", $Size, $Name, self::filelist_delim());
    }


    /**
     * filelist_old_format
     *
     * Create a string that contains file info in the old format for the API
     *
     * @param string $File string with the format .EXT sSIZEs NAME DELIMITER
     * @return string with the format NAME{{{SIZE}}}
     */
    public static function filelist_old_format($File)
    {
        $File = self::filelist_get_file($File);
        return $File['name'] . '{{{' . $File['size'] . '}}}';
    }


    /**
     * filelist_get_file
     *
     * Translate a formatted file info string into a more useful array structure
     *
     * @param string $File string with the format .EXT sSIZEs NAME DELIMITER
     * @return file info array with the keys 'ext', 'size' and 'name'
     */
    public static function filelist_get_file($File)
    {
        // Need this hack because filelists are always \Gazelle\Text::esc()ed
        $DelimLen = strlen(\Gazelle\Text::esc(self::filelist_delim())) + 1;
        list($FileExt, $Size, $Name) = explode(' ', $File, 3);
        if ($Spaces = strspn($Name, ' ')) {
            $Name = str_replace(' ', '&nbsp;', substr($Name, 0, $Spaces)) . substr($Name, $Spaces);
        }
        return array(
          'ext' => $FileExt,
          'size' => substr($Size, 1, -1),
          'name' => substr($Name, 0, -$DelimLen)
          );
    }


    /**
     * torrent_info
     *
     * Format the information about a torrent.
     * @param $Data an array a subset of the following keys:
     *  Format, Encoding, HasLog, LogScore HasCue, Media, Scene, RemasterYear
     *  RemasterTitle, FreeTorrent, PersonalFL
     * @param boolean $ShowMedia if false, Media key will be omitted
     * @param boolean $ShowEdition if false, RemasterYear/RemasterTitle will be omitted
     */
    public static function torrent_info($Data, $ShowMedia = true, $ShowEdition = false, $HTMLy = true)
    {
        # Main torrent search results info!
        $Info = [];

        # Platform
        if ($ShowMedia && !empty($Data['Media'])) {
            $Info[] = ($HTMLy)
                ? '<a class="search_link" href="torrents.php?action=advanced&media='
                    . \Gazelle\Text::esc($Data['Media'])
                    . '">'
                    . \Gazelle\Text::esc($Data['Media'])
                    . '</a>'
                : \Gazelle\Text::esc($Data['Media']);
        }

        # Format
        if (!empty($Data['Container'])) {
            $Info[] = ($HTMLy)
                ? '<a class="search_link" href="torrents.php?action=advanced&container='
                    . \Gazelle\Text::esc($Data['Container'])
                    . '">'
                    . \Gazelle\Text::esc($Data['Container'])
                    . '</a>'
                : \Gazelle\Text::esc($Data['Container']);
        }

        # Archive
        if (!empty($Data['Archive'])) {
            # todo: Search on archives, lowest priority
            $Info[] = \Gazelle\Text::esc($Data['Archive']);
        }

        # Resolution
        if (!empty($Data['Resolution'])) {
            $Info[] = ($HTMLy)
                ? '<a class="search_link" href="torrents.php?action=advanced&resolution='
                    . \Gazelle\Text::esc($Data['Resolution'])
                    . '">'
                    . \Gazelle\Text::esc($Data['Resolution'])
                    . '</a>'
                : \Gazelle\Text::esc($Data['Resolution']);
        }

        # License
        if (!empty($Data['Codec'])) {
            $Info[] = ($HTMLy)
                ? '<a class="search_link" href="torrents.php?action=advanced&codec='
                    . \Gazelle\Text::esc($Data['Codec'])
                    . '">'
                    . \Gazelle\Text::esc($Data['Codec'])
                    . '</a>'
                : \Gazelle\Text::esc($Data['Codec']);
        }

        # Alignned/Annotated
        $Data["Censored"] ??= 0;
        if ($Data['Censored'] === 1) {
            $Info[] = ($HTMLy)
                ? '<a class="search_link" href="torrents.php?action=advanced&censored=1">Aligned</a>'
                : 'Aligned';
        } else {
            $Info[] = ($HTMLy)
                ? '<a class="search_link" href="torrents.php?action=advanced&censored=0">Not Aligned</a>'
                : 'Not Aligned';
        }

        /*
        if (!empty($Data['Version'])) {
          $Info[] = $Data['Version'];
        }
        */

        $Data['IsLeeching'] ??= 0;
        $Data['IsSeeding'] ??= 0;
        $Data['IsSnatched'] ??= 0;
        $Data['FreeTorrent'] ??= '0';

        if ($Data['IsLeeching']) {
            $Info[] = $HTMLy ? \Gazelle\Format::torrent_label('Leeching', 'important_text_semi') : 'Leeching';
        } elseif ($Data['IsSeeding']) {
            $Info[] = $HTMLy ? \Gazelle\Format::torrent_label('Seeding', 'important_text_alt') : 'Seeding';
        } elseif ($Data['IsSnatched']) {
            $Info[] = $HTMLy ? \Gazelle\Format::torrent_label('Snatched', 'bold') : 'Snatched';
        }

        if ($Data['FreeTorrent'] === '1') {
            if ($Data['FreeLeechType'] === '3') {
                if ($Data['ExpiryTime']) {
                    $Info[] = ($HTMLy ? \Gazelle\Format::torrent_label('Freeleech', 'important_text_alt') : 'Freeleech') . ($HTMLy ? " <strong>(" : " (") . str_replace(['month','week','day','hour','min','s'], ['m','w','d','h','m',''], time_diff(max(strtotime($Data['ExpiryTime']), time()), 1, false)) . ($HTMLy ? ")</strong>" : ")");
                } else {
                    $Info[] = $HTMLy ? \Gazelle\Format::torrent_label('Freeleech', 'important_text_alt') : 'Freeleech';
                }
            } else {
                $Info[] = $HTMLy ? \Gazelle\Format::torrent_label('Freeleech', 'important_text_alt') : 'Freeleech';
            }
        }

        if ($Data['FreeTorrent'] == '2') {
            $Info[] = $HTMLy ? \Gazelle\Format::torrent_label('Neutral Leech', 'bold') : 'Neutral Leech';
        }

        $Data['PersonalFL'] ??= null;
        if ($Data['PersonalFL']) {
            $Info[] = $HTMLy ? \Gazelle\Format::torrent_label('Personal Freeleech', 'important_text_alt') : 'Personal Freeleech';
        }

        return implode(' | ', $Info);
    }


    /**
     * freeleech_torrents
     *
     * Will freeleech / neutral leech / normalise a set of torrents
     *
     * @param array $TorrentIDs An array of torrent IDs to iterate over
     * @param int $FreeNeutral 0 = normal, 1 = fl, 2 = nl
     * @param int $FreeLeechType 0 = Unknown, 1 = Staff picks, 2 = Perma-FL (Toolbox, etc.), 3 = Vanity House
     */
    public static function freeleech_torrents($TorrentIDs, $FreeNeutral = 1, $FreeLeechType = 0, $Announce = true)
    {
        $app = \Gazelle\App::go();

        if (!is_array($TorrentIDs)) {
            $TorrentIDs = array($TorrentIDs);
        }

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
          UPDATE torrents
          SET FreeTorrent = '$FreeNeutral', FreeLeechType = '$FreeLeechType'
          WHERE ID IN (" . implode(', ', $TorrentIDs) . ')');

        $app->dbOld->query('
          SELECT ID, GroupID, info_hash
          FROM torrents
          WHERE ID IN (' . implode(', ', $TorrentIDs) . ')
            ORDER BY GroupID ASC');

        $Torrents = $app->dbOld->to_array(false, MYSQLI_NUM, false);
        $GroupIDs = $app->dbOld->collect('GroupID');
        $app->dbOld->set_query_id($QueryID);

        foreach ($Torrents as $Torrent) {
            list($TorrentID, $GroupID, $InfoHash) = $Torrent;
            \TrackerOld::update_tracker('update_torrent', array('info_hash' => rawurlencode($InfoHash), 'freetorrent' => $FreeNeutral));
            $app->cache->delete("torrent_download_$TorrentID");
            Misc::write_log(($app->user->core["username"] ?? 'System') . " marked torrent $TorrentID freeleech type $FreeLeechType");
            \Gazelle\Torrents::write_group_log($GroupID, $TorrentID, ($app->user->core["id"] ?? 0), "marked as freeleech type $FreeLeechType", 0);

            if ($Announce && ($FreeLeechType === 1 || $FreeLeechType === 3)) {
                # todo: fsockopen(): Unable to connect to 10.10.10.60:51010 (Connection refused)
                #send_irc(ANNOUNCE_CHAN, 'FREELEECH - '.site_url()."torrents.php?id=$GroupID / ".site_url()."torrents.php?action=download&id=$TorrentID");
            }
        }

        foreach ($GroupIDs as $GroupID) {
            \Gazelle\Torrents::update_hash($GroupID);
        }
    }


    /**
     * freeleech_groups
     *
     * Convenience function to allow for passing groups to \Gazelle\Torrents::freeleech_torrents()
     *
     * @param array $GroupIDs the groups in question
     * @param int $FreeNeutral see \Gazelle\Torrents::freeleech_torrents()
     * @param int $FreeLeechType see \Gazelle\Torrents::freeleech_torrents()
     */
    public static function freeleech_groups($GroupIDs, $FreeNeutral = 1, $FreeLeechType = 0)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();

        if (!is_array($GroupIDs)) {
            $GroupIDs = [$GroupIDs];
        }

        $app->dbOld->query('
          SELECT ID
          FROM torrents
          WHERE GroupID IN (' . implode(', ', $GroupIDs) . ')');

        if ($app->dbOld->has_results()) {
            $TorrentIDs = $app->dbOld->collect('ID');
            \Gazelle\Torrents::freeleech_torrents($TorrentIDs, $FreeNeutral, $FreeLeechType);
        }
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * has_token
     *
     * Check if the logged in user has an active freeleech token
     *
     * @param int $TorrentID
     * @return true if an active token exists
     */
    public static function has_token($TorrentID)
    {
        $app = \Gazelle\App::go();

        if (empty($app->user->core)) {
            return false;
        }

        static $TokenTorrents;
        $UserID = $app->user->core["id"];
        if (!isset($TokenTorrents)) {
            $TokenTorrents = $app->cache->get("users_tokens_$UserID");

            if ($TokenTorrents === false) {
                $QueryID = $app->dbOld->get_query_id();

                $app->dbOld->query("
                  SELECT TorrentID
                  FROM users_freeleeches
                  WHERE UserID = ?
                    AND Expired = 0", $UserID);

                $TokenTorrents = array_fill_keys($app->dbOld->collect('TorrentID', false), true);
                $app->dbOld->set_query_id($QueryID);
                $app->cache->set("users_tokens_$UserID", $TokenTorrents);
            }
        }
        return isset($TokenTorrents[$TorrentID]);
    }


    /**
     * can_use_token
     *
     * Check if the logged in user can use a freeleech token on this torrent
     *
     * @param int $Torrent
     * @return boolen True if user is allowed to use a token
     */
    public static function can_use_token($Torrent)
    {
        if (empty($app->user->core)) {
            return false;
        }

        return ($app->user->extra['FLTokens'] > 0
      && $Torrent['Size'] <= 10737418240
      && !$Torrent['PersonalFL']
      && empty($Torrent['FreeTorrent'])
      && $app->user->extra['CanLeech'] == '1');
    }


    /**
     * has_snatched
     *
     * Build snatchlists and check if a torrent has been snatched
     * if a user has the 'ShowSnatched' option enabled
     * @param int $TorrentID
     * @return bool
     */
    public static function has_snatched($TorrentID)
    {
        $app = \Gazelle\App::go();

        if (empty($app->user->core) || !isset($app->user->extra['ShowSnatched']) || !$app->user->extra['ShowSnatched']) {
            return false;
        }

        $UserID = $app->user->core["id"];
        $Buckets = 64;
        $LastBucket = $Buckets - 1;
        $BucketID = $TorrentID & $LastBucket;
        static $SnatchedTorrents = [], $UpdateTime = [];

        if (empty($SnatchedTorrents)) {
            $SnatchedTorrents = array_fill(0, $Buckets, false);
            $UpdateTime = $app->cache->get("users_snatched_{$UserID}_time");
            if ($UpdateTime === false) {
                $UpdateTime = array(
          'last' => 0,
          'next' => 0);
            }
        } elseif (isset($SnatchedTorrents[$BucketID][$TorrentID])) {
            return true;
        }

        // Torrent was not found in the previously inspected snatch lists
        $CurSnatchedTorrents = & $SnatchedTorrents[$BucketID];
        if ($CurSnatchedTorrents === false) {
            $CurTime = time();
            // This bucket hasn't been checked before
            $CurSnatchedTorrents = $app->cache->get("users_snatched_{$UserID}_$BucketID", true);
            if ($CurSnatchedTorrents === false || $CurTime > $UpdateTime['next']) {
                $Updated = [];
                $QueryID = $app->dbOld->get_query_id();
                if ($CurSnatchedTorrents === false || $UpdateTime['last'] == 0) {
                    for ($i = 0; $i < $Buckets; $i++) {
                        $SnatchedTorrents[$i] = [];
                    }
                    // Not found in cache. Since we don't have a suitable index, it's faster to update everything
                    $app->dbOld->query("
                    SELECT fid
                    FROM xbt_snatched
                      WHERE uid = ?", $UserID);
                    while (list($ID) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
                        $SnatchedTorrents[$ID & $LastBucket][(int) $ID] = true;
                    }
                    $Updated = array_fill(0, $Buckets, true);
                } elseif (isset($CurSnatchedTorrents[$TorrentID])) {
                    // Old cache, but torrent is snatched, so no need to update
                    return true;
                } else {
                    // Old cache, check if torrent has been snatched recently
                    $app->dbOld->query("
                    SELECT fid
                    FROM xbt_snatched
                      WHERE uid = ?
                      AND tstamp >= ?", $UserID, $UpdateTime['last']);
                    while (list($ID) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
                        $CurBucketID = $ID & $LastBucket;
                        if ($SnatchedTorrents[$CurBucketID] === false) {
                            $SnatchedTorrents[$CurBucketID] = $app->cache->get("users_snatched_{$UserID}_$CurBucketID", true);
                            if ($SnatchedTorrents[$CurBucketID] === false) {
                                $SnatchedTorrents[$CurBucketID] = [];
                            }
                        }
                        $SnatchedTorrents[$CurBucketID][(int) $ID] = true;
                        $Updated[$CurBucketID] = true;
                    }
                }
                $app->dbOld->set_query_id($QueryID);
                for ($i = 0; $i < $Buckets; $i++) {
                    if (isset($Updated[$i])) {
                        $app->cache->set("users_snatched_{$UserID}_$i", $SnatchedTorrents[$i], 0);
                    }
                }
                $UpdateTime['last'] = $CurTime;
                $UpdateTime['next'] = $CurTime + self::SNATCHED_UPDATE_INTERVAL;
                $app->cache->set("users_snatched_{$UserID}_time", $UpdateTime, 0);
            }
        }
        return isset($CurSnatchedTorrents[$TorrentID]);
    }


    /**
     * is_seeding
     */
    public static function is_seeding($TorrentID)
    {
        if (empty($app->user->core) || !isset($app->user->extra['ShowSnatched']) || !$app->user->extra['ShowSnatched']) {
            return false;
        }

        $UserID = $app->user->core["id"];
        $Buckets = 64;
        $LastBucket = $Buckets - 1;
        $BucketID = $TorrentID & $LastBucket;
        static $SeedingTorrents = [], $UpdateTime = [];

        if (empty($SeedingTorrents)) {
            $SeedingTorrents = array_fill(0, $Buckets, false);
            $UpdateTime = $app->cache->get("users_seeding_{$UserID}_time");
            if ($UpdateTime === false) {
                $UpdateTime = array(
          'last' => 0,
          'next' => 0);
            }
        } elseif (isset($SeedingTorrents[$BucketID][$TorrentID])) {
            return true;
        }

        // Torrent was not found in the previously inspected seeding lists
        $CurSeedingTorrents = & $SeedingTorrents[$BucketID];
        if ($CurSeedingTorrents === false) {
            $CurTime = time();
            // This bucket hasn't been checked before
            $CurSeedingTorrents = $app->cache->get("users_seeding_{$UserID}_$BucketID", true);
            if ($CurSeedingTorrents === false || $CurTime > $UpdateTime['next']) {
                $Updated = [];
                $QueryID = $app->dbOld->get_query_id();
                if ($CurSeedingTorrents === false || $UpdateTime['last'] == 0) {
                    for ($i = 0; $i < $Buckets; $i++) {
                        $SeedingTorrents[$i] = [];
                    }
                    // Not found in cache. Since we don't have a suitable index, it's faster to update everything
                    $app->dbOld->query("
                    SELECT fid
                    FROM xbt_files_users
                      WHERE uid = ?
                      AND active = 1
                      AND Remaining = 0", $UserID);
                    while (list($ID) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
                        $SeedingTorrents[$ID & $LastBucket][(int) $ID] = true;
                    }
                    $Updated = array_fill(0, $Buckets, true);
                } elseif (isset($CurSeedingTorrents[$TorrentID])) {
                    // Old cache, but torrent is seeding, so no need to update
                    return true;
                } else {
                    // Old cache, check if torrent has been seeding recently
                    $app->dbOld->query("
                    SELECT fid
                    FROM xbt_files_users
                      WHERE uid = ?
                      AND active = 1
                      AND Remaining = 0
                      AND mtime >= ?", $UserID, $UpdateTime['last']);
                    while (list($ID) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
                        $CurBucketID = $ID & $LastBucket;
                        if ($SeedingTorrents[$CurBucketID] === false) {
                            $SeedingTorrents[$CurBucketID] = $app->cache->get("users_seeding_{$UserID}_$CurBucketID", true);
                            if ($SeedingTorrents[$CurBucketID] === false) {
                                $SeedingTorrents[$CurBucketID] = [];
                            }
                        }
                        $SeedingTorrents[$CurBucketID][(int) $ID] = true;
                        $Updated[$CurBucketID] = true;
                    }
                }
                $app->dbOld->set_query_id($QueryID);
                for ($i = 0; $i < $Buckets; $i++) {
                    if (isset($Updated[$i])) {
                        $app->cache->set("users_seeding_{$UserID}_$i", $SeedingTorrents[$i], 3600);
                    }
                }
                $UpdateTime['last'] = $CurTime;
                $UpdateTime['next'] = $CurTime + self::SNATCHED_UPDATE_INTERVAL;
                $app->cache->set("users_seeding_{$UserID}_time", $UpdateTime, 3600);
            }
        }
        return isset($CurSeedingTorrents[$TorrentID]);
    }


    /**
     * is_leeching
     */
    public static function is_leeching($TorrentID)
    {
        $app = \Gazelle\App::go();

        if (empty($app->user->core) || !isset($app->user->extra['ShowSnatched']) || !$app->user->extra['ShowSnatched']) {
            return false;
        }

        $UserID = $app->user->core["id"];
        $Buckets = 64;
        $LastBucket = $Buckets - 1;
        $BucketID = $TorrentID & $LastBucket;
        static $LeechingTorrents = [], $UpdateTime = [];

        if (empty($LeechingTorrents)) {
            $LeechingTorrents = array_fill(0, $Buckets, false);
            $UpdateTime = $app->cache->get("users_leeching_{$UserID}_time");
            if ($UpdateTime === false) {
                $UpdateTime = array(
          'last' => 0,
          'next' => 0);
            }
        } elseif (isset($LeechingTorrents[$BucketID][$TorrentID])) {
            return true;
        }

        // Torrent was not found in the previously inspected snatch lists
        $CurLeechingTorrents = & $LeechingTorrents[$BucketID];
        if ($CurLeechingTorrents === false) {
            $CurTime = time();
            // This bucket hasn't been checked before
            $CurLeechingTorrents = $app->cache->get("users_leeching_{$UserID}_$BucketID", true);
            if ($CurLeechingTorrents === false || $CurTime > $UpdateTime['next']) {
                $Updated = [];
                $QueryID = $app->dbOld->get_query_id();
                if ($CurLeechingTorrents === false || $UpdateTime['last'] == 0) {
                    for ($i = 0; $i < $Buckets; $i++) {
                        $LeechingTorrents[$i] = [];
                    }
                    // Not found in cache. Since we don't have a suitable index, it's faster to update everything
                    $app->dbOld->query("
                    SELECT fid
                    FROM xbt_files_users
                      WHERE uid = ?
                      AND active = 1
                      AND Remaining > 0", $UserID);
                    while (list($ID) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
                        $LeechingTorrents[$ID & $LastBucket][(int) $ID] = true;
                    }
                    $Updated = array_fill(0, $Buckets, true);
                } elseif (isset($CurLeechingTorrents[$TorrentID])) {
                    // Old cache, but torrent is leeching, so no need to update
                    return true;
                } else {
                    // Old cache, check if torrent has been leeching recently
                    $app->dbOld->query("
                    SELECT fid
                    FROM xbt_files_users
                      WHERE uid = ?
                      AND active = 1
                      AND Remaining > 0
                      AND mtime >= ?", $UserID, $UpdateTime['last']);
                    while (list($ID) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
                        $CurBucketID = $ID & $LastBucket;
                        if ($LeechingTorrents[$CurBucketID] === false) {
                            $LeechingTorrents[$CurBucketID] = $app->cache->get("users_leeching_{$UserID}_$CurBucketID", true);
                            if ($LeechingTorrents[$CurBucketID] === false) {
                                $LeechingTorrents[$CurBucketID] = [];
                            }
                        }
                        $LeechingTorrents[$CurBucketID][(int) $ID] = true;
                        $Updated[$CurBucketID] = true;
                    }
                }
                $app->dbOld->set_query_id($QueryID);
                for ($i = 0; $i < $Buckets; $i++) {
                    if (isset($Updated[$i])) {
                        $app->cache->set("users_leeching_{$UserID}_$i", $LeechingTorrents[$i], 3600);
                    }
                }
                $UpdateTime['last'] = $CurTime;
                $UpdateTime['next'] = $CurTime + self::SNATCHED_UPDATE_INTERVAL;
                $app->cache->set("users_leeching_{$UserID}_time", $UpdateTime, 3600);
            }
        }
        return isset($CurLeechingTorrents[$TorrentID]);
    }


    /**
     * set_snatch_update_time
     *
     * Change the schedule for when the next update to a user's cached snatch list should be performed.
     * By default, the change will only be made if the new update would happen sooner than the current
     * @param int $Time Seconds until the next update
     * @param bool $Force Whether to accept changes that would push back the update
     */
    public static function set_snatch_update_time($UserID, $Time, $Force = false)
    {
        $app = \Gazelle\App::go();

        if (!$UpdateTime = $app->cache->get("users_snatched_{$UserID}_time")) {
            return;
        }
        $NextTime = time() + $Time;
        if ($Force || $NextTime < $UpdateTime['next']) {
            // Skip if the change would delay the next update
            $UpdateTime['next'] = $NextTime;
            $app->cache->set("users_snatched_{$UserID}_time", $UpdateTime, 0);
        }
    }


    /**
     * get_reports
     *
     * Used to get reports info on a unison cache in both browsing pages and torrent pages.
     */
    public static function get_reports($TorrentID)
    {
        $app = \Gazelle\App::go();

        $Reports = $app->cache->get("reports_torrent_$TorrentID");
        if ($Reports === false) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT
              ID,
              ReporterID,
              Type,
              UserComment,
              ReportedTime
            FROM reportsv2
              WHERE TorrentID = ?
              AND Status != 'Resolved'", $TorrentID);
            $Reports = $app->dbOld->to_array(false, MYSQLI_ASSOC, false);
            $app->dbOld->set_query_id($QueryID);
            $app->cache->set("reports_torrent_$TorrentID", $Reports, 0);
        }
        if ($app->user->cant(["admin" => "reports"])) {
            $Return = [];
            foreach ($Reports as $Report) {
                if ($Report['Type'] !== 'edited') {
                    $Return[] = $Report;
                }
            }
            return $Return;
        }
        return $Reports;
    }


    /**
     * displayPeers
     *
     * @see gazelle/sections/torrents/peerlist.php
     */
    public static function displayPeers()
    {
        # todo
    }


    /**
     * displaySnatches
     *
     * @see gazelle/sections/torrents/snatchlist.php
     */
    public static function displaySnatches()
    {
        # todo
    }


    /**
     * displayDownloads
     *
     * @see gazelle/sections/torrents/downloadlist.php
     */
    public static function displayDownloads()
    {
        # todo
    }
} # class

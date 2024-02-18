<?php

#declare(strict_types=1);


/**
 * Torrents
 */

class Torrents
{
    # object properties
    public $uuid;
    public $id;
    public $groupId;
    public $userId;
    public $platform;
    public $format;
    public $license;
    public $scope;
    public $version;
    public $aligned;
    public $anonymous;
    public $infoHash;
    public $fileCount;
    public $fileList;
    public $filePath;
    public $dataSize;
    public $leecherCount;
    public $seederCount;
    public $lastAction;
    public $freeleech;
    public $freeleechType;
    #public $createdAt;
    public $description;
    public $snatchCount;
    public $balance;
    public $lastReseedRequest;
    public $archive;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    # ["database" => "display"]
    protected array $maps = [
        "uuid" => "uuid",
        "ID" => "id",
        "GroupID" => "groupId",
        "UserID" => "userId",
        "media" => "platform",
        "container" => "format",
        "codec" => "license",
        "resolution" => "scope",
        "version" => "version",
        "Censored" => "aligned",
        "Anonymous" => "anonymous",
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
        "Time" => "createdAt",
        "Description" => "description",
        "Snatched" => "snatchCount",
        "balance" => "balance",
        "LastReseedRequest" => "lastReseedRequest",
        "archive" => "archive",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    # hex for ÷, must be the same as phrase_boundary in manticore.conf
    public const FILELIST_DELIM = 0xF7;

    # how often we want to update users' snatch lists
    public const SNATCHED_UPDATE_INTERVAL = 3600;

    # how long after a torrent download we want to update a user's snatch lists
    public const SNATCHED_UPDATE_AFTERDL = 300;

    // Some constants for self::display_string's $Mode parameter
    public const DISPLAYSTRING_ARTISTS = 2; // Whether or not to display artists
    public const DISPLAYSTRING_YEAR = 4; // Whether or not to display the group's year
    public const DISPLAYSTRING_RELEASETYPE = 16; // Whether or not to display the release type
    public const DISPLAYSTRING_LINKED = 33; // Whether or not to link artists and the group
    // The constant for linking is 32, but because linking only works with HTML, this constant is defined as 32|1 = 33, i.e. LINKED also includes HTML
    // Keep this in mind when defining presets below!

    // Presets to facilitate the use of $Mode
    public const DISPLAYSTRING_DEFAULT = 63; // HTML|ARTISTS|YEAR|VH|RELEASETYPE|LINKED = 63


    /**
     * __construct
     */
    public function __construct(int|string $identifier = null)
    {
        if ($identifier) {
            $this->read($identifier);
        }
    }


    /** crud */


    /**
     * create
     */
    public function create(array $data = [])
    {
        throw new \Exception("not implemented");
    }


    /**
     * read
     */
    public function read(int|string $identifier)
    {
        $app = \Gazelle\App::go();

        $column = $app->dbNew->determineIdentifier($identifier);

        $query = "select * from torrents where {$column} = ?";
        $row = $app->dbNew->row($query, [$identifier]);

        if (empty($row)) {
            return [];
        }

        $translatedRow = [];
        foreach ($row as $column => $value) {
            # does the column exist in the map?
            if (isset($this->maps[$column])) {
                $outputLabel = $this->maps[$column];
                $translatedRow[$outputLabel] = $value;
            }
        }

        return $translatedRow;
    }


    /**
     * update
     */
    public function update(int|string $identifier, array $data = [])
    {
        throw new \Exception("not implemented");
    }


    /**
     * delete
     */
    public function delete(int|string $identifier)
    {
        throw new \Exception("not implemented");
    }


    /** legacy */


    /**
     * getGroupsForReal
     *
     * todo: finish this later
     */
    public static function getGroupsForReal(array $groupIds): array
    {
        $app = \Gazelle\App::go();

        if (empty($groupIds)) {
            return [];
        }

        $data = [];

        # escape just in case, because `in()` can't into prepared queries
        foreach ($groupIds as $key => $value) {
            $groupIds[$key] = \Gazelle\Esc::int($value);
        }

        $groupIds = array_filter($groupIds);
        $groupIds = implode(", ", $groupIds);

        $query = "
            select id, category_id, title, subject, object, year,
                workgroup, location, identifier, tag_list, timestamp, picture
            from torrents_group where id in({$groupIds})
        ";
        $ref = $app->dbNew->multi($query, []);
        $data["torrentGroups"] = $ref;
        #!d($ref);exit;

        # now do the torrents themselves
        $query = "
            select id, groupId, userId, media, container, codec, resolution,
                version, censored, anonymous, hex(info_hash) as infoHash, fileCount,
                size, leechers, seeders, freeTorrent, time, snatched, archive, shop_freeleeches.expiryTime
            from torrents left join shop_freeleeches on shop_freeleeches.torrentId = torrents.id
            where groupId in({$groupIds})
        ";
        $ref = $app->dbNew->multi($query, []);
        $data["torrents"] = $ref;
        #!d($ref);exit;

        # now the creators
        $data["creators"] = Artists::get_artists($groupIds) ?? [];

        return $data;
    }


    /**
      * get_groups
      *
      * Function to get data and torrents for an array of GroupIDs. Order of keys doesn't matter
      *
      * @param array $GroupIDs
      * @param boolean $Return if false, nothing is returned. For priming cache.
      * @param boolean $GetArtists if true, each group will contain the result of
      *  Artists::get_artists($GroupID), in result[$GroupID]['ExtendedArtists']
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
      *    [1-6] => { // See documentation on Artists::get_artists
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
              `tag_list`,
              `picture`,
              `category_id`
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
                  `ID`,
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
                  `ID` AS `HasFile`,
                  `FreeLeechType`,
                  HEX(`info_hash`) AS `info_hash`
                FROM
                  `torrents`
                LEFT JOIN `shop_freeleeches` AS f
                ON
                  f.`TorrentID` = `ID`
                WHERE
                  `GroupID` IN($IDs)
                ORDER BY
                  `GroupID`,
                  `Media`,
                  `Container`,
                  `Codec`,
                  `ID`
                ");

                while ($Torrent = $app->dbOld->next_record(MYSQLI_ASSOC, true)) {
                    $NotFound[$Torrent['GroupID']]['Torrents'][$Torrent['ID']] = $Torrent;
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
            $Artists = \Artists::get_artists($GroupIDs);
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
     * @example  extract(Torrents::array_group($SomeGroup));
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
          'category_id' => $Group['category_id'],
          'identifier' => $Group['identifier'],
          'workgroup' => $Group['workgroup'],
          'location' => $Group['location'],
          'GroupFlags' => ($Group['Flags'] ?? ''),
          'tag_list' => $Group['tag_list'],
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
     * @param array $Torrent torrent array preferably in the form used by Torrents::get_groups() or TorrentFunctions::get_group_info()
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
        Tracker::update_tracker('delete_torrent', array('info_hash' => rawurlencode($InfoHash), 'id' => $ID, 'reason' => $OcelotReason));

        $app->cache->decrement('stats_torrent_count');

        $app->dbOld->query("
        SELECT COUNT(ID)
        FROM torrents
          WHERE GroupID = '$GroupID'");
        list($Count) = $app->dbOld->next_record();

        if ($Count == 0) {
            Torrents::delete_group($GroupID);
        } else {
            Torrents::update_hash($GroupID);
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
          `category_id`
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
                Artists::delete_artist($ArtistID);
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
        Comments::delete_page('torrents', $GroupID);

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

        $app->dbOld->prepared_query("
        UPDATE
          `torrents_group`
        SET
          `tag_list` =(
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

        $ArtistInfo = \Artists::get_artist($GroupID);
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
            $Info[] = $HTMLy ? Format::torrent_label('Leeching', 'important_text_semi') : 'Leeching';
        } elseif ($Data['IsSeeding']) {
            $Info[] = $HTMLy ? Format::torrent_label('Seeding', 'important_text_alt') : 'Seeding';
        } elseif ($Data['IsSnatched']) {
            $Info[] = $HTMLy ? Format::torrent_label('Snatched', 'bold') : 'Snatched';
        }

        if ($Data['FreeTorrent'] === '1') {
            if ($Data['FreeLeechType'] === '3') {
                if ($Data['ExpiryTime']) {
                    $Info[] = ($HTMLy ? Format::torrent_label('Freeleech', 'important_text_alt') : 'Freeleech') . ($HTMLy ? " <strong>(" : " (") . str_replace(['month','week','day','hour','min','s'], ['m','w','d','h','m',''], time_diff(max(strtotime($Data['ExpiryTime']), time()), 1, false)) . ($HTMLy ? ")</strong>" : ")");
                } else {
                    $Info[] = $HTMLy ? Format::torrent_label('Freeleech', 'important_text_alt') : 'Freeleech';
                }
            } else {
                $Info[] = $HTMLy ? Format::torrent_label('Freeleech', 'important_text_alt') : 'Freeleech';
            }
        }

        if ($Data['FreeTorrent'] == '2') {
            $Info[] = $HTMLy ? Format::torrent_label('Neutral Leech', 'bold') : 'Neutral Leech';
        }

        $Data['PersonalFL'] ??= null;
        if ($Data['PersonalFL']) {
            $Info[] = $HTMLy ? Format::torrent_label('Personal Freeleech', 'important_text_alt') : 'Personal Freeleech';
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
            Tracker::update_tracker('update_torrent', array('info_hash' => rawurlencode($InfoHash), 'freetorrent' => $FreeNeutral));
            $app->cache->delete("torrent_download_$TorrentID");
            Misc::write_log(($app->user->core["username"] ?? 'System') . " marked torrent $TorrentID freeleech type $FreeLeechType");
            Torrents::write_group_log($GroupID, $TorrentID, ($app->user->core["id"] ?? 0), "marked as freeleech type $FreeLeechType", 0);

            if ($Announce && ($FreeLeechType === 1 || $FreeLeechType === 3)) {
                # todo: fsockopen(): Unable to connect to 10.10.10.60:51010 (Connection refused)
                #send_irc(ANNOUNCE_CHAN, 'FREELEECH - '.site_url()."torrents.php?id=$GroupID / ".site_url()."torrents.php?action=download&id=$TorrentID");
            }
        }

        foreach ($GroupIDs as $GroupID) {
            Torrents::update_hash($GroupID);
        }
    }


    /**
     * freeleech_groups
     *
     * Convenience function to allow for passing groups to Torrents::freeleech_torrents()
     *
     * @param array $GroupIDs the groups in question
     * @param int $FreeNeutral see Torrents::freeleech_torrents()
     * @param int $FreeLeechType see Torrents::freeleech_torrents()
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
            Torrents::freeleech_torrents($TorrentIDs, $FreeNeutral, $FreeLeechType);
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
     * display_string
     *
     * Return the display string for a given torrent group $GroupID.
     * @param int $GroupID
     * @return string
     */
    public static function display_string($GroupID, $Mode = self::DISPLAYSTRING_DEFAULT)
    {
        $GroupInfo = self::get_groups(array($GroupID), true, true, false)[$GroupID];
        $ExtendedArtists = $GroupInfo['ExtendedArtists'];

        if ($Mode & self::DISPLAYSTRING_ARTISTS) {
            if (!empty($ExtendedArtists[1])
        || !empty($ExtendedArtists[4])
        || !empty($ExtendedArtists[5])
        || !empty($ExtendedArtists[6])
            ) {
                unset($ExtendedArtists[2], $ExtendedArtists[3]);
                $DisplayName = \Artists::display_artists($ExtendedArtists, ($Mode & self::DISPLAYSTRING_LINKED));
            } else {
                $DisplayName = '';
            }
        }

        if ($Mode & self::DISPLAYSTRING_LINKED) {
            $DisplayName .= "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"View torrent group\" dir=\"ltr\">$GroupInfo[Name]</a>";
        } else {
            $DisplayName .= $GroupInfo['Name'];
        }

        if (($Mode & self::DISPLAYSTRING_YEAR) && $GroupInfo['Year'] > 0) {
            $DisplayName .= " [$GroupInfo[Year]]";
        }

        if (($Mode & self::DISPLAYSTRING_RELEASETYPE) && $GroupInfo['ReleaseType'] > 0) {
            $DisplayName .= ' [' . $ReleaseTypes[$GroupInfo['ReleaseType']] . ']';
        }

        return $DisplayName;
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
        if (!check_perms('admin_reports')) {
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
} # class

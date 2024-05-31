<?php

#declare(strict_types=1);


/**
 * TorrentFunctions
 *
 * Previously included in sections/torrents/functions.php.
 * Temporarily contained in a static class for now.
 */

class TorrentFunctions
{
    /**
     * get_group_info
     */
    public static function get_group_info($GroupID, $Return = true, $RevisionID = 0, $PersonalProperties = true, $ApiCall = false)
    {
        $app = \Gazelle\App::go();

        if (!$RevisionID) {
            $TorrentCache = $app->cache->get("torrents_details_$GroupID");
        }

        if ($RevisionID || !is_array($TorrentCache)) {
            // Fetch the group details
            $SQL = 'SELECT ';

            if (!$RevisionID) {
                $SQL .= '
            g.`description`,
            g.`picture`, ';
            } else {
                $SQL .= '
            w.`Body`,
            w.`Image`, ';
            }

            $SQL .= "
          g.`id`,
          g.`title`,
          g.`subject`,
          g.`object`,
          g.`year`,
          g.`workgroup`,
          g.`location`,
          g.`identifier`,
          g.`categoryId`,
            GROUP_CONCAT(DISTINCT tags.`Name` SEPARATOR '|'),
            GROUP_CONCAT(DISTINCT tags.`ID` SEPARATOR '|'),
            GROUP_CONCAT(tt.`UserID` SEPARATOR '|')
          FROM `torrents_group` AS g
            LEFT JOIN `torrents_tags` AS tt ON tt.`GroupID` = g.`id`
            LEFT JOIN `tags` ON tags.`ID` = tt.`TagID`";

            if ($RevisionID) {
                $SQL .= "
              LEFT JOIN `wiki_torrents` AS w ON w.`PageID` = '$GroupID'
                AND w.`RevisionID` = '$RevisionID' ";
            }

            $SQL .= "
          WHERE g.`id` = '$GroupID'
            GROUP BY NULL";

            $app->dbOld->prepared_query($SQL);
            $TorrentDetails = $app->dbOld->next_record(MYSQLI_ASSOC);
            $TorrentDetails['Screenshots'] = [];
            $TorrentDetails['Mirrors'] = [];

            /*
            # Screenshots (Publications)
            $app->dbOld->query("
        SELECT
          `id`,
          `userId`,
          `doi`
        FROM
          `literature`
        WHERE
          `groupId` = '$GroupID'
        ");

            if ($app->dbOld->has_results()) {
                while ($Screenshot = $app->dbOld->next_record(MYSQLI_ASSOC, true)) {
                    $TorrentDetails['Screenshots'][] = $Screenshot;
                }
            }
*/

            # Mirrors
            # todo: Fix $GroupID
            $app->dbOld->query("
        SELECT
          `id`,
          `user_id`,
          `uri`
        FROM
          `torrents_mirrors`
        WHERE
          `torrent_id` = '$GroupID'
        ");

            if ($app->dbOld->has_results()) {
                while ($Mirror = $app->dbOld->next_record(MYSQLI_ASSOC, true)) {
                    $TorrentDetails['Mirrors'][] = $Mirror;
                }
            }

            // Fetch the individual torrents
            $app->dbOld->query("
        SELECT
          t.id,
          t.Media,
          t.Container,
          t.Codec,
          t.Resolution,
          t.Version,
          t.Censored,
          t.Anonymous,
          t.Archive,
          t.FileCount,
          t.Size,
          t.Seeders,
          t.Leechers,
          t.Snatched,
          t.FreeTorrent,
          t.FreeLeechType,
          t.Time,
          t.Description,
          t.FileList,
          t.FilePath,
          t.UserID,
          t.last_action,
          HEX(t.info_hash) AS InfoHash,
          tbt.TorrentID AS BadTags,
          tbf.TorrentID AS BadFolders,
          tfi.TorrentID AS BadFiles,
          t.LastReseedRequest,
          t.id AS HasFile
        FROM torrents AS t
          LEFT JOIN torrents_bad_tags AS tbt ON tbt.TorrentID = t.id
          LEFT JOIN torrents_bad_folders AS tbf ON tbf.TorrentID = t.id
          LEFT JOIN torrents_bad_files AS tfi ON tfi.TorrentID = t.id
        WHERE t.GroupID = '" . db_string($GroupID) . "'
        GROUP BY t.id
        ORDER BY
          t.Media ASC,
          t.id");

            $TorrentList = $app->dbOld->to_array('ID', MYSQLI_ASSOC);
            if (count($TorrentList) === 0 && $ApiCall == false) {
                header('Location: log.php?search=' . (empty($_GET['torrentid']) ? "Group+$GroupID" : "Torrent+$_GET[torrentid]"));
                error();
            } elseif (count($TorrentList) === 0 && $ApiCall == true) {
                return;
            }

            /*
            if (in_array(0, $app->dbOld->collect('Seeders'))) {
                $app->cacheOldTime = 600;
            } else {
                $app->cacheOldTime = 3600;
            }
            */

            // Store it all in cache
            if (!$RevisionID) {
                $app->cache->set("torrents_details_$GroupID", array($TorrentDetails, $TorrentList), 600);
                #$app->cache->set("torrents_details_$GroupID", array($TorrentDetails, $TorrentList), $app->cacheOldTime);
            }
        } else { // If we're reading from cache
            $TorrentDetails = $TorrentCache[0];
            $TorrentList = $TorrentCache[1];
        }

        if ($PersonalProperties) {
            // Fetch all user specific torrent and group properties
            $TorrentDetails['Flags'] = array('IsSnatched' => false, 'IsLeeching' => false, 'IsSeeding' => false);
            foreach ($TorrentList as &$Torrent) {
                \Gazelle\Torrents::torrent_properties($Torrent, $TorrentDetails['Flags']);
            }
        }

        if ($Return) {
            return array($TorrentDetails, $TorrentList);
        }
    }


    /**
     * get_torrent_info
     */
    public static function get_torrent_info($TorrentID, $Return = true, $RevisionID = 0, $PersonalProperties = true, $ApiCall = false)
    {
        $app = \Gazelle\App::go();

        $GroupID = (int) self::orrentid_to_groupid($TorrentID);
        $GroupInfo = get_group_info($GroupID, $Return, $RevisionID, $PersonalProperties, $ApiCall);
        if ($GroupInfo) {
            foreach ($GroupInfo[1] as &$Torrent) {
                // Remove unneeded entries
                if ($Torrent['ID'] !== $TorrentID) {
                    unset($GroupInfo[1][$Torrent['ID']]);
                }

                if ($Return) {
                    return $GroupInfo;
                }
            }
        } else {
            if ($Return) {
                return;
            }
        }
    }


    /**
     * is_valid_torrenthash
     */
    // Check if a givin string can be validated as a torrenthash
    public static function is_valid_torrenthash($Str)
    {
        // 6C19FF4C 6C1DD265 3B25832C 0F6228B2 52D743D5
        $Str = str_replace(' ', '', $Str);
        if (preg_match('/^[0-9a-fA-F]{40}$/', $Str)) {
            return $Str;
        }
        return false;
    }
} # class

<?php

# Line 3
function get_group_info($GroupID, $Return = true, $RevisionID = 0, $PersonalProperties = true, $ApiCall = false)
{
    global $Cache, $DB;
    if (!$RevisionID) {
        $TorrentCache = $Cache->get_value("torrents_details_$GroupID");
    }
    if ($RevisionID || !is_array($TorrentCache)) {
        // Fetch the group details

        $SQL = 'SELECT ';

        if (!$RevisionID) {
            $SQL .= '
        g.WikiBody,
        g.WikiImage, ';
        } else {
            $SQL .= '
        w.Body,
        w.Image, ';
        }

        $SQL .= "
        g.ID,
        g.Name,
        g.NameRJ,
        g.NameJP,
        g.Year,
        g.Studio,
        g.Series,
        g.CatalogueNumber,
        g.Pages,
        g.CategoryID,
        g.DLsiteID,
        g.Time,
        GROUP_CONCAT(DISTINCT tags.Name SEPARATOR '|'),
        GROUP_CONCAT(DISTINCT tags.ID SEPARATOR '|'),
        GROUP_CONCAT(tt.UserID SEPARATOR '|')
      FROM torrents_group AS g
        LEFT JOIN torrents_tags AS tt ON tt.GroupID = g.ID
        LEFT JOIN tags ON tags.ID = tt.TagID";

        if ($RevisionID) {
            $SQL .= "
        LEFT JOIN wiki_torrents AS w ON w.PageID = '".db_string($GroupID)."'
            AND w.RevisionID = '".db_string($RevisionID)."' ";
        }

        $SQL .= "
      WHERE g.ID = '".db_string($GroupID)."'
      GROUP BY NULL";

        $DB->query($SQL);

        $TorrentDetails = $DB->next_record(MYSQLI_ASSOC);
        $TorrentDetails['Screenshots'] = [];
        $TorrentDetails['Mirrors'] = [];

        # Screenshots (Publications)
        $DB->query("
      SELECT
        ID, UserID, Time, Image
      FROM torrents_screenshots
      WHERE GroupID = ".db_string($GroupID));

        if ($DB->has_results()) {
            while ($Screenshot = $DB->next_record(MYSQLI_ASSOC, true)) {
                $TorrentDetails['Screenshots'][] = $Screenshot;
            }
        }

        # Mirrors
        $DB->query("
        SELECT
          ID, UserID, Time, Resource
        FROM torrents_mirrors
        WHERE GroupID = ".db_string($GroupID));
  
        if ($DB->has_results()) {
            while ($Mirror = $DB->next_record(MYSQLI_ASSOC, true)) {
                $TorrentDetails['Mirrors'][] = $Mirror;
            }
        }
  
        // Fetch the individual torrents
        $DB->query("
      SELECT
        t.ID,
        t.Media,
        t.Container,
        t.Codec,
        t.Resolution,
        t.AudioFormat,
        t.Subbing,
        t.Subber,
        t.Language,
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
        t.MediaInfo,
        t.FileList,
        t.FilePath,
        t.UserID,
        t.last_action,
        HEX(t.info_hash) AS InfoHash,
        tbt.TorrentID AS BadTags,
        tbf.TorrentID AS BadFolders,
        tfi.TorrentID AS BadFiles,
        t.LastReseedRequest,
        tln.TorrentID AS LogInDB,
        t.ID AS HasFile
      FROM torrents AS t
        LEFT JOIN torrents_bad_tags AS tbt ON tbt.TorrentID = t.ID
        LEFT JOIN torrents_bad_folders AS tbf ON tbf.TorrentID = t.ID
        LEFT JOIN torrents_bad_files AS tfi ON tfi.TorrentID = t.ID
        LEFT JOIN torrents_logs_new AS tln ON tln.TorrentID = t.ID
      WHERE t.GroupID = '".db_string($GroupID)."'
      GROUP BY t.ID
      ORDER BY
        t.Media ASC,
        t.ID");

        $TorrentList = $DB->to_array('ID', MYSQLI_ASSOC);
        if (count($TorrentList) === 0 && $ApiCall == false) {
            header('Location: log.php?search='.(empty($_GET['torrentid']) ? "Group+$GroupID" : "Torrent+$_GET[torrentid]"));
            die();
        } elseif (count($TorrentList) === 0 && $ApiCall == true) {
            return null;
        }
        if (in_array(0, $DB->collect('Seeders'))) {
            $CacheTime = 600;
        } else {
            $CacheTime = 3600;
        }
        // Store it all in cache
        if (!$RevisionID) {
            $Cache->cache_value("torrents_details_$GroupID", array($TorrentDetails, $TorrentList), $CacheTime);
        }
    } else { // If we're reading from cache
        $TorrentDetails = $TorrentCache[0];
        $TorrentList = $TorrentCache[1];
    }

    if ($PersonalProperties) {
        // Fetch all user specific torrent and group properties
        $TorrentDetails['Flags'] = array('IsSnatched' => false, 'IsLeeching' => false, 'IsSeeding' => false);
        foreach ($TorrentList as &$Torrent) {
            Torrents::torrent_properties($Torrent, $TorrentDetails['Flags']);
        }
    }

    if ($Return) {
        return array($TorrentDetails, $TorrentList);
    }
}
# Line 165
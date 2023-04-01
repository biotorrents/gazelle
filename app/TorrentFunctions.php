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
          g.`category_id`,
          g.`timestamp`,
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

            # Screenshots (Publications)
            $app->dbOld->query("
        SELECT
          `id`,
          `user_id`,
          `timestamp`,
          `doi`
        FROM
          `literature`
        WHERE
          `group_id` = '$GroupID'
        ");

            if ($app->dbOld->has_results()) {
                while ($Screenshot = $app->dbOld->next_record(MYSQLI_ASSOC, true)) {
                    $TorrentDetails['Screenshots'][] = $Screenshot;
                }
            }

            # Mirrors
            # todo: Fix $GroupID
            $app->dbOld->query("
        SELECT
          `id`,
          `user_id`,
          `timestamp`,
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
          t.ID,
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

            $TorrentList = $app->dbOld->to_array('ID', MYSQLI_ASSOC);
            if (count($TorrentList) === 0 && $ApiCall == false) {
                header('Location: log.php?search='.(empty($_GET['torrentid']) ? "Group+$GroupID" : "Torrent+$_GET[torrentid]"));
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
                Torrents::torrent_properties($Torrent, $TorrentDetails['Flags']);
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

        $GroupID = (int)self::orrentid_to_groupid($TorrentID);
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


    /**
     * torrenthash_to_torrentid
     */
    // Functionality for the API to resolve input into other data
    public static function torrenthash_to_torrentid($Str)
    {
        $app = \Gazelle\App::go();

        $app->dbOld->query("
      SELECT ID
      FROM torrents
      WHERE HEX(info_hash) = '".db_string($Str)."'");

        $TorrentID = (int)array_pop($app->dbOld->next_record(MYSQLI_ASSOC));
        if ($TorrentID) {
            return $TorrentID;
        }
        return;
    }


    /**
     * torrenthash_to_groupid
     */
    public static function torrenthash_to_groupid($Str)
    {
        $app = \Gazelle\App::go();

        $app->dbOld->query("
      SELECT GroupID
      FROM torrents
      WHERE HEX(info_hash) = '".db_string($Str)."'");

        $GroupID = (int)array_pop($app->dbOld->next_record(MYSQLI_ASSOC));
        if ($GroupID) {
            return $GroupID;
        }
        return;
    }


    /**
     * torrentid_to_groupid
     */
    public static function torrentid_to_groupid($TorrentID)
    {
        $app = \Gazelle\App::go();

        $app->dbOld->query("
      SELECT GroupID
      FROM torrents
      WHERE ID = '".db_string($TorrentID)."'");

        $GroupID = (int)array_pop($app->dbOld->next_record(MYSQLI_ASSOC));
        if ($GroupID) {
            return $GroupID;
        }
        return;
    }


    /**
     * set_torrent_logscore
     */
    // After adjusting / deleting logs, recalculate the score for the torrent
    public static function set_torrent_logscore($TorrentID)
    {
        $app = \Gazelle\App::go();

        $app->dbOld->query("
      UPDATE torrents
      SET LogScore = (
        SELECT FLOOR(AVG(Score))
        FROM torrents_logs_new
        WHERE TorrentID = $TorrentID
        )
      WHERE ID = $TorrentID");
    }


    /**
     * get_group_requests
     */
    public static function get_group_requests($GroupID)
    {
        $app = \Gazelle\App::go();

        if (empty($GroupID) || !is_numeric($GroupID)) {
            return [];
        }

        $Requests = $app->cache->get("requests_group_$GroupID");
        if ($Requests === false) {
            $app->dbOld->query("
          SELECT ID
          FROM requests
          WHERE GroupID = $GroupID
            AND TimeFilled IS NULL");

            $Requests = $app->dbOld->collect('ID');
            $app->cache->set("requests_group_$GroupID", $Requests, 0);
        }
        return Requests::get_requests($Requests);
    }


    /**
     * build_torrents_table
     */
    // Used by both sections/torrents/details.php and sections/reportsv2/report.php
    public static function build_torrents_table($user, $GroupID, $GroupName, $GroupCategoryID, $TorrentList, $Types, $Username)
    {
        $app = \Gazelle\App::go();

        /*
        function filelist($Str)
        {
            return "</td>\n<td>" . Format::get_size($Str[1]) . "</td>\n</tr>";
        }
        */

        $EditionID = 0;
        foreach ($TorrentList as $Torrent) {
            list($TorrentID, $Media, $Container, $Codec, $Resolution, $Version,
                $Censored, $Anonymous, $Archive, $FileCount, $Size, $Seeders, $Leechers, $Snatched,
                $FreeTorrent, $FreeLeechType, $TorrentTime, $Description, $FileList, $FilePath, $UserID,
                $LastActive, $InfoHash, $BadTags, $BadFolders, $BadFiles, $LastReseedRequest,
                $LogInDB, $HasFile, $PersonalFL, $IsSnatched, $IsSeeding, $IsLeeching) = array_values($Torrent);

            $Reported = false;
            $Reports = Torrents::get_reports($TorrentID);
            $NumReports = count($Reports);

            if ($NumReports > 0) {
                $Reported = true;
                include(serverRoot.'/sections/reportsv2/array.php');
                $ReportInfo = '
            <table class="reportinfo_table">
              <tr class="colhead_dark" style="font-weight: bold;">
                <td>This torrent has '.$NumReports.' active '.($NumReports === 1 ? 'report' : 'reports').":</td>
              </tr>";

                foreach ($Reports as $Report) {
                    if (check_perms('admin_reports')) {
                        $ReporterID = $Report['ReporterID'];
                        $Reporter = User::user_info($ReporterID);
                        $ReporterName = $Reporter['Username'];
                        $ReportLinks = "<a href=\"user.php?id=$ReporterID\">$ReporterName</a> <a href=\"reportsv2.php?view=report&amp;id=$Report[ID]\">reported it</a>";
                    } else {
                        $ReportLinks = 'Someone reported it';
                    }

                    if (isset($Types[$GroupCategoryID][$Report['Type']])) {
                        $ReportType = $Types[$GroupCategoryID][$Report['Type']];
                    } elseif (isset($Types['master'][$Report['Type']])) {
                        $ReportType = $Types['master'][$Report['Type']];
                    } else {
                        // There was a type but it wasn't an option!
                        $ReportType = $Types['master']['other'];
                    }

                    $ReportInfo .= "
                <tr>
                  <td>$ReportLinks ".time_diff($Report['ReportedTime'], 2, true, true).' for the reason "'.$ReportType['title'].'":
                    <blockquote>'.Text::parse($Report['UserComment']).'</blockquote>
                  </td>
                </tr>';
                }
                $ReportInfo .= "</table>";
            }

            $CanEdit = (check_perms('torrents_edit') || (($UserID == $user['ID'] && !$user['DisableWiki'])));
            $RegenLink = check_perms('users_mod') ? ' <a href="torrents.php?action=regen_filelist&amp;torrentid=' . $TorrentID . '" class="brackets">Regenerate</a>' : '';

            $FileTable = '
        <table class="filelist_table">
          <tr class="colhead_dark">
            <td>
              <div class="filelist_title u-pull-left">File Names' . $RegenLink . '</div>
              <div class="filelist_path u-pull-right">' . ($FilePath ? "/$FilePath/" : '') . '</div>
            </td>
            <td>
              <strong>Size</strong>
            </td>
          </tr>';

            if (substr($FileList, -3) == '}}}') { // Old style
                $FileListSplit = explode('|||', $FileList);

                foreach ($FileListSplit as $File) {
                    $NameEnd = strrpos($File, '{{{');
                    $Name = substr($File, 0, $NameEnd);

                    if ($Spaces = strspn($Name, ' ')) {
                        $Name = str_replace(' ', '&nbsp;', substr($Name, 0, $Spaces)) . substr($Name, $Spaces);
                    }

                    $FileSize = substr($File, $NameEnd + 3, -3);
                    $FileTable .= sprintf("\n<tr class=\"row\"><td>%s</td><td class=\"number_column\">%s</td></tr>", $Name, Format::get_size($FileSize));
                }
            } else {
                $FileListSplit = explode("\n", $FileList);
                foreach ($FileListSplit as $File) {
                    $FileInfo = Torrents::filelist_get_file($File);
                    $FileTable .= sprintf("\n<tr class=\"row\"><td>%s</td><td class=\"number_column\">%s</td></tr>", $FileInfo['name'], Format::get_size($FileInfo['size']));
                }
            }
            $FileTable .= '</table>';

            $ExtraInfo = ''; // String that contains information on the torrent (e.g. format and encoding)
            $AddExtra = '&thinsp;|&thinsp;'; // Separator between torrent properties

            $TorrentUploader = $Username; // Save this for "Uploaded by:" below
            // Similar to Torrents::torrent_info()
            if (!$ExtraInfo) {
                $ExtraInfo = $GroupName;
            }

            if ($IsLeeching) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Leeching', 'important_text_semi');
            } elseif ($IsSeeding) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Seeding', 'important_text_alt');
            } elseif ($IsSnatched) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Snatched', 'bold');
            }

            if ($FreeTorrent === 1) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Freeleech', 'important_text_alt');
            }

            if ($FreeTorrent === 2) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Neutral Leech', 'bold');
            }

            if ($PersonalFL) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Personal Freeleech', 'important_text_alt');
            }

            if ($Reported) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Reported', 'tl_reported');
            }

            if (!empty($BadTags)) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Bad Tags', 'tl_reported');
            }

            if (!empty($BadFolders)) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Bad Folders', 'tl_reported');
            }

            if (!empty($BadFiles)) {
                $ExtraInfo .= $AddExtra.Format::torrent_label('Bad File Names', 'tl_reported');
            } ?>

<tr class="torrent_row<?=(isset($ReleaseType) ? ' releases_'.$ReleaseType : '')?> groupid_<?=($GroupID)?> edition_<?=($EditionID)?> group_torrent<?=($IsSnatched ? ' snatched_torrent' : '')?>"
    style="font-weight: normal;" id="torrent<?=($TorrentID)?>">
    <td>
        <span>[ <a
                href="torrents.php?action=download&amp;id=<?=($TorrentID)?>&amp;authkey=<?=($user['AuthKey'])?>&amp;torrent_pass=<?=($user['torrent_pass'])?>"
                class="tooltip" title="Download"><?=($HasFile ? 'DL' : 'Missing')?></a>
            <?php if (Torrents::can_use_token($Torrent)) { ?>
            | <a href="torrents.php?action=download&amp;id=<?=($TorrentID)?>&amp;authkey=<?=($user['AuthKey'])?>&amp;torrent_pass=<?=($user['torrent_pass'])?>&amp;usetoken=1"
                class="tooltip" title="Use a FL Token"
                onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
            <?php } ?>
            | <a href="reportsv2.php?action=report&amp;id=<?=($TorrentID)?>"
                class="tooltip" title="Report">RP</a>
            <?php if ($CanEdit) { ?>
            | <a href="torrents.php?action=edit&amp;id=<?=($TorrentID)?>"
                class="tooltip" title="Edit">ED</a>
            <?php }
            if (check_perms('torrents_delete') || $UserID == $user['ID']) { ?>
            | <a href="torrents.php?action=delete&amp;torrentid=<?=($TorrentID)?>"
                class="tooltip" title="Remove">RM</a>
            <?php } ?>
            | <a href="torrents.php?torrentid=<?=($TorrentID)?>"
                class="tooltip" title="Permalink">PL</a>
            ]
        </span>
        <a data-toggle-target="#torrent_<?=($TorrentID)?>"><?=($ExtraInfo)?></a>
    </td>

    <td class="number_column nobr"><?=(Format::get_size($Size))?>
    </td>
    <td class="number_column"><?=(Text::float($Snatched))?>
    </td>
    <td class="number_column"><?=(Text::float($Seeders))?>
    </td>
    <td class="number_column"><?=(Text::float($Leechers))?>
    </td>
</tr>

<tr class="<?=(isset($ReleaseType) ? 'releases_'.$ReleaseType : '')?> groupid_<?=($GroupID)?> edition_<?=($EditionID)?> torrentdetails pad<?php if (!isset($_GET['torrentid']) || $_GET['torrentid'] != $TorrentID) { ?> hidden<?php } ?>"
    id="torrent_<?=($TorrentID)?>">
    <td colspan="5">
        <blockquote>
            Uploaded by <?php
  if ($Anonymous) {
      if (check_perms('users_mod')) { ?>
            <em class="tooltip"
                title="<?=User::user_info($UserID)['Username']?>">Anonymous</em>
            <?php } else {
                ?><em>Anonymous</em><?php
            }
  } else {
      print(User::format_username($UserID, false, false, false));
  } ?> <?=time_diff($TorrentTime); ?>
            <?php if ($Seeders === 0) {
                if ($LastActive && time() - strtotime($LastActive) >= 1209600) { ?>
            <br /><strong>Last active: <?=time_diff($LastActive);?></strong>
            <?php } else { ?>
            <br />Last active: <?=time_diff($LastActive);?>
            <?php }
            if ($LastActive && time() - strtotime($LastActive) >= 345678 && time() - strtotime($LastReseedRequest) >= 864000) { ?>
            <br /><a
                href="torrents.php?action=reseed&amp;torrentid=<?=($TorrentID)?>&amp;groupid=<?=($GroupID)?>"
                class="brackets">Request re-seed</a>
            <?php }
            } ?>
        </blockquote>
        <?php if (check_perms('site_moderate_requests')) { ?>
        <div class="linkbox">
            <a href="torrents.php?action=masspm&amp;id=<?=($GroupID)?>&amp;torrentid=<?=($TorrentID)?>"
                class="brackets">Mass PM snatchers</a>
        </div>
        <?php } ?>
        <div class="linkbox">
            <a href="#" class="brackets"
                onclick="show_peers('<?=($TorrentID)?>', 0); return false;">View
                peer list</a>
            <?php if (check_perms('site_view_torrent_snatchlist')) { ?>
            <a href="#" class="brackets tooltip"
                onclick="show_downloads('<?=($TorrentID)?>', 0); return false;"
                title="View the list of users that have clicked the &quot;DL&quot; button.">View download list</a>
            <a href="#" class="brackets tooltip"
                onclick="show_snatches('<?=($TorrentID)?>', 0); return false;"
                title="View the list of users that have reported a snatch to the tracker.">View snatch list</a>
            <?php } ?>
            <a href="#" class="brackets"
                onclick="show_files('<?=($TorrentID)?>'); return false;">View
                file list</a>
            <?php if ($Reported) { ?>
            <a href="#" class="brackets"
                onclick="show_reported('<?=($TorrentID)?>'); return false;">View
                report information</a>
            <?php } ?>
        </div>
        <div id="peers_<?=($TorrentID)?>" class="hidden"></div>
        <div id="downloads_<?=($TorrentID)?>" class="hidden"></div>
        <div id="snatches_<?=($TorrentID)?>" class="hidden"></div>
        <div id="files_<?=($TorrentID)?>" class="hidden"><?=($FileTable)?>
        </div>
        <?php if ($Reported) { ?>
        <div id="reported_<?=($TorrentID)?>" class="hidden"><?=($ReportInfo)?>
        </div>
        <?php }
        if (!empty($Description)) {
            echo "<blockquote>" . Text::parse($Description) . '</blockquote>';
        } ?>
    </td>
</tr>
<?php
        }
    }
} # class

<?
function get_group_info($GroupID, $Return = true, $RevisionID = 0, $PersonalProperties = true, $ApiCall = false) {
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
        GROUP_CONCAT(tt.UserID SEPARATOR '|'),
        GROUP_CONCAT(tt.PositiveVotes SEPARATOR '|'),
        GROUP_CONCAT(tt.NegativeVotes SEPARATOR '|')
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
    $TorrentDetails['Screenshots'] = array();

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

function get_torrent_info($TorrentID, $Return = true, $RevisionID = 0, $PersonalProperties = true, $ApiCall = false) {
  global $Cache, $DB;
  $GroupID = (int)torrentid_to_groupid($TorrentID);
  $GroupInfo = get_group_info($GroupID, $Return, $RevisionID, $PersonalProperties, $ApiCall);
  if ($GroupInfo) {
    foreach ($GroupInfo[1] as &$Torrent) {
      //Remove unneeded entries
      if ($Torrent['ID'] != $TorrentID) {
        unset($GroupInfo[1][$Torrent['ID']]);
      }
      if ($Return) {
        return $GroupInfo;
      }
    }
  } else {
    if ($Return) {
      return null;
    }
  }
}

//Check if a givin string can be validated as a torrenthash
function is_valid_torrenthash($Str) {
  //6C19FF4C 6C1DD265 3B25832C 0F6228B2 52D743D5
  $Str = str_replace(' ', '', $Str);
  if (preg_match('/^[0-9a-fA-F]{40}$/', $Str))
    return $Str;
  return false;
}

//Functionality for the API to resolve input into other data.

function torrenthash_to_torrentid($Str) {
  global $Cache, $DB;
  $DB->query("
    SELECT ID
    FROM torrents
    WHERE HEX(info_hash) = '".db_string($Str)."'");
  $TorrentID = (int)array_pop($DB->next_record(MYSQLI_ASSOC));
  if ($TorrentID) {
    return $TorrentID;
  }
  return null;
}

function torrenthash_to_groupid($Str) {
  global $Cache, $DB;
  $DB->query("
    SELECT GroupID
    FROM torrents
    WHERE HEX(info_hash) = '".db_string($Str)."'");
  $GroupID = (int)array_pop($DB->next_record(MYSQLI_ASSOC));
  if ($GroupID) {
    return $GroupID;
  }
  return null;
}

function torrentid_to_groupid($TorrentID) {
  global $Cache, $DB;
  $DB->query("
    SELECT GroupID
    FROM torrents
    WHERE ID = '".db_string($TorrentID)."'");
  $GroupID = (int)array_pop($DB->next_record(MYSQLI_ASSOC));
  if ($GroupID) {
    return $GroupID;
  }
  return null;
}

//After adjusting / deleting logs, recalculate the score for the torrent.
function set_torrent_logscore($TorrentID) {
  global $DB;
  $DB->query("
    UPDATE torrents
    SET LogScore = (
        SELECT FLOOR(AVG(Score))
        FROM torrents_logs_new
        WHERE TorrentID = $TorrentID
        )
    WHERE ID = $TorrentID");
}

function get_group_requests($GroupID) {
  if (empty($GroupID) || !is_number($GroupID)) {
    return array();
  }
  global $DB, $Cache;

  $Requests = $Cache->get_value("requests_group_$GroupID");
  if ($Requests === false) {
    $DB->query("
      SELECT ID
      FROM requests
      WHERE GroupID = $GroupID
        AND TimeFilled IS NULL");
    $Requests = $DB->collect('ID');
    $Cache->cache_value("requests_group_$GroupID", $Requests, 0);
  }
  return Requests::get_requests($Requests);
}

//Used by both sections/torrents/details.php and sections/reportsv2/report.php
function build_torrents_table($Cache, $DB, $LoggedUser, $GroupID, $GroupName, $GroupCategoryID, $TorrentList, $Types, $Username) {

  function filelist($Str) {
    return "</td>\n<td>" . Format::get_size($Str[1]) . "</td>\n</tr>";
  }
  $EditionID = 0;
  foreach ($TorrentList as $Torrent) {
  list($TorrentID, $Media, $Container, $Codec, $Resolution, $AudioFormat, $Subbing,
  $Subber, $Language, $Censored, $Archive, $FileCount, $Size, $Seeders, $Leechers, $Snatched,
  $FreeTorrent, $FreeLeechType, $TorrentTime, $Description, $MediaInfo, $FileList, $FilePath, $UserID,
  $LastActive, $InfoHash, $BadTags, $BadFolders, $BadFiles, $LastReseedRequest,
  $LogInDB, $HasFile, $PersonalFL, $IsSnatched, $IsSeeding, $IsLeeching) = array_values($Torrent);

  $Reported = false;
  $Reports = Torrents::get_reports($TorrentID);
  $NumReports = count($Reports);

  if ($NumReports > 0) {
    $Reported = true;
    include(SERVER_ROOT.'/sections/reportsv2/array.php');
    $ReportInfo = '
    <table class="reportinfo_table">
      <tr class="colhead_dark" style="font-weight: bold;">
        <td>This torrent has '.$NumReports.' active '.($NumReports === 1 ? 'report' : 'reports').":</td>
      </tr>";

    foreach ($Reports as $Report) {
      if (check_perms('admin_reports')) {
        $ReporterID = $Report['ReporterID'];
        $Reporter = Users::user_info($ReporterID);
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
        //There was a type but it wasn't an option!
        $ReportType = $Types['master']['other'];
      }
      $ReportInfo .= "
      <tr>
        <td>$ReportLinks ".time_diff($Report['ReportedTime'], 2, true, true).' for the reason "'.$ReportType['title'].'":
          <blockquote>'.Text::full_format($Report['UserComment']).'</blockquote>
        </td>
      </tr>';
    }
    $ReportInfo .= "\n\t\t</table>";
  }

  $CanEdit = (check_perms('torrents_edit') || (($UserID == $LoggedUser['ID'] && !$LoggedUser['DisableWiki'])));

  $RegenLink = check_perms('users_mod') ? ' <a href="torrents.php?action=regen_filelist&amp;torrentid=' . $TorrentID . '" class="brackets">Regenerate</a>' : '';
  $FileTable = '
  <table class="filelist_table">
    <tr class="colhead_dark">
      <td>
        <div class="filelist_title" style="float: left;">File Names' . $RegenLink . '</div>
        <div class="filelist_path" style="float: right;">' . ($FilePath ? "/$FilePath/" : '') . '</div>
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
  $FileTable .= '
  </table>';

  $ExtraInfo = ''; // String that contains information on the torrent (e.g. format and encoding)
  $AddExtra = ''; // Separator between torrent properties

  $TorrentUploader = $Username; // Save this for "Uploaded by:" below
  // similar to Torrents::torrent_info()
  if (!$ExtraInfo) {
    $ExtraInfo = $GroupName;
    $AddExtra = ' / ';
  }
  if ($IsLeeching) {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Leeching');
    $AddExtra = ' / ';
  } else if ($IsSeeding) {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Seeding');
    $AddExtra = ' / ';
  } else if ($IsSnatched) {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Snatched!');
    $AddExtra = ' / ';
  }
  if ($FreeTorrent == '1') {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Freeleech!');
    $AddExtra = ' / ';
  }
  if ($FreeTorrent == '2') {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Neutral Leech!');
    $AddExtra = ' / ';
  }
  if ($PersonalFL) {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Personal Freeleech!');
    $AddExtra = ' / ';
  }
  if ($Reported) {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Reported');
    $AddExtra = ' / ';
  }
  if (!empty($BadTags)) {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Bad Tags');
    $AddExtra = ' / ';
  }
  if (!empty($BadFolders)) {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Bad Folders');
    $AddExtra = ' / ';
  }
  if (!empty($BadFiles)) {
    $ExtraInfo .= $AddExtra . Format::torrent_label('Bad File Names');
    $AddExtra = ' / ';
  }
    ?>
        <tr class="torrent_row<?=(isset($ReleaseType)?' releases_'.$ReleaseType:'')?> groupid_<?=($GroupID)?> edition_<?=($EditionID)?> group_torrent<?=($IsSnatched ? ' snatched_torrent' : '')?>" style="font-weight: normal;" id="torrent<?=($TorrentID)?>">
          <td>
            <span>[ <a href="torrents.php?action=download&amp;id=<?=($TorrentID)?>&amp;authkey=<?=($LoggedUser['AuthKey'])?>&amp;torrent_pass=<?=($LoggedUser['torrent_pass'])?>" class="tooltip" title="Download"><?=($HasFile ? 'DL' : 'Missing')?></a>
<?  if (Torrents::can_use_token($Torrent)) { ?>
              | <a href="torrents.php?action=download&amp;id=<?=($TorrentID)?>&amp;authkey=<?=($LoggedUser['AuthKey'])?>&amp;torrent_pass=<?=($LoggedUser['torrent_pass'])?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?  } ?>
              | <a href="reportsv2.php?action=report&amp;id=<?=($TorrentID)?>" class="tooltip" title="Report">RP</a>
<?  if ($CanEdit) { ?>
              | <a href="torrents.php?action=edit&amp;id=<?=($TorrentID)?>" class="tooltip" title="Edit">ED</a>
<?  }
  if (check_perms('torrents_delete') || $UserID == $LoggedUser['ID']) { ?>
              | <a href="torrents.php?action=delete&amp;torrentid=<?=($TorrentID)?>" class="tooltip" title="Remove">RM</a>
<?  } ?>
              | <a href="torrents.php?torrentid=<?=($TorrentID)?>" class="tooltip" title="Permalink">PL</a>
            ]</span>
            &raquo; <a toggle-target="#torrent_<?=($TorrentID)?>"><?=($ExtraInfo)?></a>
          </td>
          <td class="number_column nobr"><?=(Format::get_size($Size))?></td>
          <td class="number_column"><?=(number_format($Snatched))?></td>
          <td class="number_column"><?=(number_format($Seeders))?></td>
          <td class="number_column"><?=(number_format($Leechers))?></td>
        </tr>
        <tr class="<?=(isset($ReleaseType)?'releases_'.$ReleaseType:'')?> groupid_<?=($GroupID)?> edition_<?=($EditionID)?> torrentdetails pad<? if (!isset($_GET['torrentid']) || $_GET['torrentid'] != $TorrentID) { ?> hidden<? } ?>" id="torrent_<?=($TorrentID)?>">
          <td colspan="5">
            <blockquote>
              Uploaded by <?=(Users::format_username($UserID, false, false, false))?> <?=time_diff($TorrentTime);?>
<?  if ($Seeders == 0) {
    if (!is_null($LastActive) && time() - strtotime($LastActive) >= 1209600) { ?>
                <br /><strong>Last active: <?=time_diff($LastActive);?></strong>
<?    } else { ?>
                <br />Last active: <?=time_diff($LastActive);?>
<?    }
    if (!is_null($LastActive) && time() - strtotime($LastActive) >= 345678 && time() - strtotime($LastReseedRequest) >= 864000) { ?>
                <br /><a href="torrents.php?action=reseed&amp;torrentid=<?=($TorrentID)?>&amp;groupid=<?=($GroupID)?>" class="brackets">Request re-seed</a>
<?    }
  } ?>
            </blockquote>
<?  if (check_perms('site_moderate_requests')) { ?>
            <div class="linkbox">
              <a href="torrents.php?action=masspm&amp;id=<?=($GroupID)?>&amp;torrentid=<?=($TorrentID)?>" class="brackets">Mass PM snatchers</a>
            </div>
<?  } ?>
            <div class="linkbox">
              <a href="#" class="brackets" onclick="show_peers('<?=($TorrentID)?>', 0); return false;">View peer list</a>
<?  if (check_perms('site_view_torrent_snatchlist')) { ?>
              <a href="#" class="brackets tooltip" onclick="show_downloads('<?=($TorrentID)?>', 0); return false;" title="View the list of users that have clicked the &quot;DL&quot; button.">View download list</a>
              <a href="#" class="brackets tooltip" onclick="show_snatches('<?=($TorrentID)?>', 0); return false;" title="View the list of users that have reported a snatch to the tracker.">View snatch list</a>
<?  } ?>
              <a href="#" class="brackets" onclick="show_files('<?=($TorrentID)?>'); return false;">View file list</a>
<?  if ($Reported) { ?>
              <a href="#" class="brackets" onclick="show_reported('<?=($TorrentID)?>'); return false;">View report information</a>
<?  } ?>
            </div>
            <div id="peers_<?=($TorrentID)?>" class="hidden"></div>
            <div id="downloads_<?=($TorrentID)?>" class="hidden"></div>
            <div id="snatches_<?=($TorrentID)?>" class="hidden"></div>
            <div id="files_<?=($TorrentID)?>" class="hidden"><?=($FileTable)?></div>
<?  if ($Reported) { ?>
            <div id="reported_<?=($TorrentID)?>" class="hidden"><?=($ReportInfo)?></div>
<?  }
  if (!empty($Description)) {
    echo "\n\t\t\t\t\t\t<blockquote>" . Text::full_format($Description) . '</blockquote>';
  } if (!empty($MediaInfo)) {
    echo "\n\t\t\t\t\t\t<blockquote>" . MediaInfo::parse($MediaInfo) . "</blockquote>";
  } ?>
          </td>
        </tr>
<?
  }
}

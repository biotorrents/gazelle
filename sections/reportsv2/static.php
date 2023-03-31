<?php
declare(strict_types=1);

$app = \Gazelle\App::go();

/**
 * This page is used for viewing reports in every viewpoint except auto.
 * It doesn't AJAX grab a new report when you resolve each one, use auto
 * for that (reports.php). If you wanted to add a new view, you'd simply
 * add to the case statement(s) below and add an entry to views.php to
 * explain it.
 * Any changes made to this page within the foreach loop should probably be
 * replicated on the auto page (reports.php).
 */

if (!check_perms('admin_reports')) {
    error(403);
}

require_once serverRoot.'/classes/reports.class.php';

define('REPORTS_PER_PAGE', '10');
list($Page, $Limit) = Format::page_limit(REPORTS_PER_PAGE);


if (isset($_GET['view'])) {
    $View = $_GET['view'];
} else {
    error(404);
}

if (isset($_GET['id'])) {
    if (!is_numeric($_GET['id']) && $View !== 'type') {
        error(404);
    } else {
        $ID = db_string($_GET['id']);
    }
} else {
    $ID = '';
}

/**
 * Large query builder
 */
$Order = 'ORDER BY r.`ReportedTime` ASC';

if (!$ID) {
    switch ($View) {
    case 'resolved':
      $Title = 'All the old smelly reports';
      $Where = "WHERE r.`Status` = 'Resolved'";
      $Order = 'ORDER BY r.`LastChangeTime` DESC';
      break;

    case 'unauto':
      $Title = 'New reports, not auto assigned!';
      $Where = "WHERE r.`Status` = 'New'";
      break;

    default:
      error(404);
      break;
  }
} else {
    switch ($View) {
    case 'staff':
      $app->dbOld->prepared_query("
        SELECT `Username`
        FROM `users_main`
        WHERE `ID` = $ID");
      list($Username) = $app->dbOld->next_record();
      if ($Username) {
          $Title = "$Username's in-progress reports";
      } else {
          $Title = "$ID's in-progress reports";
      }
      $Where = "
        WHERE r.`Status` = 'InProgress'
          AND r.`ResolverID` = $ID";
      break;

    case 'resolver':
      $app->dbOld->prepared_query("
        SELECT `Username`
        FROM `users_main`
        WHERE `ID` = $ID");
      list($Username) = $app->dbOld->next_record();
      if ($Username) {
          $Title = "$Username's resolved reports";
      } else {
          $Title = "$ID's resolved reports";
      }
      $Where = "
        WHERE r.`Status` = 'Resolved'
          AND r.`ResolverID` = $ID";
      $Order = 'ORDER BY r.`LastChangeTime` DESC';
      break;

    case 'group':
      $Title = "Unresolved reports for the group $ID";
      $Where = "
        WHERE r.`Status` != 'Resolved'
          AND tg.`id` = $ID";
      break;

    case 'torrent':
      $Title = "All reports for the torrent $ID";
      $Where = "WHERE r.`TorrentID` = $ID";
      break;

    case 'report':
      $Title = "Viewing resolution of report $ID";
      $Where = "WHERE r.`ID` = $ID";
      break;

    case 'reporter':
      $app->dbOld->prepared_query("
        SELECT `Username`
        FROM `users_main`
        WHERE `ID` = $ID");
      list($Username) = $app->dbOld->next_record();
      if ($Username) {
          $Title = "All torrents reported by $Username";
      } else {
          $Title = "All torrents reported by user $ID";
      }
      $Where = "WHERE r.`ReporterID` = $ID";
      $Order = 'ORDER BY r.`ReportedTime` DESC';
      break;

    case 'uploader':
      $app->dbOld->prepared_query("
        SELECT `Username`
        FROM `users_main`
        WHERE `ID` = $ID");
      list($Username) = $app->dbOld->next_record();
      if ($Username) {
          $Title = "All reports for torrents uploaded by $Username";
      } else {
          $Title = "All reports for torrents uploaded by user $ID";
      }
      $Where = "
        WHERE r.`Status` != 'Resolved'
          AND t.`UserID` = $ID";
      break;

    case 'type':
      $Title = 'All new reports for the chosen type';
      $Where = "
        WHERE r.`Status` = 'New'
          AND r.`Type` = '$ID'";
      break;

    default:
      error(404);
      break;
  }
}

/**
 * The large query
 */
$app->dbOld->prepared_query("
  SELECT
    SQL_CALC_FOUND_ROWS
    r.`ID`,
    r.`ReporterID`,
    reporter.`Username`,
    r.`TorrentID`,
    r.`Type`,
    r.`UserComment`,
    r.`ResolverID`,
    resolver.`Username`,
    r.`Status`,
    r.`ReportedTime`,
    r.`LastChangeTime`,
    r.`ModComment`,
    r.`Track`,
    r.`Image`,
    r.`ExtraID`,
    r.`Link`,
    r.`LogMessage`,
    COALESCE(NULLIF(tg.`title`, ''), NULLIF(tg.`subject`, ''), tg.`object`) AS Name,
    tg.`id`,
    CASE COUNT(ta.`GroupID`)
      WHEN 1 THEN ag.`ArtistID`
      ELSE '0'
    END AS `ArtistID`,
    CASE COUNT(ta.`GroupID`)
      WHEN 1 THEN ag.`Name`
      WHEN 0 THEN ''
      ELSE 'Various Artists'
    END AS ArtistName,
    tg.`year`,
    tg.`category_id`,
    t.`Time`,
    t.`Media`,
    t.`Size`,
    t.`UserID` AS UploaderID,
    uploader.`Username`
  FROM `reportsv2` AS r
    LEFT JOIN `torrents` AS t ON t.`ID` = r.`TorrentID`
    LEFT JOIN `torrents_group` AS tg ON tg.`id` = t.`GroupID`
    LEFT JOIN `torrents_artists` AS ta ON ta.`GroupID` = tg.`id`
    LEFT JOIN `artists_group` AS ag ON ag.`ArtistID` = ta.`ArtistID`
    LEFT JOIN `users_main` AS resolver ON resolver.`ID` = r.`ResolverID`
    LEFT JOIN `users_main` AS reporter ON reporter.`ID` = r.`ReporterID`
    LEFT JOIN `users_main` AS uploader ON uploader.`ID` = t.`UserID`
  $Where
  GROUP BY r.`ID`
  $Order
  LIMIT $Limit");

$Reports = $app->dbOld->to_array();

$app->dbOld->prepared_query('SELECT FOUND_ROWS()');
list($Results) = $app->dbOld->next_record();
$PageLinks = Format::get_pages($Page, $Results, REPORTS_PER_PAGE, 11);

View::header('Reports V2!', 'reportsv2');
?>
<div class="header">
  <h2><?=$Title?>
  </h2>
  <?php include('header.php'); ?>
</div>
<div class="buttonbox pad center">
  <?php if ($View !== 'resolved') { ?>
  <span class="tooltip" title="Resolves *all* checked reports with their respective resolutions"><input type="button"
      onclick="MultiResolve();" value="Multi-resolve" /></span>
  <span class="tooltip" title="Assigns all of the reports on the page to you!"><input type="button" onclick="Grab();"
      value="Claim all" /></span>
  <?php }
  if ($View === 'staff' && $app->user->core['id'] == $ID) { ?>
  | <span class="tooltip" title="Unclaim all of the reports currently displayed"><input type="button"
      onclick="GiveBack();" value="Unclaim all" /></span>
  <?php } ?>
</div>
<?php if ($PageLinks) { ?>
<div class="linkbox">
  <?=$PageLinks?>
</div>
<?php } ?>
<div id="all_reports" style="width: 80%; margin-left: auto; margin-right: auto;">
  <?php
if (count($Reports) === 0) {
      ?>
  <div class="box pad center">
    <strong>No new reports</strong>
  </div>
  <?php
  } else {
      foreach ($Reports as $Report) {
          list($ReportID, $ReporterID, $ReporterName, $TorrentID, $Type, $UserComment,
    $ResolverID, $ResolverName, $Status, $ReportedTime, $LastChangeTime, $ModComment,
    $Tracks, $Images, $ExtraIDs, $Links, $LogMessage, $GroupName, $GroupID, $ArtistID,
    $ArtistName, $Year, $CategoryID, $Time, $Media, $Size, $UploaderID,
    $UploaderName) = Misc::display_array($Report, array('ModComment'));

          if (!$GroupID && $Status != 'Resolved') {
              //Torrent already deleted
              $app->dbOld->prepared_query("
        UPDATE `reportsv2`
        SET
          `Status` = 'Resolved',
          `LastChangeTime` = NOW(),
          `ModComment` = 'Report already dealt with (torrent deleted)'
        WHERE `ID` = $ReportID");
              $app->cacheNew->decrement('num_torrent_reportsv2'); ?>
  <div id="report<?=$ReportID?>" class="report box pad center"
    data-load-report="<?=$ReportID?>">
    <a href="reportsv2.php?view=report&amp;id=<?=$ReportID?>">Report
      <?=$ReportID?></a> for torrent <?=$TorrentID?> (deleted) has been automatically resolved. <input
      type="button" value="Hide"
      onclick="ClearReport(<?=$ReportID?>);" />
  </div>
  <?php
          } else {
              if (!$CategoryID && false) {
                  //Torrent was deleted
              } else {
                  if (array_key_exists($Type, $Types['master'])) {
                      $ReportType = $Types['master'][$Type];
                  } else {
                      //There was a type but it wasn't an option!
                      $Type = 'other';
                      $ReportType = $Types['master']['other'];
                  }
              }
              $RawName = "$ArtistName - $GroupName".($Year ? " ($Year)" : '')." [$Media] (".Text::float($Size / (1024 * 1024), 2).' MB)';

              $LinkName = "<a href=\"artist.php?id=$ArtistID\">$ArtistName</a> - <a href=\"torrents.php?id=$GroupID\">$GroupName".($Year ? " ($Year)" : '')."</a> <a href=\"torrents.php?torrentid=$TorrentID\"> [$Media]</a> (".Text::float($Size / (1024 * 1024), 2).' MB)';

              $BBName = "[url=artist.php?id=$ArtistID]".$ArtistName."[/url] - [url=torrents.php?id=$GroupID]$GroupName".($Year ? " ($Year)" : '')."[/url] [url=torrents.php?torrentid=$TorrentID][$Media][/url] ".' ('.Text::float($Size / (1024 * 1024), 2).' MB)';
//      }?>
  <div id="report<?=$ReportID?>"
    data-load-report="<?=$ReportID?>">
    <form class="manage_form" name="report"
      id="reportform_<?=$ReportID?>" action="reports.php"
      method="post">
      <?php
/*
* Some of these are for takeresolve, namely the ones that aren't inputs, some for the JavaScript.
*/
?>
      <div>
        <input type="hidden" name="auth"
          value="<?=$app->user->extra['AuthKey']?>" />
        <input type="hidden" id="reportid<?=$ReportID?>"
          name="reportid" value="<?=$ReportID?>" />
        <input type="hidden" id="torrentid<?=$ReportID?>"
          name="torrentid" value="<?=$TorrentID?>" />
        <input type="hidden" id="uploader<?=$ReportID?>"
          name="uploader" value="<?=$UploaderName?>" />
        <input type="hidden" id="uploaderid<?=$ReportID?>"
          name="uploaderid" value="<?=$UploaderID?>" />
        <input type="hidden" id="reporterid<?=$ReportID?>"
          name="reporterid" value="<?=$ReporterID?>" />
        <input type="hidden" id="report_reason<?=$ReportID?>"
          name="report_reason" value="<?=$UserComment?>" />
        <input type="hidden" id="raw_name<?=$ReportID?>"
          name="raw_name" value="<?=$RawName?>" />
        <input type="hidden" id="type<?=$ReportID?>" name="type"
          value="<?=$Type?>" />
        <input type="hidden" id="categoryid<?=$ReportID?>"
          name="categoryid" value="<?=$CategoryID?>" />
      </div>
      <div class="box pad">
        <table class="layout" cellpadding="5">
          <tr>
            <td class="label"><a
                href="reportsv2.php?view=report&amp;id=<?=$ReportID?>">Reported</a>
              torrent:</td>
            <td colspan="3">
              <?php if (!$GroupID) { ?>
              <a href="log.php?search=Torrent+<?=$TorrentID?>"><?=$TorrentID?></a> (Deleted)
              <?php } else { ?>
              <?=$LinkName?>
              <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>"
                title="Download" class="brackets tooltip">DL</a>
              uploaded by <a
                href="user.php?id=<?=$UploaderID?>"><?=$UploaderName?></a> <?=time_diff($Time)?>
              <br />
              <?php if ($ReporterName == '') {
    $ReporterName = 'System';
} ?>
              <div style="text-align: right;">was reported by <a
                  href="user.php?id=<?=$ReporterID?>"><?=$ReporterName?></a> <?=time_diff($ReportedTime)?> for the reason:
                <strong><?=$ReportType['title']?></strong>
              </div>
              <?php if ($Status != 'Resolved') {
    $app->dbOld->prepared_query("
            SELECT r.`ID`
            FROM `reportsv2` AS r
              LEFT JOIN `torrents` AS t ON t.`ID` = r.`TorrentID`
            WHERE r.`Status` != 'Resolved'
              AND t.`GroupID` = $GroupID");
    $GroupOthers = ($app->dbOld->record_count() - 1);

    if ($GroupOthers > 0) { ?>
              <div style="text-align: right;">
                <a
                  href="reportsv2.php?view=group&amp;id=<?=$GroupID?>">There
                  <?=(($GroupOthers > 1) ? "are $GroupOthers other reports" : "is 1 other report")?>
                  for torrent(s) in this group</a>
              </div>
              <?php }

    $app->dbOld->prepared_query("
            SELECT t.`UserID`
            FROM `reportsv2` AS r
              JOIN `torrents` AS t ON t.`ID` = r.`TorrentID`
            WHERE r.`Status` != 'Resolved'
              AND t.`UserID` = $UploaderID");
    $UploaderOthers = ($app->dbOld->record_count() - 1);

    if ($UploaderOthers > 0) { ?>
              <div style="text-align: right;">
                <a
                  href="reportsv2.php?view=uploader&amp;id=<?=$UploaderID?>">There
                  <?=(($UploaderOthers > 1) ? "are $UploaderOthers other reports" : "is 1 other report")?>
                  for torrent(s) uploaded by this user</a>
              </div>
              <?php }

    $app->dbOld->prepared_query("
            SELECT DISTINCT req.`ID`,
              req.`FillerID`,
              um.`Username`,
              req.`TimeFilled`
            FROM `requests` AS req
              LEFT JOIN `torrents` AS t ON t.`ID` = req.`TorrentID`
              LEFT JOIN `reportsv2` AS rep ON rep.`TorrentID` = t.`ID`
              JOIN `users_main` AS um ON um.`ID` = req.`FillerID`
            WHERE rep.`Status` != 'Resolved'
              AND req.`TimeFilled` > '2010-03-04 02:31:49'
              AND req.`TorrentID` = $TorrentID");
    $Requests = ($app->dbOld->has_results());
    if ($Requests > 0) {
        while (list($RequestID, $FillerID, $FillerName, $FilledTime) = $app->dbOld->next_record()) {
            ?>
              <div style="text-align: right;">
                <strong class="important_text"><a
                    href="user.php?id=<?=$FillerID?>"><?=$FillerName?></a> used this torrent to fill <a
                    href="requests.php?action=view&amp;id=<?=$RequestID?>">this
                    request</a> <?=time_diff($FilledTime)?></strong>
              </div>
              <?php
        }
    }
}
      } ?>
            </td>
          </tr>
          <?php if ($Tracks) { ?>
          <tr>
            <td class="label">Relevant tracks:</td>
            <td colspan="3">
              <?=str_replace(' ', ', ', $Tracks)?>
            </td>
          </tr>
          <?php
      }

              if ($Links) { ?>
          <tr>
            <td class="label">Relevant links:</td>
            <td colspan="3">
              <?php
        $Links = explode(' ', $Links);
        foreach ($Links as $Link) {
            ?>
              <a href="<?=$Link?>"><?=$Link?></a>
              <?php
        } ?>
            </td>
          </tr>
          <?php
      }

              if ($ExtraIDs) { ?>
          <tr>
            <td class="label">Relevant other torrents:</td>
            <td colspan="3">
              <?php
        $First = true;
        $Extras = explode(' ', $ExtraIDs);
        foreach ($Extras as $ExtraID) {
            $app->dbOld->prepared_query("
            SELECT
              COALESCE(NULLIF(tg.`title`, ''), NULLIF(tg.`subject`, ''), tg.`object`) AS Name,
              tg.`id`,
              ta.`ArtistID`,
              CASE COUNT(ta.`GroupID`)
                WHEN 1 THEN ag.`Name`
                WHEN 0 THEN ''
                ELSE 'Various Artists'
              END AS ArtistName,
              tg.`year`,
              t.`Time`,
              t.`Media`,
              t.`Size`,
              t.`UserID` AS UploaderID,
              uploader.`Username`
            FROM `torrents` AS t
              LEFT JOIN `torrents_group` AS tg ON tg.`id` = t.`GroupID`
              LEFT JOIN `torrents_artists` AS ta ON ta.`GroupID` = tg.`id`
              LEFT JOIN `artists_group` AS ag ON ag.`ArtistID` = ta.`ArtistID`
              LEFT JOIN `users_main` AS uploader ON uploader.`ID` = t.`UserID`
            WHERE t.`ID` = ?
            GROUP BY tg.`id`", $ExtraID);

            list($ExtraGroupName, $ExtraGroupID, $ExtraArtistID, $ExtraArtistName, $ExtraYear, $ExtraTime,
            $ExtraMedia, $ExtraSize, $ExtraUploaderID, $ExtraUploaderName) = Misc::display_array($app->dbOld->next_record());
            if ($ExtraGroupName) {
                if ($ArtistID == 0 && empty($ArtistName)) {
                    $ExtraLinkName = "<a href=\"torrents.php?id=$ExtraGroupID\">$ExtraGroupName".($ExtraYear ? " ($ExtraYear)" : '')."</a> <a href=\"torrents.php?torrentid=$ExtraID\"> [$ExtraFormat/$ExtraEncoding/$ExtraMedia]</a> ".' ('.Text::float($ExtraSize / (1024 * 1024), 2).' MB)';
                } elseif ($ArtistID == 0 && $ArtistName == 'Various Artists') {
                    $ExtraLinkName = "Various Artists - <a href=\"torrents.php?id=$ExtraGroupID\">$ExtraGroupName".($ExtraYear ? " ($ExtraYear)" : '')."</a> <a href=\"torrents.php?torrentid=$ExtraID\"> [$ExtraFormat/$ExtraEncoding/$ExtraMedia]</a> (".Text::float($ExtraSize / (1024 * 1024), 2).' MB)';
                } else {
                    $ExtraLinkName = "<a href=\"artist.php?id=$ExtraArtistID\">$ExtraArtistName</a> - <a href=\"torrents.php?id=$ExtraGroupID\">$ExtraGroupName".($ExtraYear ? " ($ExtraYear)" : '')."</a> <a href=\"torrents.php?torrentid=$ExtraID\"> [//$ExtraMedia]</a>  (".Text::float($ExtraSize / (1024 * 1024), 2).' MB)';
                } ?>
              <?=($First ? '' : '<br />')?>
              <?=$ExtraLinkName?>
              <a href="torrents.php?action=download&amp;id=<?=$ExtraID?>&amp;authkey=<?=$app->user->extra['AuthKey']?>&amp;torrent_pass=<?=$app->user->extra['torrent_pass']?>"
                title="Download" class="brackets tooltip">DL</a>
              uploaded by <a
                href="user.php?id=<?=$ExtraUploaderID?>"><?=$ExtraUploaderName?></a> <?=time_diff($ExtraTime)?> <a href="#"
                onclick="Switch(<?=$ReportID?>, <?=$TorrentID?>, <?=$ExtraID?>); return false;"
                class="brackets">Switch</a>
              <?php
            $First = false;
            }
        }
?>
            </td>
          </tr>
          <?php
      }

              if ($Images) {
                  ?>
          <tr>
            <td class="label">Relevant images:</td>
            <td colspan="3">
              <?php
        $Images = explode(' ', $Images);
                  foreach ($Images as $Image) {
                      ?>
              <img style="max-width: 200px;" class="lightbox-init"
                src="<?=ImageTools::process($Image)?>"
                alt="Relevant image" />
              <?php
                  } ?>
            </td>
          </tr>
          <?php
              } ?>
          <tr>
            <td class="label">User comment:</td>
            <td colspan="3" class="wrap_overflow"><?=Text::parse($UserComment)?>
            </td>
          </tr>
          <?php // END REPORTED STUFF :|: BEGIN MOD STUFF
      if ($Status == 'InProgress') { ?>
          <tr>
            <td class="label">In progress by:</td>
            <td colspan="3">
              <a href="user.php?id=<?=$ResolverID?>"><?=$ResolverName?></a>
            </td>
          </tr>
          <?php }
              if ($Status != 'Resolved') { ?>
          <tr>
            <td class="label">Report comment:</td>
            <td colspan="3">
              <input type="text" name="comment"
                id="comment<?=$ReportID?>" size="70"
                value="<?=$ModComment?>" />
              <input type="button" value="Update now"
                onclick="UpdateComment(<?=$ReportID?>);" />
            </td>
          </tr>
          <tr>
            <td class="label">
              <a href="javascript:Load('<?=$ReportID?>')"
                class="tooltip" title="Click here to reset the resolution options to their default values.">Resolve</a>:
            </td>
            <td colspan="3">
              <select name="resolve_type"
                id="resolve_type<?=$ReportID?>"
                onchange="ChangeResolve(<?=$ReportID?>);">
                <?php
        $TypeList = $Types['master'] /* + $Types[$CategoryID] */ ;
        $Priorities = [];
        foreach ($TypeList as $Key => $Value) {
            $Priorities[$Key] = $Value['priority'];
        }
        array_multisort($Priorities, SORT_ASC, $TypeList);

        foreach ($TypeList as $Type => $Data) { ?>
                <option value="<?=$Type?>"><?=$Data['title']?>
                </option>
                <?php } ?>
              </select>
              <span id="options<?=$ReportID?>">
                <?php if (check_perms('torrents_delete')) { ?>
                <span class="tooltip" title="Delete torrent?">
                  <label
                    for="delete<?=$ReportID?>"><strong>Delete</strong></label>
                  <input type="checkbox" name="delete"
                    id="delete<?=$ReportID?>" />
                </span>
                <?php } ?>
                <span class="tooltip" title="Warning length in weeks">
                  <label
                    for="warning<?=$ReportID?>"><strong>Warning</strong></label>
                  <select name="warning" id="warning<?=$ReportID?>">
                    <?php for ($i = 0; $i < 9; $i++) { ?>
                    <option value="<?=$i?>"><?=$i?>
                    </option>
                    <?php } ?>
                  </select>
                </span>
                <span class="tooltip" title="Remove upload privileges?">
                  <label for="upload<?=$ReportID?>"><strong>Remove
                      upload privileges</strong></label>
                  <input type="checkbox" name="upload"
                    id="upload<?=$ReportID?>" />
                </span>
                &nbsp;&nbsp;
                <span class="tooltip" title="Update resolve type">
                  <input type="button" name="update_resolve"
                    id="update_resolve<?=$ReportID?>"
                    value="Update now"
                    onclick="UpdateResolve(<?=$ReportID?>);" />
                </span>
              </span>
            </td>
          </tr>
          <tr>
            <td class="label tooltip"
              title="Uploader: Appended to the regular message unless using &quot;Send now&quot;. Reporter: Must be used with &quot;Send now&quot;.">
              PM
              <select name="pm_type" id="pm_type<?=$ReportID?>">
                <option value="Uploader">Uploader</option>
                <option value="Reporter">Reporter</option>
              </select>:
            </td>
            <td colspan="3">
              <textarea name="uploader_pm"
                id="uploader_pm<?=$ReportID?>" cols="50"
                rows="1"></textarea>
              <input type="button" value="Send now"
                onclick="SendPM(<?=$ReportID?>);" />
            </td>
          </tr>
          <tr>
            <td class="label"><strong>Extra</strong> log message:</td>
            <td>
              <input type="text" name="log_message"
                id="log_message<?=$ReportID?>" size="40" <?php
          if ($ExtraIDs) {
              $Extras = explode(' ', $ExtraIDs);
              $Value = '';
              foreach ($Extras as $ExtraID) {
                  $Value .= site_url()."torrents.php?torrentid=$ExtraID ";
              }
              echo ' value="'.trim($Value).'"';
          } ?>
              />
            </td>
            <td class="label"><strong>Extra</strong> staff notes:</td>
            <td>
              <input type="text" name="admin_message"
                id="admin_message<?=$ReportID?>" size="40" />
            </td>
          </tr>
          <tr>
            <td colspan="4" style="text-align: center;">
              <input type="button" value="Invalidate report"
                onclick="Dismiss(<?=$ReportID?>);" />
              <input type="button" value="Resolve report manually"
                onclick="ManualResolve(<?=$ReportID?>);" />
              <?php if ($Status == 'InProgress' && $app->user->core['id'] == $ResolverID) { ?>
              | <input type="button" value="Unclaim"
                onclick="GiveBack(<?=$ReportID?>);" />
              <?php } else { ?>
              | <input id="grab<?=$ReportID?>" type="button"
                value="Claim" onclick="Grab(<?=$ReportID?>);" />
              <?php } ?>
              | Multi-resolve <input type="checkbox" name="multi"
                id="multi<?=$ReportID?>" checked="checked" />
              | <input type="button" id="submit_<?=$ReportID?>"
                value="Submit"
                onclick="TakeResolve(<?=$ReportID?>);" />
            </td>
          </tr>
          <?php } else { ?>
          <tr>
            <td class="label">Resolver:</td>
            <td colspan="3">
              <a href="user.php?id=<?=$ResolverID?>"><?=$ResolverName?></a>
            </td>
          </tr>
          <tr>
            <td class="label">Resolve time:</td>
            <td colspan="3">
              <?=time_diff($LastChangeTime); echo "\n"; ?>
            </td>
          </tr>
          <tr>
            <td class="label">Report comments:</td>
            <td colspan="3">
              <?=$ModComment; echo "\n"; ?>
            </td>
          </tr>
          <tr>
            <td class="label">Log message:</td>
            <td colspan="3">
              <?=$LogMessage; echo "\n"; ?>
            </td>
          </tr>
          <?php if ($GroupID) { ?>
          <tr>
            <td colspan="4" style="text-align: center;">
              <input id="grab<?=$ReportID?>" type="button"
                value="Claim" onclick="Grab(<?=$ReportID?>);" />
            </td>
          </tr>
          <?php }
        } ?>
        </table>
      </div>
    </form>
  </div>
  <?php
          }
      }
  }
?>
</div>
<?php if ($PageLinks) { ?>
<div class="linkbox pager"><?=$PageLinks?>
</div>
<?php } ?>
<?php View::footer();

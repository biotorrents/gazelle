<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

/**
 * This is the frontend of reporting a torrent, it's what users see when
 * they visit reportsv2.php?id=xxx
 */

$ENV = ENV::go();


// If we're not coming from torrents.php, check we're being returned because of an error.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if (!isset($Err)) {
        error(404);
    }
} else {
    $TorrentID = $_GET['id'];
    $app->dbOld->prepared_query("
    SELECT tg.`category_id`, t.`GroupID`, u.`Username`
    FROM `torrents_group` AS tg
      LEFT JOIN `torrents` AS t ON t.`GroupID` = tg.`id`
      LEFT JOIN `users_main` AS u ON t.`UserID` = u.`ID`
    WHERE t.`ID` = " . $_GET['id']);
    list($CategoryID, $GroupID, $Username) = $app->dbOld->next_record();
    $Artists = Artists::get_artist($GroupID);
    $TorrentCache = TorrentFunctions::get_group_info($GroupID, true);
    $GroupDetails = $TorrentCache[0];
    $TorrentList = $TorrentCache[1];
    // Resolve the torrentlist to the one specific torrent being reported
    foreach ($TorrentList as &$Torrent) {
        // Remove unneeded entries
        if ($Torrent['ID'] != $TorrentID) {
            unset($TorrentList[$Torrent['ID']]);
        }
    }

    // Group details
    list($WikiBody, $WikiImage, $GroupID, $GroupName, $GroupTitle2, $GroupNameJP,
        $GroupYear, $GroupStudio, $GroupSeries, $GroupCatalogueNumber,
        $GroupCategoryID, $GroupDLSite, $GroupTime, $TorrentTags, $TorrentTagIDs,
        $TorrentTagUserIDs, $Screenshots, $GroupFlags) = array_values($GroupDetails);

    $DisplayName = $GroupName;
    $AltName = $GroupName; // Goes in the alt text of the image
    $Title = $GroupName; // Goes in <title>
    $WikiBody = Text::parse($WikiBody);

    // Get the artist name, group name etc.
    $Artists = Artists::get_artist($GroupID);
    if ($Artists) {
        $DisplayName = '<span dir="ltr">' . Artists::display_artists($Artists, true) . "<a href=\"torrents.php?torrentid=$TorrentID\">$DisplayName</a></span>";
        $AltName = Text::esc(Artists::display_artists($Artists, false)) . $AltName;
        $Title = $AltName;
    }
    if ($GroupYear > 0) {
        $DisplayName .= " [$GroupYear]";
        $AltName .= " [$GroupYear]";
        $Title .= " [$GroupYear]";
    }
    /*
      if ($GroupCategoryID === 1) {
        $DisplayName .= ' [' . $ReleaseTypes[$ReleaseType] . ']';
        $AltName .= ' [' . $ReleaseTypes[$ReleaseType] . ']';
      }
    */
}

View::header('Report', 'reportsv2,browse,torrent,recommend');
?>

<div>
  <div class="header">
    <h2>Report a torrent</h2>
  </div>
  <div class="header">
    <h3><?=$DisplayName?></h3>
  </div>
  <div class="box">
    <table class="torrent_table details<?=((isset($GroupFlags['IsSnatched']) && $GroupFlags['IsSnatched']) ? ' snatched' : '')?>" id="torrent_details">
      <tr class="colhead_dark">
        <td width="80%"><strong>Reported torrent</strong></td>
        <td><strong>Size</strong></td>
        <td class="sign snatches">
          ↻
        </td>
        <td class="sign seeders">
          &uarr;
        </td>
        <td class="sign leechers">
          &darr;
        </td>
      </tr>
      <?php
      $LangName = $GroupName ? $GroupName : ($GroupTitle2 ? $GroupTitle2 : $GroupNameJP);
TorrentFunctions::build_torrents_table($app->user, $GroupID, $LangName, $GroupCategoryID, $TorrentList, $Types, $Username);
?>
    </table>
  </div>

  <form name="report" action="reportsv2.php?action=takereport" enctype="multipart/form-data" method="post" id="reportform">
    <div>
      <input type="hidden" name="submit" value="true" />
      <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>" />
      <input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
      <input type="hidden" name="categoryid" value="<?=$CategoryID?>" />
    </div>

    <h3>Report Information</h3>
    <div class="box pad">
      <table class="layout">
        <tr>
          <td class="label">Reason</td>
          <td>
            <select id="type" name="type" class="change_report_type">
<?php
  if (!empty($Types[$CategoryID])) {
      $TypeList = $Types['master'] + $Types[$CategoryID];
      $Priorities = [];
      foreach ($TypeList as $Key => $Value) {
          $Priorities[$Key] = $Value['priority'];
      }
      array_multisort($Priorities, SORT_ASC, $TypeList);
  } else {
      $TypeList = $Types['master'];
  }
  foreach ($TypeList as $Type => $Data) {
      ?>
              <option value="<?=($Type)?>"><?=($Data['title'])?></option>
<?php
  } ?>
            </select>
          </td>
        </tr>
      </table>
      <p>Fields that contain lists of values (for example, listing more than one track number) should be separated by a space.</p>
      <p><strong>Following the below report type specific guidelines will help the moderators deal with your report in a timely fashion.</strong></p>

      <div id="dynamic_form">
<?php
  /**
   * THIS IS WHERE SEXY AJAX COMES IN
   * The following malarky is needed so that if you get sent back here, the fields are filled in.
   */
?>
        <input id="sitelink" type="hidden" name="sitelink" size="50" value="<?=(!empty($_POST['sitelink']) ? Text::esc($_POST['sitelink']) : '')?>" />
        <input id="image" type="hidden" name="image" size="50" value="<?=(!empty($_POST['image']) ? Text::esc($_POST['image']) : '')?>" />
        <input id="track" type="hidden" name="track" size="8" value="<?=(!empty($_POST['track']) ? Text::esc($_POST['track']) : '')?>" />
        <input id="link" type="hidden" name="link" size="50" value="<?=(!empty($_POST['link']) ? Text::esc($_POST['link']) : '')?>" />
        <input id="extra" type="hidden" name="extra" value="<?=(!empty($_POST['extra']) ? Text::esc($_POST['extra']) : '')?>" />
      </div>
    </div>
  <input type="submit" class="button-primary" value="Report" />
  </form>
</div>
<?php View::footer();

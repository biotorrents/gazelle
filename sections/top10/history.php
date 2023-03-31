<?php

declare(strict_types=1);


/**
 * top10 history
 */

$app = \Gazelle\App::go();

enforce_login();
if (!check_perms('users_mod')) {
    error(404);
}

if (!check_perms('site_top10')) {
    error(403);
}

// if (!check_perms('site_top10_history')) {
//   error(403);
// }

View::header('Top 10 Torrents history!');
?>

<div>
  <div class="header">
    <h2>Top 10 Torrents</h2>
    <?php Top10::render_linkbox(); ?>
  </div>
  <div class="pad box">
    <form class="search_form" name="top10" method="get" action="">
      <input type="hidden" name="type" value="history" />
      <h3>Search for a date! (After 2010-09-05)</h3>
      <table class="layout">
        <tr>
          <td class="label">Date</td>
          <td><input type="text" id="date" name="date"
              value="<?=!empty($_GET['date']) ? Text::esc($_GET['date']) : 'YYYY-MM-DD'?>"
              onfocus="if ($('#date').raw().value == 'YYYY-MM-DD') { $('#date').raw().value = ''; }" /></td>
        </tr>
        <tr>
          <td class="label">Type</td>
          <td>
            <input type="radio" name="datetype" value="day" checked="checked"> Day
            <input type="radio" name="datetype" value="week"> Week
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <input type="submit" value="Submit" />
          </td>
        </tr>
      </table>
    </form>
  </div>

  <?php
if (!empty($_GET['date'])) {
    $Date = $_GET['date'];
    $SQLTime = $Date.' 00:00:00';
    if (empty($_GET['datetype']) || $_GET['datetype'] == 'day') {
        $Type = 'day';
        $Where = "
      WHERE th.Date BETWEEN '$SQLTime' AND '$SQLTime' + INTERVAL 24 HOUR
        AND Type = 'Daily'";
    } else {
        $Type = 'week';
        $Where = "
      WHERE th.Date BETWEEN '$SQLTime' - AND '$SQLTime' + INTERVAL 7 DAY
        AND Type = 'Weekly'";
    }

    $Details = $app->cacheNew->get("top10_history_$SQLTime");
    if ($Details === false) {
        $app->dbOld->prepared_query("
        SELECT
          tht.`Rank`,
          tht.`TitleString`,
          tht.`TagString`,
          tht.`TorrentID`,
          g.`ID`,
          g.`Name`,
          g.`CategoryID`,
          g.`TagList`,
          t.`Format`,
          t.`Encoding`,
          t.`Media`,
          t.`Scene`,
          t.`HasLog`,
          t.`HasCue`,
          t.`LogScore`,
          t.`RemasterYear`,
          g.`Year`,
          t.`RemasterTitle`
        FROM
          `top10_history` AS th
        LEFT JOIN `top10_history_torrents` AS tht
        ON
          tht.`HistoryID` = th.`ID`
        LEFT JOIN `torrents` AS t
        ON
          t.`ID` = tht.`TorrentID`
        LEFT JOIN `torrents_group` AS g
        ON
          g.`ID` = t.`GroupID`
        $Where
        ORDER BY
          tht.`Rank` ASC
        ");

        $Details = $app->dbOld->to_array();

        $app->cacheNew->set("top10_history_$SQLTime", $Details, 3600 * 24);
    } ?>

  <br />
  <div class="pad box">
    <h3>Top 10 for <?=($Type == 'day' ? $Date : "the first week after $Date")?>
    </h3>
    <table class="torrent_table cats numbering border">
      <tr class="colhead">
        <td class="center" style="width: 15px;"></td>
        <td class="center"></td>
        <td><strong>Name</strong></td>
      </tr>

      <?php
  foreach ($Details as $Detail) {
      list($Rank, $TitleString, $TagString, $TorrentID, $GroupID, $GroupName, $GroupCategoryID, $TorrentTags,
          $Format, $Encoding, $Media, $Scene, $HasLog, $HasCue, $LogScore, $Year, $GroupYear,
          $RemasterTitle, $Snatched, $Seeders, $Leechers, $Data) = $Detail;

      if ($GroupID) {
          // Group still exists
          $DisplayName = '';

          $Artists = Artists::get_artist($GroupID);

          if (!empty($Artists)) {
              $DisplayName = Artists::display_artists($Artists, true, true);
          }

          $DisplayName .= "<a href=\"torrents.php?id=$GroupID&amp;torrentid=$TorrentID\" class=\"tooltip\" title=\"View torrent\" dir=\"ltr\">$GroupName</a>";

          if ($GroupCategoryID === 1 && $GroupYear > 0) {
              $DisplayName .= " [$GroupYear]";
          }

          // Append extra info to torrent title
          $ExtraInfo = '';
          $AddExtra = '&thinsp;|&thinsp;'; # breaking

          if ($Format) {
              $ExtraInfo .= $Format;
          }

          if ($Encoding) {
              $ExtraInfo .= $AddExtra.$Encoding;
          }

          if ($HasLog) {
              $ExtraInfo .= "$AddExtra Log ($LogScore%)";
          }

          if ($HasCue) {
              $ExtraInfo .= "{$AddExtra}Cue";
          }

          if ($Media) {
              $ExtraInfo .= $AddExtra.$Media;
          }

          if ($Scene) {
              $ExtraInfo .= "{$AddExtra}Scene";
          }

          if ($Year > 0) {
              $ExtraInfo .= $AddExtra.$Year;
          }

          if ($RemasterTitle) {
              $ExtraInfo .= $AddExtra.$RemasterTitle;
          }

          if ($ExtraInfo !== '') {
              $ExtraInfo = "- [$ExtraInfo]";
          }

          $DisplayName .= $ExtraInfo;
          $TorrentTags = new Tags($TorrentTags);
      } else {
          $DisplayName = "$TitleString (Deleted)";
          $TorrentTags = new Tags($TagString);
      } // if ($GroupID)

      ?>
      <tr class="group_torrent row">
        <td style="padding: 8px; text-align: center;"><strong><?=$Rank?></strong></td>
        <td class="center categoryColumn">
        </td>
        <td>
          <span><?=($GroupID ? '<a href="torrents.php?action=download&amp;id='.$TorrentID.'&amp;authkey='.$app->user->extra['AuthKey'].'&amp;torrent_pass='.$app->user->extra['torrent_pass'].' title="Download" class="brackets tooltip">DL</a>' : '(Deleted)')?></span>
          <?=$DisplayName?>
          <div class="tags"><?=$TorrentTags->format()?>
          </div>
        </td>
      </tr>
      <?php
  } // foreach ($Details as $Detail)
    ?>
    </table><br />
  </div>
</div>
<?php
}
View::footer();

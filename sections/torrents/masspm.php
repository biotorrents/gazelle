<?php
#declare(strict_types = 1);

$app = App::go();

$GroupID = (int) $_GET['id'];
$TorrentID = (int) $_GET['torrentid'];
Security::int($GroupID, $TorrentID);

$app->dbOld->prepared_query("
SELECT
  t.`Media`,
  t.`FreeTorrent`,
  t.`GroupID`,
  t.`UserID`,
  t.`Description` AS TorrentDescription,
  tg.`category_id`,
  tg.`title` AS Title,
  tg.`year`,
  tg.`artist_id`,
  ag.`Name` AS ArtistName
FROM
  `torrents` AS t
JOIN `torrents_group` AS tg
ON
  tg.`id` = t.`GroupID`
LEFT JOIN `artists_group` AS ag
ON
  ag.`ArtistID` = tg.`artist_id`
WHERE
  t.`ID` = '$TorrentID'
");


list($Properties) = $app->dbOld->to_array(false, MYSQLI_BOTH);
if (!$Properties) {
    error(404);
}

View::header('Edit torrent', 'upload');

if (!check_perms('site_moderate_requests')) {
    error(403);
}
?>

<div>
  <div class="header">
    <h2>
      Send PM to All Snatchers of
      "<?=$Properties['ArtistName']?> - <?=$Properties['Title']?>"
    </h2>
  </div>

  <form class="send_form" name="mass_message" action="torrents.php" method="post">
    <input type="hidden" name="action" value="takemasspm" />
    <input type="hidden" name="auth"
      value="<?=$app->userNew->extra['AuthKey']?>" />
    <input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
    <input type="hidden" name="groupid" value="<?=$GroupID?>" />

    <table class="layout">
      <tr>
        <td class="label">
          Subject
        </td>

        <td>
          <input type="text" name="subject" value="" size="60" />
        </td>
      </tr>

      <tr>
        <td class="label">
          Message
        </td>

        <td>
          <textarea name="message" id="message" cols="60" rows="8"></textarea>
        </td>
      </tr>

      <tr>
        <td colspan="2" class="center">
          <input type="submit" value="Send Mass PM" />
        </td>
      </tr>
    </table>
  </form>
</div>

<?php View::footer();

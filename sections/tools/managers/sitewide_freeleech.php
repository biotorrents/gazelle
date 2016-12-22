<?
if (isset($_POST['type'])) {
  if ($_POST['type'] == 'tag') {
    authorize();
    if (!isset($_POST['tag'])) {
      error("You didn't enter a tag, dipshit.");
    }
    $Tag = db_string($_POST['tag']);
    $DB->query("
      SELECT ID
      FROM tags
      WHERE
        Name = '" . $Tag . "'");
    if ($DB->has_results()) {
      $Tag = str_replace('.', '_', $Tag);
      $DB->query("
        SELECT t.ID
        FROM torrents AS t
          JOIN torrents_group AS tg ON t.GroupID = tg.ID
        WHERE t.FreeTorrent != '2'
          AND (t.FreeLeechType = '0' OR t.FreeLeechType = '3')
          AND tg.TagList LIKE '%" . $Tag . "%'");
      if ($DB->has_results()) {
        $IDs = $DB->collect('ID');
        $Duration = db_string($_POST['duration']);
        $Query = "INSERT IGNORE INTO shop_freeleeches (TorrentID, ExpiryTime) VALUES ";
        foreach ($IDs as $ID) {
          $Query .= "(" . $ID . ", NOW() + INTERVAL " . $Duration . " HOUR), ";
        }
        $Query = substr($Query, 0, strlen($Query) - 2);
        $Query .= " ON DUPLICATE KEY UPDATE ExpiryTime = ExpiryTime + INTERVAL " . $Duration . " HOUR";
        $DB->query($Query);

        $DB->query("
          INSERT INTO misc
            (Name, First, Second)
          VALUES
            ('" . $Tag . "', '" . (time() + (60 * 60 * $Duration)) . "', 'freeleech')
          ON DUPLICATE KEY UPDATE
            First = CONVERT(First, UNSIGNED INTEGER) + " . (60 * 60 * $Duration));
        Torrents::freeleech_torrents($IDs, 1, 3);
        echo("Success! Now run the indexer.");
      } else {
        error('No torrents with that tag exist.');
      }
    } else {
      error("That tag doesn't exist.");
    }
  } elseif ($_POST['type'] == 'global') {
    authorize();
    $DB->query("
      SELECT t.ID
      FROM torrents AS t
        JOIN torrents_group AS tg ON t.GroupID = tg.ID
      WHERE t.FreeTorrent != '2'
        AND (t.FreeLeechType = '0' OR t.FreeLeechType = '3')");
    if ($DB->has_results()) {
      $IDs = $DB->collect('ID');
      $Duration = db_string($_POST['duration']);
      $Query = "INSERT IGNORE INTO shop_freeleeches (TorrentID, ExpiryTime) VALUES ";
      foreach ($IDs as $ID) {
        $Query .= "(" . $ID . ", NOW() + INTERVAL " . $Duration . " HOUR), ";
      }
      $Query = substr($Query, 0, strlen($Query) - 2);
      $Query .= " ON DUPLICATE KEY UPDATE ExpiryTime = ExpiryTime + INTERVAL " . $Duration . " HOUR";
      $DB->query($Query);
      $DB->query("
        INSERT INTO misc
          (Name, First, Second)
        VALUES
          ('global', '" . (time() + (60 * 60 * $Duration)) . "', 'freeleech')
        ON DUPLICATE KEY UPDATE
          First = CONVERT(First, UNSIGNED INTEGER) + " . (60 * 60 * $Duration));
      Torrents::freeleech_torrents($IDs, 1, 3, false);
      echo("Success! Now run the indexer.");
    } else {
      error("RIP Oppaitime");
    }
  }
} else {
  View::show_header('Site-Wide Freeleech'); ?>
  <div class="thin">
    <div class="box pad" style="padding: 10px 10px 10px 20px; text-align: center;">
      <strong>Make sure you run the indexer after using either of these tools, or torrents may disappear from search until the indexer runs.</strong>
    </div>
    <div class="box pad" style="padding: 10px 10px 10px 20px; text-align: center;">
      <form action="tools.php" method="POST">
        <input type="hidden" name="action" value="freeleech" />
        <input type="hidden" name="type" value="tag">
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        <strong>Single Tag Freeleech</strong>
        <br />
        <input id="tag_name" type="text" name="tag" placeholder="Tag" value="" />
        <br />
        <input id="tag_duration" type="number" name="duration" placeholder="Duration (hours)" value="" />
        <br />
        <input type="submit" value="RELEASE THE LEECH" />
      </form>
    </div>
    <div class="box pad" style="padding: 10px 10px 10px 20px; text-align: center;">
      <form action="tools.php" method="POST">
        <input type="hidden" name="action" value="freeleech" />
        <input type="hidden" name="type" value="global" />
        <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
        <strong>Global Freeleech</strong>
        <br />
        <input id="global_duration" type="number" name="duration" placeholder="Duration (hours)" value="" />
        <br />
        <input type="submit" value="RELEASE THE LEECH" />
    </div>
  </div>
  <? View::show_footer();
}
?>

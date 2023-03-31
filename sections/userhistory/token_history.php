<?php
declare(strict_types=1);

/**
 * User token history page
 * This page lists the torrents a user has spent his tokens on.
 * It gets called if $_GET['action'] === 'token_history.'
 *
 * Using $_GET['userid'] allows a mod to see any user's token history.
 * Non-mods and empty userid show $app->userNew->core['id']'s history.
 */

$app = \Gazelle\App::go();

# Validate user ID
if (isset($_GET['userid'])) {
    $UserID = (int) $_GET['userid'];
} else {
    $UserID = (int) $app->userNew->core['id'];
}

Security::int($UserID);

# Get user info
$UserInfo = User::user_info($UserID);
$Perms = Permissions::get_permissions($UserInfo['PermissionID']);
$UserClass = $Perms['Class'];

# Validate mod permissions
if (!check_perms('users_mod')) {
    if ($app->userNew->core['id'] !== $UserID && !check_paranoia(false, $User['Paranoia'], $UserClass, $UserID)) {
        error(403);
    }
}

if (isset($_GET['expire'])) {
    if (!check_perms('users_mod')) {
        error(403);
    }

    $UserID = (int) $_GET['userid'];
    $TorrentID = (int) $_GET['torrentid'];
    Security::int($UserID, $TorrentID);

    $app->dbOld->prepared_query("
    SELECT
      HEX(`info_hash`)
    FROM
      `torrents`
    WHERE
      `ID` = '$TorrentID'
    ");


    if (list($InfoHash) = $app->dbOld->next_record(MYSQLI_NUM, false)) {
        $app->dbOld->prepared_query("
        UPDATE
          `users_freeleeches`
        SET
          `Expired` = TRUE
        WHERE
          `UserID` = '$UserID' AND `TorrentID` = '$TorrentID'
        ");


        $app->cacheNew->delete("users_tokens_$UserID");
        Tracker::update_tracker(
            'remove_token',
            ['info_hash' => substr('%'.chunk_split($InfoHash, 2, '%'), 0, -1), 'userid' => $UserID]
        );
    }
    Http::redirect("userhistory.php?action=token_history&userid=$UserID");
}

# Render HTML
View::header('Freeleech token history');
list($Page, $Limit) = Format::page_limit(25);

$app->dbOld->prepared_query("
SELECT SQL_CALC_FOUND_ROWS
  f.`TorrentID`,
  t.`GroupID`,
  f.`Time`,
  f.`Expired`,
  f.`Downloaded`,
  f.`Uses`,
  g.`title`
FROM
  `users_freeleeches` AS f
JOIN `torrents` AS t
ON
  t.`ID` = f.`TorrentID`
JOIN `torrents_group` AS g
ON
  g.`id` = t.`GroupID`
WHERE
  f.`UserID` = '$UserID'
ORDER BY
  f.`Time`
DESC
LIMIT $Limit
");


$Tokens = $app->dbOld->to_array();
$app->dbOld->prepared_query('SELECT FOUND_ROWS()');
list($NumResults) = $app->dbOld->next_record();
$Pages = Format::get_pages($Page, $NumResults, 25);
?>

<div class="header">
  <h2>
    Freeleech token history for
    <?= User::format_username($UserID, false, false, false) ?>
  </h2>
</div>

<div class="linkbox">
  <?= $Pages ?>
</div>

<table>
  <tr class="colhead_dark">
    <th>Torrent</th>
    <th>Time</th>
    <th>Expired</th>

    <?php if (check_perms('users_mod')) { ?>
    <th>Downloaded</th>
    <th>Tokens used</th>
    <?php } ?>
  </tr>

  <?php
foreach ($Tokens as $Token) {
    $GroupIDs[] = $Token['GroupID'];
}
$Artists = Artists::get_artists($GroupIDs);

foreach ($Tokens as $Token) {
    list($TorrentID, $GroupID, $Time, $Expired, $Downloaded, $Uses, $Name) = $Token;

    if ($Name !== '') {
        $Name = "<a href='torrents.php?torrentid=$TorrentID'>$Name</a>";
    } else {
        $Name = "(<i>Deleted torrent <a href='log.php?search=Torrent+$TorrentID'>$TorrentID</a></i>)";
    }

    /*
    $ArtistName = Artists::display_artists($Artists[$GroupID]);
    if ($ArtistName) {
        $Name = $ArtistName.$Name;
    }
    */ ?>

  <tr class="row">
    <td>
      <?= $Name ?>
    </td>

    <td>
      <?= time_diff($Time) ?>
    </td>

    <td>
      <?= ($Expired ? 'Yes' : 'No') ?>
      <?= (check_perms('users_mod') && !$Expired)
        ? " <a href='userhistory.php?action=token_history&amp;expire=1&amp;userid=$UserID&amp;torrentid=$TorrentID'>(expire)</a>"
        : ''; ?>
    </td>

    <?php if (check_perms('users_mod')) { ?>
    <td>
      <?= Format::get_size($Downloaded) ?>
    </td>

    <td>
      <?= $Uses ?>
    </td>
    <?php } ?>
  </tr>
  <?php
} ?>
</table>

<div class="linkbox">
  <?= $Pages ?>
</div>

<?php View::footer();

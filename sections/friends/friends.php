<?php
#declare(strict_types=1);

/**
 * Main friends page
 *
 * This page lists a user's friends.
 * There's no real point in caching this page.
 * I doubt users load it that much.
 */

$ENV = ENV::go();

// Number of users per page
define('FRIENDS_PER_PAGE', '20');

View::header('Friends');

$UserID = $user['ID'];
list($Page, $Limit) = Format::page_limit(FRIENDS_PER_PAGE);

// Main query
$db->prepared_query("
  SELECT
    SQL_CALC_FOUND_ROWS
    f.`FriendID`,
    f.`Comment`,
    m.`Username`,
    m.`Uploaded`,
    m.`Downloaded`,
    m.`PermissionID`,
    m.`Paranoia`,
    m.`LastAccess`,
    i.`Avatar`
  FROM `friends` AS f
    JOIN `users_main` AS m ON f.`FriendID` = m.`ID`
    JOIN `users_info` AS i ON f.`FriendID` = i.`UserID`
  WHERE f.`UserID` = '$UserID'
  ORDER BY `Username`
  LIMIT $Limit");
$Friends = $db->to_array(false, MYSQLI_BOTH, array(6, 'Paranoia'));

// Number of results (for pagination)
$db->prepared_query('SELECT FOUND_ROWS()');
list($Results) = $db->next_record();

// Start printing stuff?>

<div>
  <div class="header">
    <h2>Friends List</h2>
  </div>

  <div class="linkbox">
    <?php
// Pagination
$Pages = Format::get_pages($Page, $Results, FRIENDS_PER_PAGE, 9);
echo $Pages; ?>
  </div>

  <div class="box pad">
    <?php
if ($Results === 0) {
    echo '<p>You have no friends! :(</p>';
}

// Start printing out friends
foreach ($Friends as $Friend) {
    list($FriendID, $Comment, $Username, $Uploaded, $Downloaded, $Class, $Paranoia, $LastAccess, $Avatar) = $Friend; ?>
    <form class="manage_form" name="friends" action="friends.php" method="post">
      <input type="hidden" name="auth"
        value="<?=$user['AuthKey']?>" />
      <table class="friends_table vertical_margin">
        <tr class="colhead">
          <td colspan="<?=(User::hasAvatarsEnabled() ? 3 : 2)?>">
            <span class="u-pull-left"><?=User::format_username($FriendID, true, true, true, true)?>
              <?php if (check_paranoia('ratio', $Paranoia, $Class, $FriendID)) { ?>
              &nbsp;Ratio: <strong><?=Format::get_ratio_html($Uploaded, $Downloaded)?></strong>
              <?php
  }

    if (check_paranoia('uploaded', $Paranoia, $Class, $FriendID)) {
        ?>
              &nbsp;Up: <strong><?=Format::get_size($Uploaded)?></strong>
              <?php
    }

    if (check_paranoia('downloaded', $Paranoia, $Class, $FriendID)) {
        ?>
              &nbsp;Down: <strong><?=Format::get_size($Downloaded)?></strong>
              <?php
    } ?>
            </span>
            <?php if (check_paranoia('lastseen', $Paranoia, $Class, $FriendID)) { ?>
            <span class="u-pull-right"><?=time_diff($LastAccess)?></span>
            <?php } ?>
          </td>
        </tr>
        <tr>
          <?php if (User::hasAvatarsEnabled()) { ?>
          <td class="col_avatar avatar" valign="top">
            <?=User::displayAvatar($Avatar, $Username)?>
          </td>
          <?php } ?>
          <td valign="top">
            <input type="hidden" name="friendid"
              value="<?=$FriendID?>" />

            <textarea
              name = "comment"
              rows = "5"
              cols = "50"
              placeholder ="Your saved notes about this friend"
              ><?=$Comment?></textarea>
          </td>
          <td class="left" valign="top">
          <p>
            <input type="submit" name="action" value="Update" />
            <input type="submit" name="action" value="Remove friend" />
          </p>

          <p>
            <input type="submit" name="action" class="button-primary" value="Contact" />
          </p>

          </td>
        </tr>
      </table>
    </form>
    <?php
} // while

// close <div class="box pad">
?>
  </div>
  <div class="linkbox">
    <?= $Pages ?>
  </div>
  <?php // close <div>?>
</div>
<?php
View::footer();

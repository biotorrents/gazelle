<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

if (!check_perms('users_view_ips') || !check_perms('users_view_email')) {
    error(403);
}

View::header('Registration log');
define('USERS_PER_PAGE', 50);
list($Page, $Limit) = Format::page_limit(USERS_PER_PAGE);

$AfterDate = isset($_POST['after_date']) ? $_POST['after_date'] : null;
$BeforeDate = isset($_POST['before_date']) ? $_POST['before_date'] : null;
$DateSearch = false;

if (!empty($AfterDate) && !empty($BeforeDate)) {
    list($Y, $M, $D) = explode('-', $AfterDate);
    if (!checkdate($M, $D, $Y)) {
        error('Incorrect "after" date format');
    }

    list($Y, $M, $D) = explode('-', $BeforeDate);
    if (!checkdate($M, $D, $Y)) {
        error('Incorrect "before" date format');
    }

    $AfterDate = db_string($AfterDate);
    $BeforeDate = db_string($BeforeDate);
    $DateSearch = true;
}

$RS = "
  SELECT
    SQL_CALC_FOUND_ROWS
    m.ID,
    m.IP,
    m.Email,
    m.Username,
    m.PermissionID,
    m.Uploaded,
    m.Downloaded,
    m.Enabled,
    i.Donor,
    i.Warned,
    i.JoinDate,
    im.ID,
    im.IP,
    im.Email,
    im.Username,
    im.PermissionID,
    im.Uploaded,
    im.Downloaded,
    im.Enabled,
    ii.Donor,
    ii.Warned,
    ii.JoinDate
  FROM users_main AS m
    LEFT JOIN users_info AS i ON i.UserID = m.ID
    LEFT JOIN users_main AS im ON i.Inviter = im.ID
    LEFT JOIN users_info AS ii ON i.Inviter = ii.UserID
  WHERE";

if ($DateSearch) {
    $RS .= " i.JoinDate BETWEEN '$AfterDate' AND '$BeforeDate' ";
} else {
    $RS .= " i.JoinDate > '".time_minus(3600 * 24 * 3)."'";
}

$RS .= "
  ORDER BY i.Joindate DESC
  LIMIT $Limit";

$QueryID = $app->dbOld->query($RS);
$app->dbOld->query('SELECT FOUND_ROWS()');
list($Results) = $app->dbOld->next_record();
$app->dbOld->set_query_id($QueryID);
?>

<form action="" method="post" class="box pad">
  <input type="hidden" name="action" value="registration_log">
  Joined after: <input type="date" name="after_date">
  Joined before: <input type="date" name="before_date">
  <input type="submit" class="button-primary">
</form>

<?php
if ($app->dbOld->has_results()) {
    ?>
<div class="linkbox">
  <?php
  $Pages = Format::get_pages($Page, $Results, USERS_PER_PAGE, 11) ;
    echo $Pages; ?>
</div>

<table width="100%">
  <tr class="colhead">
    <td>User</td>
    <td>Ratio</td>
    <td>Email</td>
    <td>IP address</td>
    <td>Country</td>
    <td>Registered</td>
  </tr>

  <?php
  while (list($UserID, $IP, $Email, $Username, $PermissionID, $Uploaded, $Downloaded, $Enabled, $Donor, $Warned, $Joined, $InviterID, $InviterIP, $InviterEmail, $InviterUsername, $InviterPermissionID, $InviterUploaded, $InviterDownloaded, $InviterEnabled, $InviterDonor, $InviterWarned, $InviterJoined) = $app->dbOld->next_record()) {
      $RowClass = $IP === $InviterIP ? 'warning' : '';
      $Email = apcu_exists('DBKEY') ? Crypto::decrypt($Email) : '[Encrypted]';
      $IP = apcu_exists('DBKEY') ? Crypto::decrypt($IP) : '[Encrypted]';
      $InviterEmail = apcu_exists('DBKEY') ? Crypto::decrypt($InviterEmail) : '[Encrypted]';
      $InviterIP = apcu_exists('DBKEY') ? Crypto::decrypt($InviterIP) : '[Encrypted]'; ?>

  <tr class="<?=$RowClass?>">
    <td>
      <?=User::format_username($UserID, true, true, true, true)?><br>
      <?=User::format_username($InviterID, true, true, true, true)?>
    </td>

    <td>
      <?=Format::get_ratio_html($Uploaded, $Downloaded)?><br>
      <?=Format::get_ratio_html($InviterUploaded, $InviterDownloaded)?>
    </td>

    <td>
      <span class="u-pull-left">
        <?=\Gazelle\Text::esc($Email)?>
      </span>

      <span class="u-pull-left">
        <?=\Gazelle\Text::esc($InviterEmail)?>
      </span>

    </td>

    <td>
      <span class="u-pull-left">
        <?=\Gazelle\Text::esc($IP)?>
      </span>

      <span class="u-pull-left">
        <?=\Gazelle\Text::esc($InviterIP)?>
      </span>
    </td>

    <td>
      <?=time_diff($Joined)?><br>
      <?=time_diff($InviterJoined)?>
    </td>
  </tr>
  <?php
  } ?>
</table>

<div class="linkbox">
  <?= $Pages; ?>
</div>
<?php
} else { ?>
<h2>There have been no new registrations in the past 72 hours.</h2>
<?php
}
View::footer();

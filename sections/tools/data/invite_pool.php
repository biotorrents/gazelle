<?php
#declare(strict_types=1);

$app = \Gazelle\App::go();

if (!check_perms('users_view_invites')) {
    error(403);
}

$Title = 'Invite Pool';
View::header($Title);
define('INVITES_PER_PAGE', 50);
list($Page, $Limit) = \Gazelle\Format::page_limit(INVITES_PER_PAGE);

if (!empty($_POST['invitekey']) && check_perms('users_edit_invites')) {
    authorize();

    $app->dbOld->query("
      DELETE FROM invites
      WHERE InviteKey = '" . db_string($_POST['invitekey']) . "'");
}

if (!empty($_GET['search'])) {
    $Search = db_string($_GET['search']);
} else {
    $Search = '';
}

$sql = "
  SELECT
    SQL_CALC_FOUND_ROWS
    um.ID,
    um.IP,
    i.InviteKey,
    i.Expires,
    i.Email
  FROM invites AS i
    JOIN users_main AS um ON um.ID = i.InviterID ";

if ($Search) {
    $sql .= "
  WHERE i.Email LIKE '%$Search%' ";
}

$sql .= "
  ORDER BY i.Expires DESC
  LIMIT $Limit";
$RS = $app->dbOld->query($sql);

$app->dbOld->query('SELECT FOUND_ROWS()');
list($Results) = $app->dbOld->next_record();
$app->dbOld->set_query_id($RS);
?>

<div class="header">
  <h2>
    <?=$Title?>
  </h2>
</div>

<div class="box pad">
  <p>
    <?=\Gazelle\Text::float($Results)?> unused invites have been sent.
  </p>
</div>
<br>

<div>
  <form class="search_form" name="invites" action="" method="get">
    <table cellpadding="6" cellspacing="1" border="0" class="layout border" width="100%">
      <tr>
        <td class="label">
          <strong>Email address:</strong>
        </td>

        <td>
          <input type="hidden" name="action" value="invite_pool">
          <input type="email" name="search" size="60"
            value="<?=\Gazelle\Text::esc($Search)?>">
          &nbsp;
          <input type="submit" class="button-primary" value="Search log">
        </td>
      </tr>
    </table>
  </form>
</div>

<div class="linkbox">
  <?php
  $Pages = \Gazelle\Format::get_pages($Page, $Results, INVITES_PER_PAGE, 11) ;
echo $Pages;
?>
</div>

<table width="100%">
  <tr class="colhead">
    <td>Inviter</td>
    <td>Email address</td>
    <td>IP address</td>
    <td>InviteCode</td>
    <td>Expires</td>
    <?php if (check_perms('users_edit_invites')) { ?>
    <td>Controls</td>
    <?php } ?>
  </tr>

  <?php
  while (list($UserID, $IP, $InviteKey, $Expires, $Email) = $app->dbOld->next_record()) {
      $IP = apcu_exists('DBKEY') ? \Gazelle\Crypto::decrypt($IP) : '[Encrypted]';
      $Email = apcu_exists('DBKEY') ? \Gazelle\Crypto::decrypt($Email) : '[Encrypted]'; ?>
  <tr class="row">
    <td>
      <?=User::format_username($UserID, true, true, true, true)?>
    </td>

    <td>
      <?=\Gazelle\Text::esc($Email)?>
    </td>

    <td>
      <?=\Gazelle\Text::esc($IP)?>
    </td>

    <td>
      <?=\Gazelle\Text::esc($InviteKey)?>
    </td>

    <td>
      <?=time_diff($Expires)?>
    </td>

    <?php if (check_perms('users_edit_invites')) { ?>
    <td>
      <form class="delete_form" name="invite" action="" method="post">
        <input type="hidden" name="action" value="invite_pool">
        <input type="hidden" name="auth"
          value="<?=$app->user->extra['AuthKey']?>">
        <input type="hidden" name="invitekey"
          value="<?=\Gazelle\Text::esc($InviteKey)?>">
        <input type="submit" value="Delete">
      </form>
    </td>
    <?php } ?>
  </tr>
  <?php
  } ?>
</table>

<?php if ($Pages) { ?>
<div class="linkbox pager"><?=($Pages)?>
</div>
<?php }
View::footer();

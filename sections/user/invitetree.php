<?php
#declare(strict_types=1);

$app = App::go();

if (isset($_GET['userid']) && check_perms('users_view_invites')) {
    if (!is_number($_GET['userid'])) {
        error(403);
    }

    $UserID = $_GET['userid'];
    $Sneaky = true;
} else {
    if (!$UserCount = $app->cacheOld->get_value('stats_user_count')) {
        $app->dbOld->query("
      SELECT COUNT(ID)
      FROM users_main
      WHERE Enabled = '1'");
        list($UserCount) = $app->dbOld->next_record();
        $app->cacheOld->cache_value('stats_user_count', $UserCount, 0);
    }

    $UserID = $user['ID'];
    $Sneaky = false;
}
list($UserID, $Username, $PermissionID) = array_values(User::user_info($UserID));

$ENV = ENV::go();
require_once serverRoot.'/classes/invite_tree.class.php';
$Tree = new INVITE_TREE($UserID);
View::header("$Username $ENV->crumb Invites $ENV->crumb Tree");
?>
<div>
  <div class="header">
    <h2>
      <?=User::format_username($UserID, false, false, false)?>
      <?=$ENV->crumb?>
      <a
        href="user.php?action=invite&amp;userid=<?=$UserID?>">Invites</a>
      <?=$ENV->crumb?> Tree</h2>
  </div>
  <div class="box pad">
    <?php $Tree->make_tree(); ?>
  </div>
</div>
<?php View::footer();

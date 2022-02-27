<?php
#declare(strict_types=1);

enforce_login();
if (!check_perms('site_moderate_forums')) {
    error(403);
}

$ForumID = $_GET['forumid'];
if (!is_number($ForumID)) {
    error(404);
}

if (!empty($_POST['add']) || (!empty($_POST['del']))) {
    if (!empty($_POST['add'])) {
        if (is_number($_POST['new_thread'])) {
            $db->query("
            INSERT INTO forums_specific_rules (ForumID, ThreadID)
            VALUES ($ForumID, ".$_POST['new_thread'].')');
        }
    }

    if (!empty($_POST['del'])) {
        if (is_number($_POST['threadid'])) {
            $db->query("
            DELETE FROM forums_specific_rules
            WHERE ForumID = $ForumID
              AND ThreadID = ".$_POST['threadid']);
        }
    }
    $cache->delete_value('forums_list');
}

$db->query("
SELECT ThreadID
FROM forums_specific_rules
  WHERE ForumID = $ForumID");
$ThreadIDs = $db->collect('ThreadID');

$ENV = ENV::go();
View::header();
?>

<div class="box pad">
  <div class="header">
    <h2>
      <a href="forums.php">Forums</a>
      <?=$ENV->CRUMB?>
      <a
        href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>"><?=$Forums[$ForumID]['Name']?></a>
      <?=$ENV->CRUMB?>
      Edit forum specific rules
    </h2>
  </div>

  <table>
    <tr class="colhead">
      <td>
        Thread ID
      </td>

      <td></td>
    </tr>

    <tr>
      <form class="add_form" name="forum_rules" action="" method="post">
        <td>
          <input name="new_thread" type="text" size="8" />
        </td>

        <td>
          <input type="submit" name="add" class="button-primary" value="Add thread" />
        </td>
      </form>

      <?php foreach ($ThreadIDs as $ThreadID) { ?>
    <tr>
      <td>
        <?=$ThreadID?>
      </td>

      <td>
        <form class="delete_form" name="forum_rules" action="" method="post">
          <input type="hidden" name="threadid"
            value="<?=$ThreadID?>" />
          <input type="submit" name="del" value="Delete link" />
        </form>
      </td>
    </tr>
    <?php } ?>
  </table>
</div>
<?php View::footer();

<?php

$app = \Gazelle\App::go();

if (!(check_perms('users_mod') || check_perms('site_tag_aliases_read'))) {
    error(403);
}

View::header('Tag Aliases');

$orderby = ((isset($_GET['order']) && $_GET['order'] === 'badtags') ? 'BadTag' : 'AliasTag');

if (check_perms('users_mod')) {
    if (isset($_POST['newalias'])) {
        $badtag = db_string($_POST['badtag']);
        $aliastag = db_string($_POST['aliastag']);

        $app->dbOld->query("
      INSERT INTO tag_aliases (BadTag, AliasTag)
      VALUES ('$badtag', '$aliastag')");
    }

    if (isset($_POST['changealias']) && is_numeric($_POST['aliasid'])) {
        $aliasid = $_POST['aliasid'];
        $badtag = db_string($_POST['badtag']);
        $aliastag = db_string($_POST['aliastag']);

        if ($_POST['save']) {
            $app->dbOld->query("
        UPDATE tag_aliases
        SET BadTag = '$badtag', AliasTag = '$aliastag'
        WHERE ID = '$aliasid' ");
        }
        if ($_POST['delete']) {
            $app->dbOld->query("
        DELETE FROM tag_aliases
        WHERE ID = '$aliasid'");
        }
        $app->cacheNew->delete('tag_aliases_search');
    }
}
?>
<div class="header">
  <h2>Tag Aliases</h2>
  <div class="linkbox">
      <a href="tools.php?action=tag_aliases&amp;order=goodtags" class="brackets">Sort by good tags</a>
      <a href="tools.php?action=tag_aliases&amp;order=badtags" class="brackets">Sort by bad tags</a>
  </div>
</div>
<table>
  <tr class="colhead">
    <td>Proper tag</td>
    <td>Renamed from</td>
<?php if (check_perms('users_mod')) { ?>
    <td>Submit</td>
<?php } ?>
  </tr>
  <tr />
  <tr>
    <form class="add_form" name="aliases" method="post" action="">
      <input type="hidden" name="newalias" value="1" />
      <td>
        <input type="text" name="aliastag" />
      </td>
      <td>
        <input type="text" name="badtag" />
      </td>
<?php if (check_perms('users_mod')) { ?>
      <td>
        <input type="submit" class="button-primary" value="Add alias" />
      </td>
<?php } ?>
    </form>
  </tr>
<?php
$app->dbOld->query("
  SELECT ID, BadTag, AliasTag
  FROM tag_aliases
  ORDER BY $orderby");
while (list($ID, $BadTag, $AliasTag) = $app->dbOld->next_record()) {
    ?>
  <tr>
    <form class="manage_form" name="aliases" method="post" action="">
      <input type="hidden" name="changealias" value="1" />
      <input type="hidden" name="aliasid" value="<?=$ID?>" />
      <td>
        <input type="text" name="aliastag" value="<?=$AliasTag?>" />
      </td>
      <td>
        <input type="text" name="badtag" value="<?=$BadTag?>" />
      </td>
<?php if (check_perms('users_mod')) { ?>
      <td>
        <input type="submit" name="save" class="button-primary" value="Save alias" />
        <input type="submit" name="delete" value="Delete alias" />
      </td>
<?php } ?>
    </form>
  </tr>
<?php
} ?>
</table>
<?php View::footer(); ?>

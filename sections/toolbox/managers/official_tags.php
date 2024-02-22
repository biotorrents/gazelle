<?php

$app = \Gazelle\App::go();

if (isset($_POST['doit'])) {


    if (isset($_POST['oldtags'])) {
        $OldTagIDs = $_POST['oldtags'];
        foreach ($OldTagIDs as $OldTagID) {
            if (!is_numeric($OldTagID)) {
                error(403);
            }
        }
        $OldTagIDs = implode(', ', $OldTagIDs);

        $app->dbOld->query("
      UPDATE tags
      SET TagType = 'other'
      WHERE ID IN ($OldTagIDs)");
    }

    if ($_POST['newtag']) {
        $TagName = Misc::sanitize_tag($_POST['newtag']);

        $app->dbOld->query("
      SELECT ID
      FROM tags
      WHERE Name LIKE '$TagName'");
        list($TagID) = $app->dbOld->next_record();

        if ($TagID) {
            $app->dbOld->query("
        UPDATE tags
        SET TagType = 'genre'
        WHERE ID = $TagID");
        } else { // Tag doesn't exist yet - create tag
            $app->dbOld->query("
        INSERT INTO tags
          (Name, UserID, TagType, Uses)
        VALUES
          ('$TagName', ".$app->user->core['id'].", 'genre', 0)");
            $TagID = $app->dbOld->inserted_id();
        }
    }

    $app->cache->delete('genre_tags');
}

View::header('Official Tags Manager');
?>
<div class="header">
  <h2>Official Tags Manager</h2>
</div>
<div style="text-align: center;">
  <div style="display: inline-block;">
    <form class="manage_form" name="tags" method="post" action="">
      <input type="hidden" name="action" value="official_tags">
      <input type="hidden" name="auth" value="<?=$app->user->extra['AuthKey']?>">
      <input type="hidden" name="doit" value="1">
      <table class="tags_table layout box slight_margin">
        <tr class="colhead_dark">
          <td style="font-weight: bold; text-align: center;">Remove</td>
          <td style="font-weight: bold;">Tag</td>
          <td style="font-weight: bold;">Uses</td>
          <td>&nbsp;&nbsp;&nbsp;</td>
          <td style="font-weight: bold; text-align: center;">Remove</td>
          <td style="font-weight: bold;">Tag</td>
          <td style="font-weight: bold;">Uses</td>
          <td>&nbsp;&nbsp;&nbsp;</td>
          <td style="font-weight: bold; text-align: center;">Remove</td>
          <td style="font-weight: bold;">Tag</td>
          <td style="font-weight: bold;">Uses</td>
        </tr>
<?php
$i = 0;
$app->dbOld->query("
  SELECT ID, Name, Uses
  FROM tags
  WHERE TagType = 'genre'
  ORDER BY Name ASC");
$TagCount = $app->dbOld->record_count();
$Tags = $app->dbOld->to_array();
for ($i = 0; $i < $TagCount / 3; $i++) {
    list($TagID1, $TagName1, $TagUses1) = $Tags[$i];
    if (isset($Tags[ceil($TagCount / 3) + $i])) {
        list($TagID2, $TagName2, $TagUses2) = $Tags[ceil($TagCount / 3) + $i];
    }
    if (isset($Tags[2 * ceil($TagCount / 3) + $i])) {
        list($TagID3, $TagName3, $TagUses3) = $Tags[2 * ceil($TagCount / 3) + $i];
    } ?>
        <tr class="row">
          <td style="text-align: center;"><input type="checkbox" name="oldtags[]" value="<?=$TagID1?>"></td>
          <td><?=$TagName1?></td>
          <td style="text-align: center;"><?=\Gazelle\Text::float($TagUses1)?></td>
          <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
          <td style="text-align: center;">
<?php if ($TagID2) { ?>
            <input type="checkbox" name="oldtags[]" value="<?=$TagID2?>">
<?php } ?>
          </td>
          <td><?=$TagName2?></td>
          <td style="text-align: center;"><?=\Gazelle\Text::float($TagUses2)?></td>
          <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
          <td style="text-align: center;">
<?php if ($TagID3) { ?>
            <input type="checkbox" name="oldtags[]" value="<?=$TagID3?>">
<?php } ?>
          </td>
          <td><?=$TagName3?></td>
          <td style="text-align: center;"><?=\Gazelle\Text::float($TagUses3)?></td>
        </tr>
<?php
}
?>
        <tr class="row">
          <td colspan="11">
            <label for="newtag">New official tag: </label><input type="text" name="newtag">
          </td>
        </tr>
        <tr style="border-top: thin solid;">
          <td colspan="11" style="text-align: center;">
            <input type="submit" class="button-primary" value="Submit changes">
          </td>
        </tr>

      </table>
    </form>
  </div>
</div>
<?php View::footer(); ?>

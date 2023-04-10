<?php
declare(strict_types=1);

$app = \Gazelle\App::go();

/**
 * Edit torrent group wiki page
 *
 * The page inserts a new revision into the wiki_torrents table,
 * and clears the cache for the torrent group page.
 */

$group_id = (int) $_GET['groupid'];
Security::int($group_id);

// Get the torrent group name and the body of the last revision
$app->dbOld->prepared_query("
SELECT
  tg.`title`,
  tg.`subject`,
  tg.`object`,
  wt.`Image`,
  wt.`Body`,
  tg.`picture`,
  tg.`description`,
  tg.`year`,
  tg.`workgroup`,
  tg.`location`,
  tg.`identifier`,
  tg.`category_id`
FROM
  `torrents_group` AS tg
LEFT JOIN `wiki_torrents` AS wt
ON
  wt.`RevisionID` = tg.`revision_id`
WHERE
  tg.`id` = '$group_id'
");


if (!$app->dbOld->has_results()) {
    error(404);
}
list($title, $subject, $object, $Image, $Body, $picture, $description, $published, $workgroup, $location, $identifier, $category_id) = $app->dbOld->next_record();

$app->dbOld->prepared_query("
SELECT
  `id`,
  `user_id`,
  `timestamp`,
  `doi`
FROM
  `literature`
WHERE
  `group_id` = '$group_id'
");


if ($app->dbOld->has_results()) {
    $Screenshots = [];
    while ($S = $app->dbOld->next_record(MYSQLI_ASSOC, true)) {
        $Screenshots[] = $S;
    }
}

$Artists = Artists::get_artists(array($group_id))[$group_id];

if (!$Body) {
    $Body = $description;
    $Image = $picture;
}

View::header(
    'Edit torrent group',
    'upload,vendor/easymde.min',
    'vendor/easymde.min'
); ?>

<h2 class="header">
  Edit
  <a href="torrents.php?id=<?=$group_id?>"><?=($title ? $title : ($subject ? $subject : $object))?></a>
</h2>

<div class="box pad">
  <form class="edit_form" name="torrent_group" action="torrents.php" method="post">
    <input type="hidden" name="action" value="takegroupedit" />

    <input type="hidden" name="auth"
      value="<?=$app->user->extra['AuthKey']?>" />

    <input type="hidden" name="groupid" value="<?=$group_id?>" />

    <h3>
      Picture
    </h3>

    <input type="text" name="image" size="80" value="<?=$Image?>" />
    <br /><br />

    <h3>
      Torrent Group Description
    </h3>

    <?php
View::textarea(
    id: 'body',
    value: \Gazelle\Text::esc($Body) ?? '',
);

$app->dbOld->query("
  SELECT
    `UserID`
  FROM
    `torrents`
  WHERE
    `GroupID` = '$group_id'
  ");
$Contributed = in_array($app->user->core['id'], $app->dbOld->collect('UserID'));
?>

    <h3>
      Edit Summary
    </h3>

    <input type="text" name="summary" size="80" />
    <br />

    <div class="center pad">
      <input type="submit" value="Submit" />
    </div>
  </form>
</div>

<?php
  if ($Contributed
    || check_perms('torrents_edit')
    || check_perms('screenshots_delete')
    || check_perms('screenshots_add')) { ?>
<h2 id="screenshots_section">
  Publications
</h2>

<div class="box pad">
  <form class="edit_form" name="screenshots_form" action="torrents.php" method="post">
    <input type="hidden" name="action" value="screenshotedit" />

    <input type="hidden" name="auth"
      value="<?=$app->user->extra['AuthKey']?>" />

    <input type="hidden" name="groupid" value="<?=$group_id?>" />

    <table cellpadding="3" cellspacing="1" border="0" class="layout" width="100%">
      <tr>
        <td class="label">
          Publications
        </td>

        <td id="screenshots">
          <?php
  if ($Contributed || check_perms('screenshots_add') || check_perms('torrents_edit')) { ?>
          <a class="u-pull-right brackets" onclick="AddScreenshotField()">+</a>
          <?php } ?>
        </td>
      </tr>
    </table>

    <div class="center pad">
      <input type="submit" value="Submit" />
    </div>
  </form>
</div>
<?php
  }

  // Users can edit the group info if they've uploaded a torrent to the group or have torrents_edit
  if ($Contributed || check_perms('torrents_edit')) { ?>
<h2>
  Non-wiki torrent group editing
</h2>

<div class="box pad">
  <form class="edit_form" name="torrent_group" action="torrents.php" method="post">
    <input type="hidden" name="action" value="nonwikiedit" />

    <input type="hidden" name="auth"
      value="<?=$app->user->extra['AuthKey']?>" />

    <input type="hidden" name="groupid" value="<?=$group_id?>" />

    <table cellpadding="3" cellspacing="1" border="0" class="layout" width="100%">
      <tr>
        <td class="label">
          Author(s)
        </td>

        <td id="idolfields">
          <input type="text" id="idols_0" name="idols[]" size="45"
            value="<?=$Artists[0]['name']?>" />
          <a class="add_artist_button brackets">+</a> <a class="remove_artist_button brackets">&minus;</a>
          <?php
  for ($i = 1; $i < count($Artists); $i++) {
      echo '<br /><input type="text" id="idol_'.$i.'" name="idols[]" size="45" value="'.$Artists[$i]['name'].'"/>';
  } ?>
        </td>
      </tr>

      <tr>
        <td class="label">
          Department/Lab
        </td>

        <td>
          <input type="text" id="studio" name="studio" size="60"
            value="<?=$workgroup?>" />
        </td>
      </tr>

      <tr>
        <td class="label">
          Location
        </td>

        <td>
          <input type="text" id="series" name="series" size="60"
            value="<?=$location?>" />
        </td>
      </tr>

      <tr>
        <td class="label">
          Year
        </td>

        <td>
          <input type="text" name="year" size="10"
            value="<?=$year ?? null?>" />
        </td>
      </tr>

      <tr>
        <td class="label">
          Accession Number
        </td>

        <td>
          <input type="text" name="catalogue" size="40"
            value="<?=$identifier?>" />
        </td>
      </tr>

      <?php if (check_perms('torrents_freeleech')) { ?>
      <tr>
        <td class="label">
          Torrent <strong>group</strong> leech status
        </td>

        <td>
          <input type="checkbox" id="unfreeleech" name="unfreeleech" />
          <label for="unfreeleech"> Reset</label>

          <input type="checkbox" id="freeleech" name="freeleech" />
          <label for="freeleech"> Freeleech</label>

          <input type="checkbox" id="neutralleech" name="neutralleech" />
          <label for="neutralleech"> Neutral Leech</label>

          because

          <select name="freeleechtype">
            <?php $FL = array('N/A', 'Staff Pick', 'Perma-FL', 'Freeleechizer', 'Site-Wide FL');
          foreach ($FL as $Key => $FLType) {
              $Torrent ??= [];
              $Torrent['FreeLeechType'] ??= null;
              ?>
            <option value="<?=$Key?>" <?=($Key == $Torrent['FreeLeechType'] ? ' selected="selected"' : '')?>><?=$FLType?>
            </option>
            <?php } ?>
          </select>
        </td>
      </tr>
      <?php } ?>
    </table>

    <div class="center pad">
      <input type="submit" value="Edit" />
    </div>
  </form>
</div>
<?php
  }

  if ($Contributed || check_perms('torrents_edit')) { ?>
<h2>
  Rename (will not merge)
</h2>

<div class="box pad">
  <form class="rename_form" name="torrent_group" action="torrents.php" method="post">
    <table cellpadding="3" cellspacing="1" border="0" class="layout" width="100%">
      <input type="hidden" name="action" value="rename" />

      <input type="hidden" name="auth"
        value="<?=$app->user->extra['AuthKey']?>" />

      <input type="hidden" name="groupid" value="<?=$group_id?>" />

      <tr>
        <td class="label">
          Torrent Title
        </td>

        <td>
          <input type="text" name="name" size="70"
            value="<?=$title?>" />
        </td>
      </tr>

      <tr>
        <td class="label">
          Organism
        </td>

        <td>
          <input type="text" name="subject" size="70"
            value="<?=$subject?>" />
        </td>
      </tr>

      <tr>
        <td class="label">
          Strain/Variety
        </td>

        <td>
          <input type="text" name="namejp" size="70"
            value="<?=$object?>" />
        </td>
      </tr>
    </table>

    <div class="center pad">
      <input type="submit" value="Rename" />
    </div>
  </form>
</div>
<?php
  }

  if (check_perms('torrents_edit')) { ?>
<h2>
  Merge with another group
</h2>

<div class="box pad">
  <form class="merge_form" name="torrent_group" action="torrents.php" method="post">
    <input type="hidden" name="action" value="merge" />

    <input type="hidden" name="auth"
      value="<?=$app->user->extra['AuthKey']?>" />

    <input type="hidden" name="groupid" value="<?=$group_id?>" />

    <h3>
      Target torrent group ID
      <input type="text" name="targetgroupid" size="10" />
    </h3>

    <div class="center pad">
      <input type="submit" value="Merge" />
    </div>
  </form>
</div>
<?php
  }

View::footer();

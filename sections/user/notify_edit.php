<?php
#declare(strict_types = 1);

if (!check_perms('site_torrents_notify')) {
    error(403);
}

View::header('Manage notifications'); ?>

<div>
  <h2 class="header">
    Notify me of all new torrents with&hellip;
  </h2>

  <div class="linkbox">
    <a href="torrents.php?action=notify" class="brackets">View notifications</a>
  </div>

  <?php
$db->query("
  SELECT
    `ID`,
    `Label`,
    `Artists`,
    `NewGroupsOnly`,
    `Tags`,
    `NotTags`,
    `ReleaseTypes`,
    `Categories`,
    `Formats`,
    `Encodings`,
    `Media`,
    `FromYear`,
    `ToYear`,
    `Users`
  FROM `users_notify_filters`
  WHERE `UserID` = $user[ID]
");

$NumFilters = $db->record_count();

$Notifications = $db->to_array();
$Notifications[] = array(
  'ID' => false,
  'Label' => '',
  'Artists' => '',
  'NewGroupsOnly' => true,
  'Tags' => '',
  'NotTags' => '',
  'ReleaseTypes' => '',
  'Categories' => '',
  'Formats' => '',
  'Encodings' => '',
  'Media' => '',
  'FromYear' => '',
  'ToYear' => '',
  'Users' => ''
);

$i = 0;
foreach ($Notifications as $N) { // $N stands for Notifications
    $i++;
    $NewFilter = $N['ID'] === false;
    $N['Artists']      = implode(', ', explode('|', substr($N['Artists'], 1, -1)));
    $N['Tags']         = implode(', ', explode('|', substr($N['Tags'], 1, -1)));
    $N['NotTags']      = implode(', ', explode('|', substr($N['NotTags'], 1, -1)));
    $N['ReleaseTypes'] = explode('|', substr($N['ReleaseTypes'], 1, -1));
    $N['Categories']   = explode('|', substr($N['Categories'], 1, -1));
    $N['Formats']      = explode('|', substr($N['Formats'], 1, -1));
    $N['Encodings']    = explode('|', substr($N['Encodings'], 1, -1));
    $N['Media']        = explode('|', substr($N['Media'], 1, -1));
    $N['Users']        = explode('|', substr($N['Users'], 1, -1));

    $Usernames = '';
    foreach ($N['Users'] as $UserID) {
        $UserInfo = Users::user_info($UserID);
        $Usernames .= $UserInfo['Username'] . ', ';
    }
    $Usernames = rtrim($Usernames, ', ');

    if ($N['FromYear'] === 0) {
        $N['FromYear'] = '';
    }

    if ($N['ToYear'] === 0) {
        $N['ToYear'] = '';
    }

    if ($NewFilter && $NumFilters > 0) {
        ?>
  <br><br>
  <h3>Create a new notification filter</h3>
  <?php
    } elseif ($NumFilters > 0) { ?>
  <h3>
    <a
      href="feeds.php?feed=torrents_notify_<?=$N['ID']?>_<?=$user['torrent_pass']?>&amp;user=<?=$user['ID']?>&amp;auth=<?=$user['RSS_Auth']?>&amp;passkey=<?=$user['torrent_pass']?>&amp;authkey=<?=$user['AuthKey']?>&amp;name=<?=urlencode($N['Label'])?>"><img
        src="<?=STATIC_SERVER?>/images/symbols/rss.png"
        alt="RSS feed"></a>
    <?=esc($N['Label'])?>
    <a href="user.php?action=notify_delete&amp;id=<?=$N['ID']?>&amp;auth=<?=$user['AuthKey']?>"
      onclick="return confirm('Are you sure you want to delete this notification filter?')" class="brackets">Delete</a>
    <a data-toggle-target="#filter_<?=$N['ID']?>"
      class="brackets">Show</a>
  </h3>
  <?php } ?>
  <form
    class="box pad slight_margin <?=($NewFilter ? 'create_form' : 'edit_form')?>"
    id="<?=($NewFilter ? 'filter_form' : '')?>"
    name="notification" action="user.php" method="post">
    <input type="hidden" name="formid" value="<?=$i?>">
    <input type="hidden" name="action" value="notify_handle">
    <input type="hidden" name="auth"
      value="<?=$user['AuthKey']?>">
    <?php if (!$NewFilter) { ?>
    <input type="hidden" name="id<?=$i?>"
      value="<?=$N['ID']?>">
    <?php } ?>
    <table <?=(!$NewFilter ? 'id="filter_'.$N['ID'].'" class="layout hidden"' : 'class="layout"')?>>
      <?php if ($NewFilter) { ?>

      <tr>
        <td class="label">
          <strong>Notification filter name</strong>
          <strong class="important_text">*</strong>
        </td>
        <td>
          <input type="text" class="required" name="label<?=$i?>"
            style="width: 100%;">
          A name for the notification filter set to tell different filters apart
        </td>
      </tr>

      <!--
      <tr>
        <td colspan="2" class="center">
          <strong>All fields below here are optional</strong>
        </td>
      </tr>
      -->

      <?php } ?>

      <tr>
        <td class="label"><strong>One of these artists</strong></td>
        <td>
          <textarea name="artists<?=$i?>" style="width: 100%;"
            rows="5"><?=esc($N['Artists'])?></textarea>
          Comma-separated list, e.g., Yumeno Aika, Pink Pineapple
        </td>
      </tr>

      <tr>
        <td class="label"><strong>One of these users</strong></td>
        <td>
          <textarea name="users<?=$i?>" style="width: 100%;"
            rows="5"><?=esc($Usernames)?></textarea>
          Comma-separated list of usernames
        </td>
      </tr>

      <tr>
        <td class="label"><strong>At least one of these tags</strong></td>
        <td>
          <textarea name="tags<?=$i?>" style="width: 100%;"
            rows="2"><?=esc($N['Tags'])?></textarea>
          Comma-separated list, e.g., paizuri, nakadashi
        </td>
      </tr>

      <tr>
        <td class="label"><strong>None of these tags</strong></td>
        <td>
          <textarea name="nottags<?=$i?>" style="width: 100%;"
            rows="2"><?=esc($N['NotTags'])?></textarea>
          Comma-separated list, e.g., paizuri, nakadashi
        </td>
      </tr>

      <tr>
        <td class="label"><strong>Only these categories</strong></td>
        <td>
          <?php foreach ($Categories as $Category) { ?>
          <input type="checkbox" name="categories<?=$i?>[]"
            id="<?=$Category?>_<?=$N['ID']?>"
            value="<?=$Category?>" <?php if (in_array($Category, $N['Categories'])) {
        echo ' checked="checked"';
    } ?>>
          <label
            for="<?=$Category?>_<?=$N['ID']?>"><?=$Category?></label><br />
          <?php } ?>
        </td>
      </tr>

      <tr>
        <td class="label"><strong>Only new releases</strong></td>
        <td>
          <input type="checkbox" name="newgroupsonly<?=$i?>"
            id="newgroupsonly_<?=$N['ID']?>"
            <?php if ($N['NewGroupsOnly'] == '1') { # todo: Fix strict equality checking
        echo ' checked="checked"';
    } ?>>
          <label
            for="newgroupsonly_<?=$N['ID']?>">Only
            notify for new releases, not new formats</label>
        </td>
      </tr>

      <tr>
        <td colspan="2" class="center">
          <input type="submit" class="button-primary"
            value="<?=($NewFilter ? 'Create' : 'Update')?>">
        </td>
      </tr>
    </table>
  </form>
  <?php
} ?>
</div>
<?php View::footer();

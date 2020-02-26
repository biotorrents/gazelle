<?php

/************************************************************************
||------------|| Edit torrent group wiki page ||-----------------------||

This page is the page that is displayed when someone feels like editing
a torrent group's wiki page.

It is called when $_GET['action'] == 'edit'. $_GET['groupid'] is the
ID of the torrent group and must be set.

The page inserts a new revision into the wiki_torrents table, and clears
the cache for the torrent group page.

************************************************************************/

$GroupID = $_GET['groupid'];
if (!is_number($GroupID) || !$GroupID) {
    error(0);
}

// Get the torrent group name and the body of the last revision
$DB->query("
  SELECT
    tg.Name,
    tg.NameRJ,
    tg.NameJP,
    wt.Image,
    wt.Body,
    tg.WikiImage,
    tg.WikiBody,
    tg.Year,
    tg.Studio,
    tg.Series,
    tg.DLsiteID,
    tg.CatalogueNumber,
    tg.Pages,
    tg.CategoryID,
    tg.DLsiteID
  FROM torrents_group AS tg
    LEFT JOIN wiki_torrents AS wt ON wt.RevisionID = tg.RevisionID
  WHERE tg.ID = '".db_string($GroupID)."'");
if (!$DB->has_results()) {
    error(404);
}
list($Name, $NameRJ, $NameJP, $Image, $Body, $WikiImage, $WikiBody, $Year, $Studio, $Series, $DLsiteID, $CatalogueNumber, $Pages, $CategoryID, $DLsiteID) = $DB->next_record();

$DB->query("
  SELECT
    ID, UserID, Time, Image
  FROM torrents_screenshots
  WHERE GroupID = '".db_string($GroupID)."'");

if ($DB->has_results()) {
    $Screenshots = [];
    while ($S = $DB->next_record(MYSQLI_ASSOC, true)) {
        $Screenshots[] = $S;
    }
}

$Artists = Artists::get_artists(array($GroupID))[$GroupID];

if (!$Body) {
    $Body = $WikiBody;
    $Image = $WikiImage;
}

View::show_header('Edit torrent group', 'upload,bbcode');

// Start printing form
?>
<div class="thin">
  <div class="header">
    <h2>Edit <a href="torrents.php?id=<?=$GroupID?>"><?=($Name ? $Name : ($NameRJ ? $NameRJ : $NameJP))?></a></h2>
  </div>
  <div class="box pad">
    <form class="edit_form" name="torrent_group" action="torrents.php" method="post">
      <div>
        <input type="hidden" name="action" value="takegroupedit" />
        <input type="hidden" name="auth"
          value="<?=$LoggedUser['AuthKey']?>" />
        <input type="hidden" name="groupid" value="<?=$GroupID?>" />
        <h3>Picture</h3>
        <input type="text" name="image" size="92"
          value="<?=$Image?>" /><br /><br />
        <h3>Torrent Group Descriptions</h3>
        <textarea class="bbcode_editor" name="body" cols="91"
          rows="20"><?=$Body?></textarea><br /><br />
        <?php
  $DB->query("
    SELECT UserID
    FROM torrents
    WHERE GroupID = $GroupID");

  $Contributed = in_array($LoggedUser['ID'], $DB->collect('UserID'));
?>
        <h3>Edit Summary</h3>
        <input type="text" name="summary" size="92" /><br />
        <div style="text-align: center;">
          <input type="submit" value="Submit" />
        </div>
      </div>
    </form>
  </div>
  <?php
  if ($Contributed || check_perms('torrents_edit') || check_perms('screenshots_delete') || check_perms('screenshots_add')) {
      ?>
  <h3 id="screenshots_section">Publications</h3>
  <div class="box pad">
    <!-- <p><strong class="important_text">Thumbs, promotional material, and preview images consisting of multiple images are not allowed as screenshots.</strong></p> -->
    <form class="edit_form" name="screenshots_form" action="torrents.php" method="post">
      <input type="hidden" name="action" value="screenshotedit" />
      <input type="hidden" name="auth"
        value="<?=$LoggedUser['AuthKey']?>" />
      <input type="hidden" name="groupid" value="<?=$GroupID?>" />
      <table cellpadding="3" cellspacing="1" border="0" class="layout" width="100%">
        <tr>
          <td class="label">Publications</td>
          <td id="screenshots">
            <?php
  if ($Contributed || check_perms('screenshots_add') || check_perms('torrents_edit')) {
      ?>
            <a class="float_right brackets" onclick="AddScreenshotField()">+</a>
            <?php
  }

      foreach ($Screenshots as $i => $Screenshot) {
          /* Image host integration
          $SSURL = ImageTools::process($Screenshot['Image'], 'thumb');
          */ ?>
            <div>
              <input type="text" size="45" id="ss_<?=$i?>"
                name="screenshots[]"
                value="<?=$Screenshot['Image']?>" />
              <?php
  if ($Screenshot['UserID'] == $LoggedUser['ID'] || check_perms('torrents_edit') || check_perms('screenshots_delete')) {
      ?>
              <a onclick="RemoveScreenshotField(this)" class="brackets">&minus;</a>
              <?php
  } ?>
              <br />
              <?php if (check_perms('users_mod')) { ?>
              <!--
              <img class="tooltip lightbox-init"
                title='<?=Users::format_username($Screenshot['UserID'], false, false, false)?>
              - <?=time_diff($Screenshot['Time'])?>'
              src="<?=$SSURL?>" />
              -->
              <?php } else { ?>
              <!--
              <img class="tooltip lightbox-init"
                title='Added <?=time_diff($Screenshot['Time'])?>'
              src="<?=$SSURL?>" />
              -->
              <?php } ?>
            </div>
            <br />
            <?php
      } ?>
          </td>
        </tr>
      </table>
      <div style="text-align: center;">
        <input type="submit" value="Submit" />
      </div>
    </form>
  </div>
  <?php
  }
  //Users can edit the group info if they've uploaded a torrent to the group or have torrents_edit
  if ($Contributed || check_perms('torrents_edit')) { ?>
  <h3>Non-wiki torrent group editing</h3>
  <div class="box pad">
    <form class="edit_form" name="torrent_group" action="torrents.php" method="post">
      <input type="hidden" name="action" value="nonwikiedit" />
      <input type="hidden" name="auth"
        value="<?=$LoggedUser['AuthKey']?>" />
      <input type="hidden" name="groupid" value="<?=$GroupID?>" />
      <table cellpadding="3" cellspacing="1" border="0" class="layout" width="100%">
        <tr>
          <td class="label">Author(s)
            <?php
/*
if ($CategoryID == 1) {
  echo "Idol(s):";
} elseif ($CategoryID == 2) {
  echo "Artist/Studio:";
} elseif ($CategoryID == 3) {
  echo "Artist:";
} elseif ($CategoryID == 4) {
  echo "Developer:";
} elseif ($CategoryID == 5) {
  echo "Creator/Author:";
}
*/
?>
          </td>
          <td id="idolfields">
            <input type="text" id="idols_0" name="idols[]" size="45"
              value="<?=$Artists[0]['name']?>"
              <?php Users::has_autocomplete_enabled('other'); ?>/>
            <a class="add_artist_button brackets">+</a> <a class="remove_artist_button brackets">&minus;</a>
            <?php
  for ($i = 1; $i < count($Artists); $i++) {
      print '<br><input type="text" id="idol_'.$i.'" name="idols[]" size="45" value="'.$Artists[$i]['name'].'"/>';
  }
?>
          </td>
        </tr>
        <tr>
          <td class="label">Department/Lab</td>
          <td><input type="text" id="studio" name="studio" size="60"
              value="<?=$Studio?>" /></td>
        </tr>
        <tr>
          <td class="label">Location</td>
          <td><input type="text" id="series" name="series" size="60"
              value="<?=$Series?>" /></td>
        <tr>
          <td class="label">Year</td>
          <td>
            <input type="text" name="year" size="10"
              value="<?=$Year?>" />
          </td>
        </tr>
        <tr>
          <td class="label">Accession Number</td>
          <td>
            <input type="text" name="catalogue" size="40"
              value="<?=$CatalogueNumber?>" />
          </td>
        </tr>
        <?php  if (check_perms('torrents_freeleech')) { ?>
        <tr>
          <td class="label">Torrent <strong>group</strong> leech status</td>
          <td>
            <input type="checkbox" id="unfreeleech" name="unfreeleech" /><label for="unfreeleech"> Reset</label>
            <input type="checkbox" id="freeleech" name="freeleech" /><label for="freeleech"> Freeleech</label>
            <input type="checkbox" id="neutralleech" name="neutralleech" /><label for="neutralleech"> Neutral
              Leech</label>
            because
            <select name="freeleechtype">
              <?php    $FL = array('N/A', 'Staff Pick', 'Perma-FL', 'Freeleechizer', 'Site-Wide FL');
    foreach ($FL as $Key => $FLType) { ?>
              <option value="<?=$Key?>" <?=($Key == $Torrent['FreeLeechType'] ? ' selected="selected"' : '')?>><?=$FLType?>
              </option>
              <?php    } ?>
            </select>
          </td>
        </tr>
        <?php  } ?>
      </table>
      <input type="submit" value="Edit" />
    </form>
  </div>
  <?php
  }
  if ($Contributed || check_perms('torrents_edit')) {
      ?>
  <h3>Rename (will not merge)</h3>
  <div class="box pad">
    <form class="rename_form" name="torrent_group" action="torrents.php" method="post">
      <div>
        <table cellpadding="3" cellspacing="1" border="0" class="layout" width="100%">
          <input type="hidden" name="action" value="rename" />
          <input type="hidden" name="auth"
            value="<?=$LoggedUser['AuthKey']?>" />
          <input type="hidden" name="groupid"
            value="<?=$GroupID?>" />
          <tr>
            <td class="label">Torrent Title</td>
            <td>
              <input type="text" name="name" size="70"
                value="<?=$Name?>" />
            </td>
          </tr>
          <tr>
            <td class="label">Organism</td>
            <td>
              <input type="text" name="namerj" size="70"
                value="<?=$NameRJ?>" />
            </td>
          </tr>
          <tr>
            <td class="label">Strain/Variety</td>
            <td>
              <input type="text" name="namejp" size="70"
                value="<?=$NameJP?>" />
            </td>
          </tr>
        </table>
        <div style="text-align: center;">
          <input type="submit" value="Rename" />
        </div>
      </div>
    </form>
  </div>
  <?php
  }
  if (check_perms('torrents_edit')) { ?>
  <h3>Merge with another group</h3>
  <div class="box pad">
    <form class="merge_form" name="torrent_group" action="torrents.php" method="post">
      <div>
        <input type="hidden" name="action" value="merge" />
        <input type="hidden" name="auth"
          value="<?=$LoggedUser['AuthKey']?>" />
        <input type="hidden" name="groupid" value="<?=$GroupID?>" />
        <h3>Target torrent group ID
          <input type="text" name="targetgroupid" size="10" />
        </h3>
        <div style="text-align: center;">
          <input type="submit" value="Merge" />
        </div>
      </div>
    </form>
  </div>
  <?php  } ?>
</div>
<?php View::show_footer();

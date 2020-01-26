<?php

# Line 22
// Group details
list($WikiBody, $WikiImage, $GroupID, $GroupName, $GroupNameRJ, $GroupNameJP, $GroupYear,
  $GroupStudio, $GroupSeries, $GroupCatalogueNumber, $GroupPages, $GroupCategoryID,
  $GroupDLsiteID, $GroupTime, $TorrentTags, $TorrentTagIDs, $TorrentTagUserIDs,
  $Screenshots, $Mirrors, $GroupFlags) = array_values($TorrentDetails);
# Line 26
?>

<!-- Line 967 -->
<!-- Mirrors -->
<div class="box torrent_mirrors_box <?php if (!count($Mirrors)) {
    echo 'dead';
} ?>">

<div class="head"><a href="#">&uarr;</a>&nbsp;<strong>
Mirrors (<?= count($Mirrors) ?>)</strong>
<?php
if (count($Mirrors) > 0) {
    ?>
<a class="float_right brackets" data-toggle-target=".torrent_mirrors" data-toggle-replace="Show">Hide</a>
<?php
}

$DB->query("
SELECT UserID
FROM torrents
WHERE GroupID = $GroupID");

if (in_array($LoggedUser['ID'], $DB->collect('UserID'))
 || check_perms('torrents_edit')
 || check_perms('screenshots_add')
 || check_perms('screenshots_delete')) {
?>
<a class="brackets"
  href="torrents.php?action=editgroup&groupid=<?=$GroupID?>#mirrors_section">Add/Remove</a>
<?php
}
?>
  </div>

  <div class="body torrent_mirrors">
  <?php if (!empty($Mirrors)) {
    echo '<p>Mirror links open in a new tab.</p>';
  } ?>
    <ul>
      <?php
        foreach ($Mirrors as $Mirror) {
          echo '<li><a href="'.$Mirror['Resource'].'" target="_blank">'.$Mirror['Resource'].'</a></li>';
        }
      ?>
    </ul>
  </div>
</div>
<?php
# Line 1005

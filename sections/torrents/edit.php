<?php
#declare(strict_types = 1);

$app = \Gazelle\App::go();

/**
 * Edit form
 *
 * This page relies on the TorrentForm class.
 * All it does is call the necessary functions.
 * At the bottom, there are grouping functions,
 * which are off limits to most members.
 */

require_once serverRoot.'/classes/torrent_form.class.php';
if (!is_numeric($_GET['id']) || !$_GET['id']) {
    error(400);
}

# DB query for the main torrent parameters
# todo: Simplify based on unused tables
$TorrentID = $_GET['id'];
$app->dbOld->query("
SELECT
  t.`Media`,
  t.`Container`,
  t.`Codec`,
  t.`Resolution`,
  t.`Version`,
  t.`Censored`,
  t.`Anonymous`,
  t.`Archive`,
  t.`FreeTorrent`,
  t.`FreeLeechType`,
  t.`Description` AS TorrentDescription,
  t.`FileList`,
  tg.`category_id`,
  tg.`title` AS title,
  tg.`subject` AS subject,
  tg.`object` AS object,
  tg.`year`,
  tg.`workgroup`,
  tg.`location`,
  tg.`identifier`,
  ag.`Name` AS ArtistName,
  t.`GroupID`,
  t.`UserID`,
  bt.`TorrentID` AS BadTags,
  bf.`TorrentID` AS BadFolders,
  bfi.`TorrentID` AS BadFiles
FROM
  `torrents` AS t
LEFT JOIN `torrents_group` AS tg
ON
  tg.`id` = t.`GroupID`
LEFT JOIN `torrents_artists` AS ta
ON
  tg.`id` = ta.`GroupID`
LEFT JOIN `artists_group` AS ag
ON
  ag.`ArtistID` = ta.`ArtistID`
LEFT JOIN `torrents_bad_tags` AS bt
ON
  bt.`TorrentID` = t.`ID`
LEFT JOIN `torrents_bad_folders` AS bf
ON
  bf.`TorrentID` = t.`ID`
LEFT JOIN `torrents_bad_files` AS bfi
ON
  bfi.`TorrentID` = t.`ID`
WHERE
  t.`ID` = '$TorrentID'
");

# Error on no results
list($Properties) = $app->dbOld->to_array(false, MYSQLI_BOTH);
if (!$Properties) {
    error(404);
}

# Error on bad permissions
$UploadForm = $Categories[$Properties['CategoryID'] - 1];
if (($app->user->core['id'] !== $Properties['UserID']
  && !check_perms('torrents_edit'))
  || $app->user->extra['DisableWiki']) {
    error(403);
}


/**
 * Torrent Form
 *
 * This is the actual edit form.
 * Commenting only to see it better.
 */

View::header('Edit torrent', 'upload,torrent');
$TorrentForm = new TorrentForm(
    $Torrent = $Properties,
    $Error = $Err,
    $NewTorrent = false
);
$TorrentForm->upload_form();


/**
 * Moderator tools
 *
 * Various inlined tools to manage torrent grouping, etc.
 */
if (check_perms('torrents_edit') || check_perms('users_mod')) { ?>
<!-- Start HTML -->


<!--
  Change Group
-->
<div class="header">
  <h2>Change Group</h2>
</div>

<div class="box pad">
  <form class="edit_form" name="torrent_group" action="torrents.php" method="post">

    <!-- Hidden values -->
    <input type="hidden" name="action" value="editgroupid">

    <input type="hidden" name="auth"
      value="<?=$app->user->extra['AuthKey']?>" />

    <input type="hidden" name="torrentid" value="<?=$TorrentID?>">

    <input type="hidden" name="oldgroupid"
      value="<?=$Properties['GroupID']?>">

    <!-- Formlet table -->
    <table class="layout">
      <tr>
        <td class="label">
          Group ID
        </td>

        <td>
          <input type="text" name="groupid"
            value="<?=$Properties['GroupID']?>"
            size="10" />
        </td>
      </tr>

      <tr>
        <td colspan="2" class="center">
          <input type="submit" value="Change Group ID">
        </td>
      </tr>
    </table>
  </form>
</div> <!-- box pad -->


<!--
  Split off into new group
-->

<h2 class="header">
  Split off into new group
</h2>

<div class="box pad">
  <form class="split_form" name="torrent_group" action="torrents.php" method="post">

    <!-- Hidden values -->
    <input type="hidden" name="action" value="newgroup">

    <input type="hidden" name="auth"
      value="<?=$app->user->extra['AuthKey']?>" />

    <input type="hidden" name="torrentid" value="<?=$TorrentID?>">

    <input type="hidden" name="oldgroupid"
      value="<?=$Properties['GroupID']?>">

    <!-- Formlet table -->
    <table class="layout">
      <tr>
        <td class="label">
          Author
        </td>

        <td>
          <input type="text" name="artist"
            value="<?=$Properties['ArtistName']?>"
            size="50">
        </td>
      </tr>


      <tr>
        <td class="label">
          Torrent Title
        </td>

        <td>
          <input type="text" name="title"
            value="<?=$Properties['Title']?>"
            size="50">
        </td>
      </tr>


      <tr>
        <td class="label">
          Organism
        </td>

        <td>
          <input type="test" name="title_rj"
            value="<?=$Properties['Title2']?>"
            size=50">
        </td>
      </tr>


      <tr>
        <td class="label">
          Strain/Variety
        </td>

        <td>
          <input type="test" name="title_jp"
            value="<?=$Properties['TitleJP']?>"
            size=50">
        </td>
      </tr>


      <tr>
        <td class="label">
          Year
        </td>

        <td>
          <input type="text" name="year"
            value="<?=$Properties['Year']?>"
            size="10">
        </td>
      </tr>


      <tr>
        <td colspan="2" class="center">
          <input type="submit" value="Split into new group">
        </td>
      </tr>
    </table>
  </form>
</div> <!-- box pad -->

<?php
    if (check_perms('users_mod')) { ?>

<!-- Change Category -->
<h2>Change Category</h2>
<div class="box pad">
  <form action="torrents.php" method="post">
    <input type="hidden" name="action" value="changecategory">
    <input type="hidden" name="auth"
      value="<?=$app->user->extra['AuthKey']?>" />
    <input type="hidden" name="torrentid" value="<?=$TorrentID?>">
    <input type="hidden" name="oldgroupid"
      value="<?=$Properties['GroupID']?>">
    <input type="hidden" name="oldartistid"
      value="<?=$Properties['ArtistID']?>">
    <input type="hidden" name="oldcategoryid"
      value="<?=$Properties['CategoryID']?>">

    <table>
      <tr>
        <td class="label">Category</td>
        <td>
          <select id="newcategoryid" name="newcategoryid">
            <?php foreach ($Categories as $CatID => $CatName) { ?>
            <option value="<?=($CatID + 1)?>"
              <?Format::selected('CategoryID', $CatID + 1, 'selected', $Properties)?>><?=($CatName)?>
            </option>
            <?php } ?>
          </select>
        </td>
      </tr>

      <tr id="split_artist">
        <td class="label">Author</td>
        <td>
          <input type="text" name="artist"
            value="<?=$Properties['ArtistName']?>"
            size="50">
        </td>
      </tr>

      <tr id="split_title">
        <td class="label">Torrent Title</td>
        <td>
          <input type="text" name="title"
            value="<?=$Properties['Title']?>"
            size="50">
        </td>
      </tr>

      <tr id="split_year">
        <td class="label">Year</td>
        <td>
          <input type="text" name="year"
            value="<?=$Properties['Year']?>"
            size="10">
        </td>
      </tr>

      <tr>
        <td colspan="2" class="center">
          <input type="submit" value="Change Category">
        </td>
      </tr>
    </table>
  </form>
</div>
<?php
    } ?>
<?php
} // if check_perms('torrents_edit')
View::footer();

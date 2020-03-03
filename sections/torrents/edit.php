<?php

//**********************************************************************//
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~ Edit form ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//
// This page relies on the TorrentForm class. All it does is call      //
// the necessary functions.                                             //
//----------------------------------------------------------------------//
// At the bottom, there are grouping functions which are off limits to  //
// most members.                                                        //
//**********************************************************************//

require_once(SERVER_ROOT.'/classes/torrent_form.class.php');

if (!is_number($_GET['id']) || !$_GET['id']) {
    error(0);
}

$TorrentID = $_GET['id'];
$DB->query("
  SELECT
    t.Media,
    t.Container,
    t.Codec,
    t.Resolution,
    t.AudioFormat,
    t.Subbing,
    t.Language,
    t.Subber,
    t.Censored,
    t.Anonymous,
    t.Archive,
    t.FreeTorrent,
    t.FreeLeechType,
    t.Description AS TorrentDescription,
    t.FileList,
    t.MediaInfo,
    tg.CategoryID,
    tg.Name AS Title,
    tg.NameJP AS TitleJP,
    tg.Year,
    tg.Studio,
    tg.Series,
    tg.CatalogueNumber,
    ag.Name AS ArtistName,
    t.GroupID,
    t.UserID,
    bt.TorrentID AS BadTags,
    bf.TorrentID AS BadFolders,
    bfi.TorrentID AS BadFiles
  FROM torrents AS t
    LEFT JOIN torrents_group AS tg ON tg.ID = t.GroupID
    LEFT JOIN torrents_artists AS ta ON tg.ID = ta.GroupID
    LEFT JOIN artists_group AS ag ON ag.ArtistID = ta.ArtistID
    LEFT JOIN torrents_bad_tags AS bt ON bt.TorrentID = t.ID
    LEFT JOIN torrents_bad_folders AS bf ON bf.TorrentID = t.ID
    LEFT JOIN torrents_bad_files AS bfi ON bfi.TorrentID = t.ID
  WHERE t.ID = '$TorrentID'");

list($Properties) = $DB->to_array(false, MYSQLI_BOTH);
if (!$Properties) {
    error(404);
}

$UploadForm = $Categories[$Properties['CategoryID'] - 1];

if (($LoggedUser['ID'] !== $Properties['UserID'] && !check_perms('torrents_edit')) || $LoggedUser['DisableWiki']) {
    error(403);
}

View::show_header('Edit torrent', 'upload,torrent,bbcode');

$TorrentForm = new TorrentForm($Properties, $Err, false);

$TorrentForm->upload_form();

if (check_perms('torrents_edit') || check_perms('users_mod')) {
    # Start the HTML edit form
    ?>
<div class="thin">
  <?php
  #if ($Properties['CategoryID'] !== 5) {
      ?>

  <br />
  <div class="header">
    <h2>Change Group</h2>
  </div>
  <div class="box pad">
    <form class="edit_form" name="torrent_group" action="torrents.php" method="post">
      <input type="hidden" name="action" value="editgroupid" />
      <input type="hidden" name="auth"
        value="<?=$LoggedUser['AuthKey']?>" />
      <input type="hidden" name="torrentid"
        value="<?=$TorrentID?>" />
      <input type="hidden" name="oldgroupid"
        value="<?=$Properties['GroupID']?>" />
      <table class="layout">
        <tr>
          <td class="label">Group ID</td>
          <td>
            <input type="text" name="groupid"
              value="<?=$Properties['GroupID']?>"
              size="10" />
          </td>
        </tr>
        <tr>
          <td colspan="2" class="center">
            <input type="submit" value="Change Group ID" />
          </td>
        </tr>
      </table>
    </form>
  </div>
  <br />

  <h2>Split off into new group</h2>
  <div class="box pad">
    <form class="split_form" name="torrent_group" action="torrents.php" method="post">
      <input type="hidden" name="action" value="newgroup" />
      <input type="hidden" name="auth"
        value="<?=$LoggedUser['AuthKey']?>" />
      <input type="hidden" name="torrentid"
        value="<?=$TorrentID?>" />
      <input type="hidden" name="oldgroupid"
        value="<?=$Properties['GroupID']?>" />
      <table class="layout">
        <tr>
          <td class="label">Author</td>
          <td>
            <input type="text" name="artist"
              value="<?=$Properties['ArtistName']?>"
              size="50" />
          </td>
        </tr>
        <tr>
          <td class="label">Torrent Title</td>
          <td>
            <input type="text" name="title"
              value="<?=$Properties['Title']?>"
              size="50" />
          </td>
        </tr>
        <tr>
          <td class="label">Strain/Variety</td>
          <td>
            <input type="test" name="title_jp"
              value="<?=$Properties['TitleJP']?>"
              size=50" />
          </td>
        <tr>
          <td class="label">Year</td>
          <td>
            <input type="text" name="year"
              value="<?=$Properties['Year']?>"
              size="10" />
          </td>
        </tr>
        <tr>
          <td colspan="2" class="center">
            <input type="submit" value="Split into new group" />
          </td>
        </tr>
      </table>
    </form>
  </div>
  <br />

  <?php
  #}
    if (check_perms('users_mod')) { ?>
  <h2>Change Category</h2>
  <div class="box pad">
    <form action="torrents.php" method="post">
      <input type="hidden" name="action" value="changecategory" />
      <input type="hidden" name="auth"
        value="<?=$LoggedUser['AuthKey']?>" />
      <input type="hidden" name="torrentid"
        value="<?=$TorrentID?>" />
      <input type="hidden" name="oldgroupid"
        value="<?=$Properties['GroupID']?>" />
      <input type="hidden" name="oldartistid"
        value="<?=$Properties['ArtistID']?>" />
      <input type="hidden" name="oldcategoryid"
        value="<?=$Properties['CategoryID']?>" />
      <table>
        <tr>
          <td class="label">Category</td>
          <td>
            <select id="newcategoryid" name="newcategoryid">
              <?php    foreach ($Categories as $CatID => $CatName) { ?>
              <option value="<?=($CatID + 1)?>" <?Format::selected('CategoryID', $CatID + 1, 'selected', $Properties)?>><?=($CatName)?>
              </option>
              <?php    } ?>
            </select>
          </td>
          </tr>
        <tr id="split_artist">
          <td class="label">Artist</td>
          <td>
            <input type="text" name="artist"
              value="<?=$Properties['ArtistName']?>"
              size="50" />
          </td>
        </tr>
        <tr id="split_title">
          <td class="label">Torrent Title</td>
          <td>
            <input type="text" name="title"
              value="<?=$Properties['Title']?>"
              size="50" />
          </td>
        </tr>
        <tr id="split_year">
          <td class="label">Year</td>
          <td>
            <input type="text" name="year"
              value="<?=$Properties['Year']?>"
              size="10" />
          </td>
        </tr>
        <tr>
          <td colspan="2" class="center">
            <input type="submit" value="Change Category" />
          </td>
        </tr>
      </table>
      <script type="text/javascript">
        ChangeCategory($('#newcategoryid').raw().value);
      </script>
    </form>
  </div>
  <?php
  } ?>
</div>
<?php
} // if check_perms('torrents_edit')

View::show_footer();

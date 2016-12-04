<?

/********************************************************************************
 ************ Torrent form class *************** upload.php and torrents.php ****
 ********************************************************************************
 ** This class is used to create both the upload form, and the 'edit torrent'  **
 ** form. It is broken down into several functions - head(), foot(),           **
 ** music_form() [music], audiobook_form() [Audiobooks and comedy], and         **
 ** simple_form() [everything else].                                           **
 **                                                                            **
 ** When it is called from the edit page, the forms are shortened quite a bit. **
 **                                                                            **
 ********************************************************************************/

class TORRENT_FORM {
  var $UploadForm = '';
  var $Categories = array();
  var $Formats = array();
  var $Bitrates = array();
  var $Media = array();
  var $MediaManaga = array();
  var $Containers = array();
  var $ContainersGames = array();
  var $Codecs = array();
  var $Resolutions = array();
  var $AudioFormats = array();
  var $Subbing = array();
  var $Languages = array();
  var $Platform = array();
  var $NewTorrent = false;
  var $Torrent = array();
  var $Error = false;
  var $TorrentID = false;
  var $Disabled = '';
  var $DisabledFlag = false;

  function TORRENT_FORM($Torrent = false, $Error = false, $NewTorrent = true) {

    $this->NewTorrent = $NewTorrent;
    $this->Torrent = $Torrent;
    $this->Error = $Error;

    global $UploadForm, $Categories, $Formats, $Bitrates, $Media, $MediaManga, $TorrentID, $Containers, $ContainersGames, $Codecs, $Resolutions, $AudioFormats, $Subbing, $Languages, $Platform, $Archives, $ArchivesManga;

    $this->UploadForm = $UploadForm;
    $this->Categories = $Categories;
    $this->Formats = $Formats;
    $this->Bitrates = $Bitrates;
    $this->Media = $Media;
    $this->MediaManga = $MediaManga;
    $this->Containers = $Containers;
    $this->ContainersGames = $ContainersGames;
    $this->Codecs = $Codecs;
    $this->Resolutions = $Resolutions;
    $this->AudioFormats = $AudioFormats;
    $this->Subbing = $Subbing;
    $this->Languages = $Languages;
    $this->TorrentID = $TorrentID;
    $this->Platform = $Platform;
    $this->Archives = $Archives;
    $this->ArchivesManga = $ArchivesManga;

    if ($this->Torrent && $this->Torrent['GroupID']) {
      $this->Disabled = ' readonly="readonly"';
      $this->DisabledFlag = true;
    }
  }

  function head() {
    G::$DB->query("
      SELECT COUNT(ID)
      FROM torrents
      WHERE UserID = ".G::$LoggedUser['ID']);
    list($Uploads) = G::$DB->next_record();
?>

<div class="thin">
<?    if ($this->NewTorrent) { ?>
  <p style="text-align: center;">
    Your personal announce URL is:<br />
    <input type="text" value="<?= ANNOUNCE_URLS[0][0] . '/' . G::$LoggedUser['torrent_pass'] . '/announce'?>" size="71" onclick="this.select();" readonly="readonly" />
    <p style="text-align: center;">
    <strong<?=((!$Uploads)?' class="important_text"':'')?>>
        If you never have before, be sure to read this list of <a href="wiki.php?action=article&name=uploadingpitfalls">uploading pitfalls</a>
      </strong>
    </p>
  </p>
<?    }
    if ($this->Error) {
      echo "\t".'<p style="color: red; text-align: center;">'.$this->Error."</p>\n";
    }
?>
  <form class="create_form box pad" name="torrent" action="" enctype="multipart/form-data" method="post" onsubmit="$('#post').raw().disabled = 'disabled';">
    <div>
      <input type="hidden" name="submit" value="true" />
      <input type="hidden" name="auth" value="<?=G::$LoggedUser['AuthKey']?>" />
<?    if (!$this->NewTorrent) { ?>
      <input type="hidden" name="action" value="takeedit" />
      <input type="hidden" name="torrentid" value="<?=display_str($this->TorrentID)?>" />
      <input type="hidden" name="type" value="<?=display_str($this->Torrent['CategoryID']-1)?>" />
<?
    } else {
      if ($this->Torrent && $this->Torrent['GroupID']) {
?>
      <input type="hidden" name="groupid" value="<?=display_str($this->Torrent['GroupID'])?>" />
      <input type="hidden" name="type" value="<?=display_str($this->Torrent['CategoryID']-1)?>" />
<?
      }
      if ($this->Torrent && $this->Torrent['RequestID']) {
?>
      <input type="hidden" name="requestid" value="<?=display_str($this->Torrent['RequestID'])?>" />
<?
      }
    }
?>
    </div>
<?    if ($this->NewTorrent) { ?>
    <table cellpadding="3" cellspacing="1" border="0" class="layout" width="100%">
      <tr>
        <td class="label">Torrent file:</td>
        <td><input id="file" type="file" name="file_input" size="50" /></td>
      </tr>
      <tr>
        <td class="label">Type:</td>
        <td>
          <select id="categories" name="type" onchange="Categories()"<?=($this->DisabledFlag) ? ' disabled="disabled"' : ''?>>
<?
      foreach (Misc::display_array($this->Categories) as $Index => $Cat) {
        echo "\t\t\t\t\t\t<option value=\"$Index\"";
        if ($Cat == $this->Torrent['CategoryName']) {
          echo ' selected="selected"';
        }
        echo ">$Cat</option>\n";
      }
?>
          </select>
        </td>
      </tr>
    </table>
<?    }//if ?>
    <div id="dynamic_form">
<?
  } // function head


  function foot() {
    $Torrent = $this->Torrent;
?>
    </div>
    <table cellpadding="3" cellspacing="1" border="0" class="layout slice" width="100%">
<?
    if (!$this->NewTorrent) {
      if (check_perms('torrents_freeleech')) {
?>
      <tr id="freetorrent">
        <td class="label">Freeleech</td>
        <td>
          <select name="freeleech">
<?
        $FL = array("Normal", "Free", "Neutral");
        foreach ($FL as $Key => $Name) {
?>
            <option value="<?=$Key?>"<?=($Key == $Torrent['FreeTorrent'] ? ' selected="selected"' : '')?>><?=$Name?></option>
<?        } ?>
          </select>
          because
          <select name="freeleechtype">
<?
        $FL = array("N/A", "Staff Pick", "Perma-FL", "Freeleechizer", "Site-Wide FL");
        foreach ($FL as $Key => $Name) {
?>
            <option value="<?=$Key?>"<?=($Key == $Torrent['FreeLeechType'] ? ' selected="selected"' : '')?>><?=$Name?></option>
<?        } ?>
          </select>
        </td>
      </tr>
<?
      }
    }
?>
      <tr>
        <td colspan="2" style="text-align: center;">
          <p>Be sure that your torrent is approved by the <a href="rules.php?p=upload" target="_blank">rules</a>. Not doing this will result in a <strong class="important_text">warning</strong> or <strong class="important_text">worse</strong>.</p>
<?    if ($this->NewTorrent) { ?>
          <p>After uploading the torrent, you will have a one hour grace period during which no one other than you can fill requests with this torrent. Make use of this time wisely, and <a href="requests.php">search the list of requests</a>.</p>
<?    } ?>
        <input id="post" type="submit"<? if ($this->NewTorrent) { echo ' value="Upload torrent"'; } else { echo ' value="Edit torrent"';} ?> />
        </td>
      </tr>
    </table>
  </form>
</div>
<?
  } //function foot


  function movies_form($GenreTags) {
    $QueryID = G::$DB->get_query_id();
    $Torrent = $this->Torrent;
?>
    <table cellpadding="3" cellspacing="1" border="0" class="layout slice" width="100%">
<? if ($this->NewTorrent) { ?>
      <tr id="javdb_tr">
        <td class="label tooltip" title='Enter a JAV catalogue number, e.g., "CND-060"'>Catalogue Number:</td>
        <td>
          <input type="text" id="catalogue" name="catalogue" size="10" value="<?=display_str($Torrent['CatalogueNumber']) ?>" <?=$this->Disabled?>/>
<? if (!$this->DisabledFlag) { ?>
          <input type="button" autofill="jav" value="Autofill"></input>
<? } ?>
        </td>
      </tr>
      <tr id="title_tr">
        <td class="label">Title:</td>
        <td><input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr>
        <td class="label">Japanese Title:</td>
        <td><input type="text" id="title_jp" name="title_jp" size="60" value="<?=display_str($Torrent['TitleJP']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr>
        <td class="label">Idol(s):</td>
        <td id="idolfields">
<?      if (!empty($Torrent['Artists'])) {
          foreach ($Torrent['Artists'] as $Num => $Artist) { ?>
            <input type="text" id="idols_<?=$Num?>" name="idols[]" size="45" value="<?=display_str($Artist['name'])?>" <?=$this->Disabled?>/>
            <? if ($Num == 0) { ?>
              <a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
            <? }
            }
          } else { ?>
            <input type="text" id="idols_0" name="idols[]" size="45" value="" <?=$this->Disabled?> />
            <a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
<?        } ?>
        </td>
      </tr>
      <tr>
        <td class="label">Studio:</td>
        <td><input type="text" id="studio" name="studio" size="60" value="<?=display_str($Torrent['Studio']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr>
        <td class="label">Series:</td>
        <td><input type="text" id="series" name="series" size="60" value="<?=display_str($Torrent['Series']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr id="year_tr">
        <td class="label">Year:</td>
        <td><input type="text" id="year" name="year" maxlength="4" size="5" value="<?=display_str($Torrent['Year']) ?>" <?=$this->Disabled?>/></td>
      </tr>
<? } ?>
      <tr>
        <td class="label">Media:</td>
        <td>
          <select name="media">
            <option>---</option>
<?
    foreach($this->Media as $Media) {
      echo "\t\t\t\t\t\t<option value=\"$Media\"";
      if ($Media == $Torrent['Media']) {
        echo " selected";
      }
      echo ">$Media</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Container:</td>
        <td>
          <select name="container">
            <option>---</option>
<?
    foreach($this->Containers as $Cont) {
      echo "\t\t\t\t\t\t<option value=\"$Cont\"";
      if ($Cont == $Torrent['Container']) {
        echo " selected";
      }
      echo ">$Cont</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Codecs:</td>
        <td>
          <select name="codec">
            <option>---</option>
<?
    foreach($this->Codecs as $Codec) {
      echo "\t\t\t\t\t\t<option value=\"$Codec\"";
      if ($Codec == $Torrent['Codec']) {
        echo " selected";
      }
      echo ">$Codec</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Resolution:</td>
        <td>
          <select id="ressel" name="ressel" onchange="SetResolution()">
            <option>---</option>
<?
    foreach($this->Resolutions as $Res) {
      echo "\t\t\t\t\t\t<option value=\"$Res\"";
      if ($Res == $Torrent['Resolution'] || (!isset($FoundRes) && isset($Torrent['Resolution']) && $Res == "Other")) {
        echo " selected";
        $FoundRes = true;
      }
      echo ">$Res</option>\n";
    }
?>
          </select>
          <input type="text" id="resolution" name="resolution" size="10" class="hidden tooltip" title='Enter "Other" resolutions in the form ###x###' value="<?=$Torrent['Resolution']?>" readonly></input>
          <script>
            if ($('#ressel').raw().value == "Other") {
              $('#resolution').raw().readOnly = false
              $('#resolution').gshow()
            }
          </script>
        </td>
      </tr>
      <tr>
        <td class="label">Audio:</td>
        <td>
          <select name="audioformat">
            <option>---</option>
<?
    foreach($this->AudioFormats as $AudioFormat) {
      echo "\t\t\t\t\t\t<option value=\"$AudioFormat\"";
      if  ($AudioFormat == $Torrent['AudioFormat']) {
        echo " selected";
      }
      echo ">$AudioFormat</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Subbing:</td>
        <td>
          <select name="sub">
            <option>---</option>
<?
    foreach($this->Subbing as $Subbing) {
      echo "\t\t\t\t\t\t<option value=\"$Subbing\"";
      if ($Subbing == $Torrent['Subbing']) {
        echo " selected";
      }
      echo ">$Subbing</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Language:</td>
        <td>
          <select name="lang">
            <option>---</option>
<?
    foreach($this->Languages as $Language) {
      echo "\t\t\t\t\t\t<option value=\"$Language\"";
      if ($Language == $Torrent['Language']) {
        echo " selected";
      }
      echo ">$Language</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Censored?:</td>
        <td>
          <input type="checkbox" name="censored" value="1" <?=(($Torrent['Censored'] ?? 1) ? 'checked ' : '')?>/>
        </td>
      </tr>
      <tr>
        <td class="label">Media Info:</td>
        <td>
          <textarea name="mediainfo" id="mediainfo" onchange="MediaInfoExtract()"  rows="8" cols="60"><?=display_str($Torrent['MediaInfo'])?></textarea>
        </td>
      </tr>
      <!--<tr>
        <td class="label">Release Group (optional):</td>
        <td><input type="text" id="release" name="release" size="60" /></td>
      </tr>-->
<?    if ($this->NewTorrent) { ?>
      <tr>
        <td class="label tooltip" title="Comma seperated list of tags">Tags:</td>
        <td>
<?
  $GenreTags = G::$Cache->get_value('genre_tags');
  if (!$GenreTags) {
    $DB->query("
      SELECT Name
      FROM tags
      WHERE TagType = 'genre'
      ORDER BY Name");
    $GenreTags = $DB->collect('Name');
    G::$Cache->cache_value('genre_tags', $GenreTags, 3600*6);
  }
?>
          <select id="genre_tags" name="genre_tags" onchange="add_tag(); return false;" <?=($this->DisabledFlag) ? ' disabled="disabled"' : ''?>>
            <option>---</option>
<? foreach (Misc::display_array($GenreTags) as $Genre) { ?>
            <option value="<?=$Genre?>"><?=$Genre?></option>
<? } ?>
          </select>
          <input type="text" id="tags" name="tags" size="60" value="<?=display_str($Torrent['TagList']) ?>"<? Users::has_autocomplete_enabled('other'); ?> />
        </td>
      </tr>
      <tr>
        <td class="label">Cover Image:</td>
        <td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image']) ?>"<?=$this->Disabled?> /></td>
      </tr>
<? if (!$this->DisabledFlag && $this->NewTorrent) { ?>
      <tr>
        <td class="label">Screenshots:</td>
        <td>
          <textarea rows="8" cols="60" name="screenshots" id="screenshots"><?=display_str($Torrent['Screenshots'])?></textarea>
          <p>Enter up to 10 links to screenshots for the torrent, one per line. The system will automatically remove malformed or invalid links, as well as any links after the 10th. Remember to consult the <a href="/rules.php?p=upload#h1.4">rules for adding screenshots</a>.</p>
      </tr>
<? } ?>
      <tr>
        <td class="label">Torrent Group Description:</td>
        <td>
<?php new TEXTAREA_PREVIEW('album_desc', 'album_desc', display_str($Torrent['GroupDescription']), 60, 8, !$this->DisabledFlag, !$this->DisabledFlag, false, array($this->Disabled)); ?>
          <p class="min_padding">Contains information such as a description of the movie, a link to a JAV catalogue, etc.</p>
        </td>
      </tr>
<?    } ?>
      <tr>
        <td class="label">Torrent Description (optional):</td>
        <td>
<?php new TEXTAREA_PREVIEW('release_desc', 'release_desc', display_str($Torrent['TorrentDescription']), 60, 8); ?>
          <p class="min_padding">Contains information such as encoder settings.</p>
        </td>
      </tr>
    </table>

<?
    //  For AJAX requests (e.g. when changing the type from Music to Applications),
    //  we don't need to include all scripts, but we do need to include the code
    //  that generates previews. It will have to be eval'd after an AJAX request.
    if ($_SERVER['SCRIPT_NAME'] === '/ajax.php')
      TEXTAREA_PREVIEW::JavaScript(false);

    G::$DB->set_query_id($QueryID);
  }//function music_form

  function anime_form() {
    $Torrent = $this->Torrent;
?>
    <table cellpadding="3" cellspacing="1" border="0" class="layout slice" width="100%">
<?    if ($this->NewTorrent) { ?>
      <tr id="anidb_tr">
        <td class="label">AniDB Autofill (optional):</td>
        <td>
          <input type="text" id="anidb" size="10" <?=$this->Disabled?>/>
<? if (!$this->DisabledFlag) { ?>
          <input type="button" autofill="anime" value="Autofill"/>
<? } ?>
        </td>
      </tr>
      <tr id="title_tr">
        <td class="label">Title:</td>
        <td><input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr>
        <td class="label">Japanese Title:</td>
        <td><input type="text" id="title_jp" name="title_jp" size="60" value="<?=display_str($Torrent['TitleJP']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr>
        <td class="label">Artist/Studio:</td>
        <td id="idolfields">
<?      if (!empty($Torrent['Artists'])) {
          foreach ($Torrent['Artists'] as $Num => $Artist) { ?>
            <input type="text" id="idols_<?=$Num?>" name="idols[]" size="45" value="<?=display_str($Artist['name'])?>" <?=$this->Disabled?> />
            <? if ($Num == 0) { ?>
              <a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
            <? }
            }
          } else { ?>
            <input type="text" id="idols_0" name="idols[]" size="45" value="" <?=$this->Disabled?> />
            <a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
<?        } ?>
        </td>
      </tr>
      <tr>
        <td class="label">Circle (Optional):</td>
        <td><input type="text" id="series" name="series" size="60" value="<?=display_str($Torrent['Series']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr id="year_tr">
        <td class="label">Year:</td>
        <td><input type="text" id="year" name="year" maxlength="4" size="5" value="<?=display_str($Torrent['Year']) ?>" <?=$this->Disabled?> /></td>
      </tr>
<?    } ?>
      <tr>
        <td class="label">Media:</td>
        <td>
          <select name="media">
            <option>---</option>
<?
    foreach($this->Media as $Media) {
      echo "\t\t\t\t\t\t<option value=\"$Media\"";
      if ($Media == $Torrent['Media']) {
        echo " selected";
      }
      echo ">$Media</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Container:</td>
        <td>
          <select name="container">
            <option>---</option>
<?
    foreach($this->Containers as $Container) {
      echo "\t\t\t\t\t\t<option value=\"$Container\"";
      if ($Container == $Torrent['Container']) {
        echo " selected";
      }
      echo ">$Container</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Codecs:</td>
        <td>
          <select name="codec">
            <option>---</option>
<?
    foreach($this->Codecs as $Codec) {
      echo "\t\t\t\t\t\t<option value=\"$Codec\"";
      if ($Codec == $Torrent['Codec']) {
        echo " selected";
      }
      echo ">$Codec</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Resolution:</td>
        <td>
          <select id="ressel" name="ressel" onchange="SetResolution()">
            <option>---</option>
<?
    foreach($this->Resolutions as $Res) {
      echo "\t\t\t\t\t\t<option value=\"$Res\"";
      if ($Res == $Torrent['Resolution'] || (!isset($FoundRes) && isset($Torrent ['Resolution']) && $Res == "Other")) {
        echo " selected";
        $FoundRes = true;
      }
      echo ">$Res</option>\n";
    }
?>
          </select>
          <input type="text" id="resolution" name="resolution" size="10" class="hidden tooltip" title='Enter "Other" resolutions in the form ###x###' value="<?=$Torrent['Resolution']?>" readonly></input>
          <script>
            if ($('#ressel').raw().value == "Other") {
              $('#resolution').raw().readOnly = false
              $('#resolution').gshow()
            }
          </script>
        </td>
      </tr>
      <tr>
        <td class="label">Audio:</td>
        <td>
          <select name="audioformat">
            <option>---</option>
<?
    foreach($this->AudioFormats as $AudioFormat) {
      echo "\t\t\t\t\t\t<option value=\"$AudioFormat\"";
      if ($AudioFormat == $Torrent['AudioFormat']) {
        echo " selected";
      }
      echo ">$AudioFormat</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Language:</td>
        <td>
          <select name="lang">
            <option>---</option>
<?
    foreach($this->Languages as $Language) {
      echo "\t\t\t\t\t\t<option value=\"$Language\"";
      if ($Language == $Torrent['Language']) {
        echo " selected";
      }
      echo ">$Language</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Subbing:</td>
        <td>
          <select name="sub" onchange="DisplayTrans()">
            <option>---</option>
<?
    foreach($this->Subbing as $Subbing) {
      echo "\t\t\t\t\t\t<option value=\"$Subbing\"";
      if ($Subbing == $Torrent['Subbing']) {
        echo " selected";
      }
      echo ">$Subbing</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Translation Group (optional):</td>
        <td><input type="text" id="subber" name="subber" size="60" value="<?=display_str($Torrent['Subber']) ?>" /></td>
      </tr>
      <tr>
        <td class="label">Censored?:</td>
        <td>
          <input type="checkbox" name="censored" value="censored" <?=(($Torrent['Censored'] ?? 1) ? 'checked ' : '')?> />
        </td>
      </tr>
      <tr>
        <td class="label">Media Info:</td>
        <td>
          <textarea name="mediainfo" id="mediainfo" onchange="MediaInfoExtract()" rows="8" cols="60"><?=display_str($Torrent['MediaInfo'])?></textarea>
        </td>
      </tr>
<?    if ($this->NewTorrent) { ?>
      <tr>
        <td class="label tooltip" title="Comma seperated list of tags">Tags:</td>
        <td>
<?
  $GenreTags = G::$Cache->get_value('genre_tags');
  if (!$GenreTags) {
    $DB->query("
      SELECT Name
      FROM tags
      WHERE TagType = 'genre'
      ORDER BY Name");
    $GenreTags = $DB->collect('Name');
    G::$Cache->cache_value('genre_tags', $GenreTags, 3600*6);
  }
?>
          <select id="genre_tags" name="genre_tags" onchange="add_tag(); return false;" <?=($this->DisabledFlag) ? ' disabled="disabled"' : ''?>>
            <option>---</option>
<? foreach (Misc::display_array($GenreTags) as $Genre) { ?>
            <option value="<?=$Genre?>"><?=$Genre?></option>
<? } ?>
          </select>
          <input type="text" id="tags" name="tags" size="60" value="<?=display_str($Torrent['TagList']) ?>"<? Users::has_autocomplete_enabled('other'); ?> />
        <p class="min_padding">Remember to use the '3d' tag if your upload is 3DCG!</p>
        </td>
      </tr>
      <tr>
        <td class="label">Cover Image:</td>
        <td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image']) ?>"<?=$this->Disabled?> /></td>
      </tr>
<? if (!$this->DisabledFlag && $this->NewTorrent) { ?>
      <tr>
        <td class="label">Screenshots:</td>
        <td>
          <textarea rows="8" cols="60" name="screenshots" id="screenshots"><?=display_str($Torrent['Screenshots'])?></textarea>
          <p>Enter up to 10 links to screenshots for the torrent, one per line. The system will automatically remove malformed or invalid links, as well as any links after the 10th. Remember to consult the <a href="/rules.php?p=upload#h1.4">rules for adding screenshots</a>.</p>
      </tr>
<? } ?>
      <tr>
        <td class="label">Torrent Group Description:</td>
        <td>
<?php new TEXTAREA_PREVIEW('album_desc', 'album_desc', display_str($Torrent['GroupDescription']), 60, 8, !$this->DisabledFlag, !$this->DisabledFlag, false, array($this->Disabled)); ?>
          <p class="min_padding">Contains information such as a description of the anime, a link to AniDB, etc.</p>
        </td>
      </tr>
<?    } ?>
      <tr>
        <td class="label">Torrent Description (optional):</td>
        <td>
<?php new TEXTAREA_PREVIEW('release_desc', 'release_desc', display_str($Torrent['TorrentDescription']), 60, 8); ?>
          <p class="min_padding">Contains information such as encoder settings.</p>
        </td>
      </tr>
    </table>
<?
    if ($_SERVER['SCRIPT_NAME'] === '/ajax.php')
      TEXTAREA_PREVIEW::JavaScript(false);
  }//function audiobook_form

  function manga_form() {
    $Torrent = $this->Torrent;
?>
    <table cellpadding="3" cellspacing="1" border="0" class="layout slice" width="100%">
<?    if ($this->NewTorrent) { ?>
      <tr id="catalogue_tr">
        <td class="label">e-hentai URL (optional):</td>
        <td>
          <input type="text" id="catalogue" size="50" <?=$this->Disabled?> />
<? if (!$this->DisabledFlag) { ?>
          <input type="button" autofill="douj" value="Autofill"/>
<? } ?>
        </td>
      </tr>
      <tr id="title_tr">
        <td class="label">Title:</td>
        <td><input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title']) ?>" <?=$this->Disabled?> /></td>
      </tr>
      <tr>
        <td class="label">Japanese Title:</td>
        <td><input type="text" id="title_jp" name="title_jp" size="60" value="<?=display_str($Torrent['TitleJP']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr>
        <td class="label">Artist:</td>
        <td id="idolfields">
<?      if (!empty($Torrent['Artists'])) {
          foreach ($Torrent['Artists'] as $Num => $Artist) { ?>
            <input type="text" id="idols_<?=$Num?>" name="idols[]" size="45" value="<?=display_str($Artist['name'])?>" <?=$this->Disabled?> />
            <? if ($Num == 0) { ?>
              <a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
            <? }
            }
          } else { ?>
            <input type="text" id="idols_0" name="idols[]" size="45" value="" <?=$this->Disabled?> />
            <a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
<?        } ?>
        </td>
      </tr>
      <tr>
        <td class="label">Circle (Optional):</td>
        <td><input type="text" id="series" name="series" size="60" value="<?=display_str($Torrent['Series']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr>
        <td class="label">Publisher (Optional):</td>
        <td><input type="text" id="studio" name="studio" size="60" value="<?=display_str($Torrent['Studio']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr id="year_tr">
        <td class="label">Year:</td>
        <td><input type="text" id="year" name="year" maxlength="4" size="5" value="<?=display_str($Torrent['Year']) ?>" <?=$this->Disabled?> /></td>
      </tr>
      <tr id="pages_tr">
        <td class="label">Pages:</td>
        <td><input type="text" id="pages" name="pages" maxlength="5" size="5" value="<?=display_str($Torrent['Pages']) ?>" <?=$this->Disabled?> /></td>
      </tr>
<?    } ?>
      <tr>
        <td class="label">Media:</td>
        <td>
          <select name="media">
            <option>---</option>
<?
    foreach($this->MediaManga as $Media) {
      echo "\t\t\t\t\t\t<option value=\"$Media\"";
      if ($Media == $Torrent['Media']) {
        echo " selected";
      }
      echo ">$Media</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class='label'>Archive:</td>
        <td>
          <select name='archive'>
            <option>---</option>
<?
    foreach(array_merge($this->Archives, $this->ArchivesManga) as $Archive) {
      echo "\t\t\t\t\t\t<option value=\"$Archive\"";
      if ($Archive == $Torrent['Archive']) {
        echo ' selected';
      }
      echo ">$Archive</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Language:</td>
        <td>
          <select name="lang" id="lang">
            <option>---</option>
<?
    foreach($this->Languages as $Language) {
      echo "\t\t\t\t\t\t<option value=\"$Language\"";
      if ($Language == $Torrent['Language']) {
        echo " selected";
      }
      echo ">$Language</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Translation Group (optional):</td>
        <td><input type="text" id="subber" name="subber" size="60" value="<?=display_str($Torrent['Subber']) ?>" /></td>
      </tr>
      <tr>
        <td class="label">Censored?:</td>
        <td>
          <input type="checkbox" name="censored" value="censored" <?=(($Torrent['Censored'] ?? 1) ? 'checked ' : '')?> />
        </td>
      </tr>
      <!--<tr>
        <td class="label">Release Group (optional):</td>
        <td><input type="text" id="release" name="release" size="60" /></td>
      </tr>-->
<?    if ($this->NewTorrent) { ?>
      <tr>
        <td class="label tooltip" title="Comma seperated list of tags">Tags:</td>
        <td>
<?
  $GenreTags = G::$Cache->get_value('genre_tags');
  if (!$GenreTags) {
    $DB->query("
      SELECT Name
      FROM tags
      WHERE TagType = 'genre'
      ORDER BY Name");
    $GenreTags = $DB->collect('Name');
    G::$Cache->cache_value('genre_tags', $GenreTags, 3600*6);
  }
?>
          <select id="genre_tags" name="genre_tags" onchange="add_tag(); return false;" <?=($this->DisabledFlag) ? ' disabled="disabled"' : ''?>>
            <option>---</option>
<? foreach (Misc::display_array($GenreTags) as $Genre) { ?>
            <option value="<?=$Genre?>"><?=$Genre?></option>
<? } ?>
          </select>
          <input type="text" id="tags" name="tags" size="60" value="<?=display_str($Torrent['TagList']) ?>"<? Users::has_autocomplete_enabled('other'); ?> />
        </td>
      </tr>
      <tr>
        <td class="label">Cover Image:</td>
        <td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image']) ?>"<?=$this->Disabled?> /></td>
      </tr>
<? if (!$this->DisabledFlag && $this->NewTorrent) { ?>
      <tr>
        <td class="label">Samples:</td>
        <td>
          <textarea rows="8" cols="60" name="screenshots" id="screenshots"><?=display_str($Torrent['Screenshots'])?></textarea>
          <p>Enter up to 10 links to samples for the torrent, one per line. The system will automatically remove malformed or invalid links, as well as any links after the 10th. Remember to consult the <a href="/rules.php?p=upload#h1.4">rules for adding screenshots</a>.</p>
      </tr>
<? } ?>
      <tr>
        <td class="label">Torrent Group Description:</td>
        <td>
<?php new TEXTAREA_PREVIEW('album_desc', 'album_desc', display_str($Torrent['GroupDescription']), 60, 8, !$this->DisabledFlag, !$this->DisabledFlag, false, array($this->Disabled)); ?>
          <p class="min_padding">Contains information such as a description of the doujin.</p>
        </td>
      </tr>
<?    } ?>
      <tr>
        <td class="label">Torrent Description (optional):</td>
        <td>
<?php new TEXTAREA_PREVIEW('release_desc', 'release_desc', display_str($Torrent['TorrentDescription']), 60, 8); ?>
          <p class="min_padding">Contains information such as formatting information.</p>
        </td>
      </tr>
    </table>
<?
    if ($_SERVER['SCRIPT_NAME'] === '/ajax.php')
      TEXTAREA_PREVIEW::JavaScript(false);
  }//function audiobook_form


  function simple_form($CategoryID) {
    $Torrent = $this->Torrent;
?>    <table cellpadding="3" cellspacing="1" border="0" class="layout slice" width="100%">
      <tr id="name">
<?    if ($this->NewTorrent) { ?>
        <td class="label">Title:</td>
        <td><input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title']) ?>" <?=$this->Disabled?> /></td>
      </tr>
      <tr>
        <td class="label">Japanese Title:</td>
        <td><input type="text" id="title_jp" name="title_jp" size="60" value="<?=display_str($Torrent['TitleJP'])?>" <?=$this->Disabled?>/></td>
      </tr>
<? } ?>
      <tr>
        <td class="label">Censored?:</td>
        <td>
          <input type="checkbox" name="censored" value="1" <?=(($Torrent['Censored'] ?? 1) ? 'checked ' : '')?>/>
        </td>
      </tr>
<? if ($this->NewTorrent) { ?>
      <tr>
        <td class="label tooltip" title="Comma seperated list of tags">Tags:</td>
        <td>
<?
  $GenreTags = G::$Cache->get_value('genre_tags');
  if (!$GenreTags) {
    $DB->query("
      SELECT Name
      FROM tags
      WHERE TagType = 'genre'
      ORDER BY Name");
    $GenreTags = $DB->collect('Name');
    G::$Cache->cache_value('genre_tags', $GenreTags, 3600*6);
  }
?>
          <select id="genre_tags" name="genre_tags" onchange="add_tag(); return false;" <?=($this->DisabledFlag) ? ' disabled="disabled"' : ''?>>
            <option>---</option>
<? foreach (Misc::display_array($GenreTags) as $Genre) { ?>
            <option value="<?=$Genre?>"><?=$Genre?></option>
<? } ?>
          </select>
          <input type="text" id="tags" name="tags" size="60" value="<?=display_str($Torrent['TagList']) ?>"<? Users::has_autocomplete_enabled('other'); ?> /></td>
      </tr>
      <tr>
        <td class="label">Cover Image:</td>
        <td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image']) ?>"<?=$this->Disabled?> /></td>
      </tr>
<? if (!$this->DisabledFlag && $this->NewTorrent) { ?>
      <tr>
        <td class="label">Screenshots:</td>
        <td>
          <textarea rows="8" cols="60" name="screenshots" id="screenshots"><?=display_str($Torrent['Screenshots'])?></textarea>
          <p>Enter up to 10 links to screenshots for the torrent, one per line. The system will automatically remove malformed or invalid links, as well as any links after the 10th. Remember to consult the <a href="/rules.php?p=upload#h1.4">rules for adding screenshots</a>.</p>
      </tr>
<? } ?>
      <tr>
        <td class="label">Description:</td>
        <td>
<?php
new TEXTAREA_PREVIEW('album_desc', 'album_desc', display_str($Torrent['GroupDescription']), 60, 8, !$this->DisabledFlag, !$this->DisabledFlag, false, array($this->Disabled));
  if ($_SERVER['SCRIPT_NAME'] === '/ajax.php')
    TEXTAREA_PREVIEW::JavaScript(false);
?>
        </td>
      </tr>
<? } ?>
    </table>
<?  }//function simple_form

  function game_form($CategoryID) {
    $Torrent = $this->Torrent;
?>    <table cellpadding="3" cellspacing="1" border="0" class="layout slice" width="100%">
<?    if ($this->NewTorrent) { ?>
      <tr id="title_tr">
        <td class="label">Title:</td>
        <td><input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title'])?>" <?=$this->Disabled?> /></td>
      </tr>
      <tr>
        <td class="label">Japanese Title:</td>
        <td><input type="text" id="title_jp" name="title_jp" size="60" value="<?=display_str($Torrent['TitleJP'])?>" <?=$this->Disabled?> /></td>
      </tr>
      <tr>
        <td class="label">Developer:</td>
        <td id="idolfields">
<?      if (!empty($Torrent['Artists'])) {
          foreach ($Torrent['Artists'] as $Num => $Artist) { ?>
            <input type="text" id="idols_<?=$Num?>" name="idols[]" size="45" value="<?=display_str($Artist['name'])?>" <?=$this->Disabled?> />
            <? if ($Num == 0) { ?>
              <a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
            <? }
            }
          } else { ?>
            <input type="text" id="idols_0" name="idols[]" size="45" value="" <?=$this->Disabled?> />
            <a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
<?        } ?>
        </td>
      </tr>
      <tr>
        <td class="label">Circle (Optional):</td>
        <td><input type="text" id="series" name="series" size="60" value="<?=display_str($Torrent['Series']) ?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr>
        <td class="label">Publisher (Optional):</td>
        <td><input type="text" id="studio" name="studio" size="60" value="<?=display_str($Torrent['Studio'])?>" <?=$this->Disabled?>/></td>
      </tr>
      <tr>
        <td class="label">Year:</td>
        <td><input type="text" id="year" name="year" maxlength="4" size="5" value="<?=display_str($Torrent['Year']) ?>" <?=$this->Disabled?> /></td>
      </tr>
      <tr>
        <td class="label">DLsite ID:</td>
        <td><input type="text" id="dlsiteid" name="dlsiteid" size="8" maxlength="8" value="<?=display_str($Torrent['DLsiteID'])?>" <?=$this->Disabled?>/></td>
      </tr>
<?  } ?>
      <tr>
        <td class="label">Platform:</td>
        <td>
          <select id="platform" name="media">
            <option>---</option>
<?
    foreach($this->Platform as $Platform) {
      echo "\t\t\t\t\t\t<option value=\"$Platform\"";
      if ($Platform == $Torrent['Media']) {
        echo " selected";
      }
      echo ">$Platform</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Container:</td>
        <td>
          <select id="container" name="container">
            <option>---</option>
<?
    foreach($this->ContainersGames as $Container) {
      echo "\t\t\t\t\t\t<option value=\"$Container\"";
      if ($Container == $Torrent['Container']) {
        echo " selected";
      }
      echo ">$Container</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class='label'>Archive:</td>
        <td>
          <select name='archive'>
            <option>---</option>
<?
    foreach($this->Archives as $Archive) {
      echo "\t\t\t\t\t\t<option value=\"$Archive\"";
      if ($Archive == $Torrent['Archive']) {
        echo ' selected';
      }
      echo ">$Archive</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Language:</td>
        <td>
          <select name="lang">
            <option>---</option>
<?
    foreach($this->Languages as $Language) {
      echo "\t\t\t\t\t\t<option value=\"$Language\"";
      if ($Language == $Torrent['Language']) {
        echo " selected";
      }
      echo ">$Language</option>\n";
    }
?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label">Translation/Release Group (optional):</td>
        <td><input type="text" id="subber" name="subber" size="60" value="<?=display_str($Torrent['Subber']) ?>" /></td>
      </tr>
      <tr>
        <td class="label">Censored?:</td>
        <td>
          <input type="checkbox" name="censored" value="censored" <?=(($Torrent['Censored'] ?? 1) ? 'checked ' : '')?> />
        </td>
      </tr>
      <!--<tr>
        <td class="label">Release Group (optional):</td>
        <td><input type="text" id="release" name="release" size="60" /></td>
      </tr>-->
<?
  if ($this->NewTorrent) {
?>
      <tr>
        <td class="label tooltip" title="Comma seperated list of tags">Tags:</td>
        <td>
<?
  $GenreTags = G::$Cache->get_value('genre_tags');
  if (!$GenreTags) {
    $DB->query("
      SELECT Name
      FROM tags
      WHERE TagType = 'genre'
      ORDER BY Name");
    $GenreTags = $DB->collect('Name');
    G::$Cache->cache_value('genre_tags', $GenreTags, 3600*6);
  }
?>
          <select id="genre_tags" name="genre_tags" onchange="add_tag(); return false;" <?=($this->DisabledFlag) ? ' disabled="disabled"' : ''?>>
            <option>---</option>
<? foreach (Misc::display_array($GenreTags) as $Genre) { ?>
            <option value="<?=$Genre?>"><?=$Genre?></option>
<? } ?>
          </select>
          <input type="text" id="tags" name="tags" size="60" value="<?=display_str($Torrent['TagList']) ?>"<? Users::has_autocomplete_enabled('other'); ?> />
          <p class="min_padding">Tags you should consider, if appropriate: <strong>visual.novel</strong>, <strong>nukige</strong></p>
        </td>
      </tr>
      <tr>
        <td class="label">Cover Image:</td>
        <td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image'])?>"<?=$this->Disabled?> /></td>
      </tr>
<? if (!$this->DisabledFlag && $this->NewTorrent) { ?>
      <tr>
        <td class="label">Screenshots:</td>
        <td>
          <textarea rows="8" cols="60" name="screenshots" id="screenshots"><?=display_str($Torrent['Screenshots'])?></textarea>
          <p>Enter up to 10 links to screenshots for the torrent, one per line. The system will automatically remove malformed or invalid links, as well as any links after the 10th. Remember to consult the <a href="/rules.php?p=upload#h1.4">rules for adding screenshots</a>.</p>
      </tr>
<? } ?>
      <tr>
        <td class="label">Torrent Group Description:</td>
        <td>
<?php
new TEXTAREA_PREVIEW('album_desc', 'album_desc', display_str($Torrent['GroupDescription']), 60, 8, !$this->DisabledFlag, !$this->DisabledFlag, false, array($this->Disabled));
?>
          <p class="min_padding">Contains information such as a description of the game, it's mechanics, etc.</p>
        </td>
      </tr>
<? } ?>
      <tr>
        <td class="label">Torrent Description (optional):</td>
        <td>
<?php new TEXTAREA_PREVIEW('release_desc', 'release_desc', display_str($Torrent['TorrentDescription']), 60, 8); ?>
          <p class="min_padding">Contains information such as install instructions, patching instructions, etc.</p>
        </td>
      </tr>
    </table>
<?
    if ($_SERVER['SCRIPT_NAME'] === '/ajax.php')
      TEXTAREA_PREVIEW::JavaScript(false);
}

}//class
?>

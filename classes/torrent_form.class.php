<?php

// This class is used in upload.php to display the upload form, and the edit
// section of torrents.php to display a shortened version of the same form

class TorrentForm
{
    public $UploadForm = '';
    public $Categories = [];
    #var $Formats = [];
    #var $Bitrates = [];
    public $Media = [];
    public $MediaManga = [];
    public $Containers = [];
    public $ContainersGames = [];
    public $ContainersProt = [];
    public $Codecs = [];
    public $Resolutions = [];
    var $AudioFormats = [];
    #var $Subbing = [];
    #var $Languages = [];
    #var $Platform = [];
    public $NewTorrent = false;
    public $Torrent = [];
    public $Error = false;
    public $TorrentID = false;
    public $Disabled = '';
    public $DisabledFlag = false;

    public function __construct($Torrent = false, $Error = false, $NewTorrent = true)
    {
        $this->NewTorrent = $NewTorrent;
        $this->Torrent = $Torrent;
        $this->Error = $Error;

        global $UploadForm, $Categories, $Media,  $MediaManga, $TorrentID, $Containers, $ContainersGames, $ContainersProt, $Codecs, $Resolutions, $Archives;
        #global $UploadForm, $Categories, $Formats, $Bitrates, $Media, $MediaManga, $TorrentID, $Containers, $ContainersGames, $Codecs, $Resolutions, $AudioFormats, $Subbing, $Languages, $Platform, $Archives, $ArchivesManga;

        $this->UploadForm = $UploadForm;
        $this->Categories = $Categories;
        #$this->Formats = $Formats;
        #$this->Bitrates = $Bitrates;
        $this->Media = $Media;
        $this->MediaManga = $MediaManga;
        $this->Containers = $Containers;
        $this->ContainersGames = $ContainersGames;
        $this->ContainersProt = $ContainersProt;
        $this->Codecs = $Codecs;
        $this->Resolutions = $Resolutions;
        $this->AudioFormats = $AudioFormats;
        #$this->Subbing = $Subbing;
        #$this->Languages = $Languages;
        $this->TorrentID = $TorrentID;
        #$this->Platform = $Platform;
        $this->Archives = $Archives;
        #$this->ArchivesManga = $ArchivesManga;

        if ($this->Torrent && $this->Torrent['GroupID']) {
            $this->Disabled = ' readonly="readonly"';
            $this->DisabledFlag = true;
        }
    }

    public function head()
    {
        G::$DB->query("
          SELECT COUNT(ID)
          FROM torrents
          WHERE UserID = ?", G::$LoggedUser['ID']);
        list($Uploads) = G::$DB->next_record(); ?>

<!-- Everything until the catalogue number field-->
<div class="thin">
  <?php if ($this->NewTorrent) { ?>
  <p style="text-align: center;">
    If you would like to use your own torrent file, add the following to it.
    Otherwise, add none of it and redownload the torrent file after uploading it.
    All of the above data will be added to it by the site.
    <strong>If you never have before, be sure to read this list of
      <a href="wiki.php?action=article&name=uploadingpitfalls">uploading pitfalls</a></strong>.
  </p>

  <p style="text-align: center;">
    <?php
      $Announces = call_user_func_array('array_merge', ANNOUNCE_URLS);
        foreach ($Announces as $Announce) {
            # Loop through tracker URLs?>
    <strong>Announce</strong>

    <?php
    # Buying into the shit coding style
    # Just trying to mirror content on a Tier 2 public tracker
    if (!strstr($Announce, 'openbittorrent')) {
        ?>
    <input type="text"
      value="<?= $Announce . '/' . G::$LoggedUser['torrent_pass'] . '/announce' ?>"
      size="74" onclick="this.select();" readonly="readonly" /> <br />
    <?php
    } else { ?>
    <input type="text" value="<?= $Announce ?>" size="74"
      onclick="this.select();" readonly="readonly" /> <br />
    <?php
    }
        } ?>

    <strong>Source</strong>
    <input type="text" value="<?= Users::get_upload_sources()[0] ?>"
      size="20" onclick="this.select();" readonly="readonly" />
  </p>

  <!-- Error display -->
  <p style="text-align: center;">
    <?php
      }
        if ($this->Error) {
            echo "\t".'<p style="color: red; text-align: center;">' . $this->Error . "</p>\n";
        } ?>
  </p>

  <!-- Torrent form hidden values -->
  <form class="create_form box pad" name="torrent" action="" enctype="multipart/form-data" method="post"
    onsubmit="$('#post').raw().disabled = 'disabled';">
    <div>
      <input type="hidden" name="submit" value="true" />
      <input type="hidden" name="auth"
        value="<?=G::$LoggedUser['AuthKey']?>" />
      <?php if (!$this->NewTorrent) { ?>
      <input type="hidden" name="action" value="takeedit" />
      <input type="hidden" name="torrentid"
        value="<?=display_str($this->TorrentID)?>" />
      <input type="hidden" name="type"
        value="<?=display_str($this->Torrent['CategoryID']-1)?>" />
      <?php
        } else {
            if ($this->Torrent && $this->Torrent['GroupID']) {
                # Find groups and requests
      ?>
      <input type="hidden" name="groupid"
        value="<?= display_str($this->Torrent['GroupID']) ?>" />
      <input type="hidden" name="type"
        value="<?= display_str($this->Torrent['CategoryID']-1) ?>" />
      <?php
            }
            if ($this->Torrent && ($this->Torrent['RequestID'] ?? false)) {
                ?>
      <input type="hidden" name="requestid"
        value="<?=display_str($this->Torrent['RequestID'])?>" />
      <?php
            }
        } ?>
    </div>

    <!-- New torrent options: file and category -->
    <?php if ($this->NewTorrent) { ?>
    <table cellpadding="3" cellspacing="1" border="0" class="layout" width="100%">

      <tr>
        <td class="label">
        Torrent File
        <strong class="important_text">*</strong>
        </td>
        <td><input id="file" type="file" name="file_input" size="50" /><br />
          Use the above announce URL and set the private flag in your BitTorrent client, e.g.,
          <code>mktorrent -p -a &lt;announce&gt; &lt;target folder&gt;</code>
        </td>
      </tr>

      <tr>
        <td class="label">
        Category
        <strong class="important_text">*</strong>
        </td>
        <td>
          <select id="categories" name="type" onchange="Categories()" <?= ($this->DisabledFlag) ? ' disabled="disabled"' : '' ?>>
            <?php
            foreach (Misc::display_array($this->Categories) as $Index => $Cat) {
                echo "\t\t\t\t\t\t<option value=\"$Index\"";
                if ($Cat == $this->Torrent['CategoryName']) {
                    echo ' selected="selected"';
                }
                echo ">$Cat</option>\n";
            }
          ?>
          </select><br />
          What alphabet the sequence uses, unless it's images or supplemental data
        </td>
      </tr>
    </table>

    <!-- Start the dynamic form -->
    <?php } # if?>
    <div id="dynamic_form">
      <?php
    }

    public function foot()
    {
        $Torrent = $this->Torrent; ?>
    </div>

    <!-- Freeleech type -->
    <table cellpadding="3" cellspacing="1" border="0" class="layout slice" width="100%">
      <?php
        if (!$this->NewTorrent) {
            if (check_perms('torrents_freeleech')) {
                ?>

      <tr id="freetorrent">
        <td class="label">Freeleech</td>
        <td>
          <select name="freeleech">
            <?php
              $FL = array("Normal", "Free", "Neutral");
                foreach ($FL as $Key => $Name) {
                    # Cycle types
            ?>
            <option value="<?= $Key ?>" <?= ($Key === $Torrent['FreeTorrent'] ? ' selected="selected"' : '') ?>><?= $Name ?>
            </option>
            <?php
                } ?>
          </select>
          because
          <select name="freeleechtype">
            <?php
              $FL = array("N/A", "Staff Pick", "Perma-FL", "Freeleechizer", "Site-Wide FL");
                foreach ($FL as $Key => $Name) {
                    # Cycle reasons
            ?>
            <option value="<?=$Key?>" <?= ($Key === $Torrent['FreeLeechType'] ? ' selected="selected"' : '') ?>><?= $Name ?>
            </option>
            <?php
                } ?>
          </select>
        </td>
      </tr>

      <?php
            }
        } ?>

      <!-- Rules notice and submit button -->
      <tr>
        <td colspan="2" style="text-align: center;">
          <p>
            Be sure that your torrent is approved by the <a href="rules.php?p=upload" target="_blank">rules</a>.
            Not doing this will result in a <strong class="important_text">warning</strong> or <strong
              class="important_text">worse</strong>.
          </p>
          <?php if ($this->NewTorrent) { ?>
          <p>
            After uploading the torrent, you will have a one hour grace period during which no one other than you can
            fill requests with this torrent.
            Make use of this time wisely, and <a href="requests.php">search the list of requests</a>.
          </p>
          <?php } ?>
          <input id="post" type="submit" <?php
            if ($this->NewTorrent) {
                echo ' value="Upload"';
            } else {
                echo ' value="Edit"';
            } ?>
          />
        </td>
      </tr>
    </table>
  </form>
</div>

<!-- Okay, finally the real form -->
<?php
    } # End

    public function upload_form()
    {
        $QueryID = G::$DB->get_query_id();
        $this->head();
        $Torrent = $this->Torrent; ?>

<!-- Catalogue number field -->
<table cellpadding="3" cellspacing="1" border="0" class="layout slice" width="100%">
  <?php if ($this->NewTorrent) { ?>

  <tr id="javdb_tr">
    <td class="label tooltip" title="">Accession Number</td>
    <td>
      <input type="text" id="catalogue" name="catalogue" size="10"
        value="<?= display_str($Torrent['CatalogueNumber']) ?>"
        <?= $this->Disabled ?>/>
      <?php if (!$this->DisabledFlag) { ?>
      <input type="button" autofill="jav" value="Autofill" style="pointer-events: none; opacity: 0.5;">
      </input><br />
      <!-- Autofill only supports RefSeq and UniProt; -->
      Enter any ID number that corresponds to the data,
      preferring RefSeq and UniProt
      <?php } ?>
    </td>
  </tr>

  <!-- Other autofill options -->
  <tr id="anidb_tr" class="hidden">
    <td class="label">AniDB Autofill (optional)</td>
    <td>
      <input type="text" id="anidb" size="10" <?= $this->Disabled ?>/>
      <?php if (!$this->DisabledFlag) { ?>
      <input type="button" autofill="anime" value="Autofill" />
      <?php } ?>
    </td>
  </tr>

  <tr id="ehentai_tr" class="hidden">
    <td class="label">e-hentai URL (optional)</td>
    <td>
      <input type="text" id="catalogue" size="50" <?= $this->Disabled ?> />
      <?php if (!$this->DisabledFlag) { ?>
      <input type="button" autofill="douj" value="Autofill" />
      <?php } ?>
    </td>
  </tr>

  <!-- Semantic Versioning -->
    <tr id="audio_tr">
    <td class="label">Version</td>
    <td>
      <input type="text" id="audioformat" name="audioformat" size="10"
        pattern="\d+\.\d+\.\d+" value="<?= display_str($Torrent['AudioFormat']) ?>"
        <?= $this->Disabled ?>/><br />
      Please see <a href="https://semver.org target="_blank">Semantic Versioning</a>; start with 0.1.0
    </td>
  </tr>

  <!-- Three title fields -->
  <tr id="title_tr">
    <td class="label">
      Torrent Title
      <strong class="important_text">*</strong>
    </td>
    <td>
      <input type="text" id="title" name="title" size="60"
        value="<?= display_str($Torrent['Title']) ?>"
        <?= $this->Disabled ?>/><br />
      Definition line, e.g., Alcohol dehydrogenase ADH1
    </td>
  </tr>

  <tr id="title_rj_tr">
    <td class="label" title="">Organism</td>
    <td>
      <input type="text" id="title_rj" name="title_rj" size="60"
        value="<?= display_str($Torrent['TitleRJ']) ?>"
        <?= $this->Disabled ?>/><br />
      Organism line binomial, e.g., <em>Saccharomyces cerevisiae</em>
    </td>
  </tr>

  <tr id="title_jp_tr">
    <td class="label">Strain/Variety</td>
    <td>
      <input type="text" id="title_jp" name="title_jp" size="60"
        value="<?= display_str($Torrent['TitleJP']) ?>"
        <?= $this->Disabled ?>/><br />
      Organism line if any, e.g., S288C
    </td>
  </tr>

  <!-- Multiple artists -->
  <tr id="idols_tr">
    <td class="label">
      Authors(s)
      <strong class="important_text">*</strong>
    </td>
    <td id="idolfields">
      One per field, e.g., Robert K. Mortimer [+] David Schild<br />
      <?php
        if (!empty($Torrent['Artists'])) {
            foreach ($Torrent['Artists'] as $Num => $Artist) {
                ?>
      <input type="text" id="idols_<?= $Num ?>" name="idols[]"
        size="45"
        value="<?= display_str($Artist['name']) ?>"
        <?= $this->Disabled ?>/>
      <?php if ($Num === 0) { ?>
      <a class="add_artist_button brackets">+</a>
      <a class="remove_artist_button brackets">&minus;</a>
      <?php
            }
            }
        } else {
            ?>
      <input type="text" id="idols_0" name="idols[]" size="45" value="" <?= $this->Disabled ?> />
      <a class="add_artist_button brackets">+</a>
      <a class="remove_artist_button brackets">&minus;</a>
      <?php
        } ?>
    </td>
  </tr>

  <!-- Production studio -->
  <tr id="studio_tr">
    <td class="label">
      Department/Lab
      <strong class="important_text">*</strong>
    </td>
    <td>
      <input type="text" id="studio" name="studio" size="60"
        value="<?= display_str($Torrent['Studio']) ?>"
        <?= $this->Disabled ?>/><br />
      Last author's institution, e.g., Lawrence Berkeley Laboratory
    </td>
  </tr>

  <!-- Location -->
  <tr id="series_tr">
    <td class="label">Location</td>
    <td>
      <input type="text" id="series" name="series" size="60"
        value="<?= display_str($Torrent['Series']) ?>"
        <?= $this->Disabled ?>/><br />
      Physical location, e.g., Berkeley, CA 94720
    </td>
  </tr>

  <!-- Year -->
  <tr id="year_tr">
    <td class="label">
      Year
      <strong class="important_text">*</strong>
    </td>
    <td>
      <input type="text" id="year" name="year" maxlength="4" size="5"
        value="<?= display_str($Torrent['Year']) ?>"
        <?= $this->Disabled ?>/><br />
      Original publication year
    </td>
  </tr>
  <?php } # Ends if NewTorrent line 256?>

  <!-- Encoding -->
  <tr id="codec_tr">
    <td class="label">
      License
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select name="codec">
        <option>---</option>
        <?php
          foreach ($this->Codecs as $Codec) {
              echo "\t\t\t\t\t\t<option value=\"$Codec\"";
              if ($Codec === ($Torrent['Codec'] ?? false)) {
                  echo " selected";
              }
              echo ">$Codec</option>\n";
          } ?>
      </select><br />
      Please see <a href="http://www.dcc.ac.uk/resources/how-guides/license-research-data" target="_blank">How to
        License Research Data</a>
    </td>
  </tr>

  <!-- Media type -->
  <tr id="media_tr">
    <td class="label">
      Platform
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select name="media">
      <option value="">---</option>
        <?php
          foreach ($this->Media as $Media) {
              echo "\t\t\t\t\t\t<option value=\"$Media\"";
              if ($Media == ($Torrent['Media'] ?? false)) {
                  echo " selected";
              }
              echo ">$Media</option>\n";
          } ?>
      </select><br />
      The class of technology used
    </td>
  </tr>

  <!-- Alternate media -->
  <tr id="media_manga_tr">
    <td class="label">
      Platform
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select name="media">
        <option>---</option>
        <?php
            foreach ($this->MediaManga as $Media) {
                echo "\t\t\t\t\t\t<option value=\"$Media\"";
                if ($Media === ($Torrent['Media'] ?? false)) {
                    echo " selected";
                }
                echo ">$Media</option>\n";
            } ?>
      </select><br />
      The class of technology used
    </td>
  </tr>

  <!-- Resolution -->
  <tr id="resolution_tr">
    <td class="label">
      Assembly Level
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select id="ressel" name="ressel" onchange="SetResolution()">
        <option value="">---</option>
        <?php
          foreach ($this->Resolutions as $Res) {
              echo "\t\t\t\t\t\t<option value=\"$Res\"";
              if ($Res === ($Torrent['Resolution'] ?? false)
              || (!isset($FoundRes) && ($Torrent['Resolution'] ?? false)
              && $Res === 'Other')) {
                  echo " selected";
                  $FoundRes = true;
              }
              echo ">$Res</option>\n";
          } ?>
      </select>

      <input type="text" id="resolution" name="resolution" size="10" class="hidden"
        value="<?= ($Torrent['Resolution']??'') ?>"
        readonly>
      </input>
      <script>
        if ($('#ressel').raw().value == "Other") {
          $('#resolution').raw().readOnly = false
          $('#resolution').gshow()
        }
      </script><br />
      How complete the data is, specifically or conceptually
    </td>
  </tr>

  <!-- Three container fields -->
  <tr id="container_tr">
    <td class="label">
      Format
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select name="container">
      <option value="">---</option>
        <?php
          foreach ($this->Containers as $Name => $Container) {
              echo "<option value='$Name'>$Name</option>\n";
          } ?>
      </select><br />
      Data file format, or detect from file list
    </td>
  </tr>

  <!-- 2 -->
  <tr id="container_games_tr">
    <td class="label">
      Format
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select id="container" name="container">
      <option value="">---</option>
        <?php
          foreach ($this->ContainersGames as $Name => $Container) {
              echo "<option value='$Name'>$Name</option>\n";
          } ?>
      </select><br />
      Data file format, or detect from file list
    </td>
  </tr>

  <!-- 3 -->
  <tr id="container_prot_tr">
    <td class="label">
      Format
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select id="container" name="container">
      <option value="">---</option>
        <?php
          foreach ($this->ContainersProt as $Name => $Container) {
              echo "<option value='$Name'>$Name</option>\n";
          } ?>
      </select><br />
      Data file format, or detect from file list
    </td>
  </tr>

  <!-- Compression -->
  <tr id="archive_tr">
    <td class="label">
      Archive
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select name='archive'>
      <option value="">---</option>
        <?php
          foreach ($this->Archives as $Name => $Archive) {
              echo "\t\t\t\t\t\t<option value=\"$Name\"";
              if ($Archive === ($Torrent['Archive'] ?? false)) {
                  echo ' selected';
              }
              echo ">$Name</option>\n";
          } ?>
      </select><br />
      Compression algorithm, or detect from file list
    </td>
  </tr>

  <!-- Tags -->
  <?php if ($this->NewTorrent) { ?>
  <tr id="tags_tr">
    <td class="label">
      Tags
      <strong class="important_text">*</strong>
    </td>
    <td>
      <?php
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
      <select id="genre_tags" name="genre_tags" onchange="add_tag(); return false;" <?= ($this->DisabledFlag) ? ' disabled="disabled"' : '' ?>>
        <option>---</option>
        <?php foreach (Misc::display_array($GenreTags) as $Genre) { ?>
        <option value="<?= $Genre ?>"><?= $Genre ?>
        </option>
        <?php } ?>
      </select>
      <input type="text" id="tags" name="tags" size="60"
        value="<?= display_str(implode(', ', explode(',', $Torrent['TagList']))) ?>"
        <?php Users::has_autocomplete_enabled('other'); ?>
      /><br />
      Comma-seperated list of at least 5 tags
    </td>
  </tr>

  <!-- Picture -->
  <tr id="cover_tr">
    <td class="label">Picture</td>
    <td>
      <input type="text" id="image" name="image" size="60"
        value="<?= display_str($Torrent['Image']) ?>"
        <?= $this->Disabled ?> /><br />
      A meaningful picture, e.g., the specimen or a thumbnail
    </td>
  </tr>

  <!-- Sample pictures/links -->
  <?php if (!$this->DisabledFlag && $this->NewTorrent) { ?>
  <tr id="screenshots_tr">
    <td class="label">Publications</td>
    <td>
      <textarea rows="8" cols="60" name="screenshots"
        id="screenshots"><?= display_str($Torrent['Screenshots'])?></textarea>
      Up to ten DOI numbers, one per line
  </tr>
  <?php } ?>

  <!-- Album description -->
  <tr id="group_desc_tr">
    <td class="label">
      Torrent Group Description
      <strong class="important_text">*</strong>
    </td>
    <td>
      <?php
        new TEXTAREA_PREVIEW(
          'album_desc',
          'album_desc',
          display_str($Torrent['GroupDescription']),
          60,
          8,
          !$this->DisabledFlag,
          !$this->DisabledFlag,
          false,
          array($this->Disabled)
      );
      ?><br />
      General info about the torrent subject's function or significance
    </td>
  </tr>
  <?php } # Ends if NewTorrent line 499?>

  <!-- Torrent description -->
  <tr id="release_desc_tr">
    <td class="label">Torrent Description</td>
    <td>
      <?php
        new TEXTAREA_PREVIEW(
          'release_desc',
          'release_desc',
          display_str($Torrent['TorrentDescription'] ?? ''),
          60,
      ); ?><br />
      Specific info about the protocols and equipment used to produce the data
    </td>
  </tr>

  <!-- Boolean options -->
  <tr id="censored_tr">
    <td class="label">Aligned/Annotated</td>
    <td>
      <input type="checkbox" name="censored" value="1" <?= (($Torrent['Censored'] ?? 0) ? 'checked ' : '') ?>/>
      Whether the torrent contains alignments, annotations, or other structural metadata
    </td>
  </tr>

  <tr id="anon_tr">
    <td class="label">Upload Anonymously</td>
    <td>
      <input type="checkbox" name="anonymous" value="1" <?= (($Torrent['Anonymous'] ?? false) ? 'checked ' : '') ?>/>
      Hide your username from other users on the torrent details page
    </td>
  </tr>
</table>

<?php
  # Phew
  $this->foot();
        G::$DB->set_query_id($QueryID);
    }
}

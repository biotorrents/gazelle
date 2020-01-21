<!-- ... -->

<p style="text-align: center;">
  <?php
    $Announces = ANNOUNCE_URLS[0];
    #$Announces = call_user_func_array('array_merge', ANNOUNCE_URLS);
    foreach ($Announces as $Announce) {
      # Loop through tracker URLs
  ?>
  <strong>Announce</strong>
  <input type="text"
    value="<?= $Announce . '/' . G::$LoggedUser['torrent_pass'] . '/announce' ?>"
    size="74" onclick="this.select();" readonly="readonly" /> <br />
  <?php
    }
  ?>

  <strong>Source</strong>
  <input type="text" value="<?= Users::get_upload_sources()[0] ?>"
    size="20" onclick="this.select();" readonly="readonly" />
</p>

<!-- ... -->

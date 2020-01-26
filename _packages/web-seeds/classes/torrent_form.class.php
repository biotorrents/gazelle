<!-- Line 674 -->
<!-- FTP/HTTP mirrors -->
<?php if (!$this->DisabledFlag && $this->NewTorrent) { ?>
  <tr id="mirrors_tr">
    <td class="label">Mirrors</td>
    <td>
      <textarea rows="1" cols="60" name="mirrors"
        id="mirrors"><?= display_str($Torrent['Mirrors'])?></textarea>
      <strong class="important_text">Experimental.</strong>
      Up to two FTP/HTTP addresses that either point directly to a file, or for multi-file torrents, to the enclosing folder
  </tr>
<?php } ?>
<!-- Line 684 -->
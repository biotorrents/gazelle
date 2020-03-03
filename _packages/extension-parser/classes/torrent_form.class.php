<?php

class TorrentForm
{
    # Line 13
    public $Containers = [];
    public $ContainersGames = [];
    public $ContainersProt = [];
    public $ContainersExtra = [];
    # Line 16

    public function __construct($Torrent = false, $Error = false, $NewTorrent = true)
    {
        # Line 45
        $this->Containers = $Containers;
        $this->ContainersGames = $ContainersGames;
        $this->ContainersProt = $ContainersProt;
        $this->ContainersExtra = $ContainersExtra;
        # Line 48
?>

<!-- Line 510 -->
  <!-- Multiple container fields -->
  <tr id="container_tr">
    <td class="label">
      Format
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select id="container" name="container">
        <option value="Autofill">Autofill</option>
        <?php
            foreach ($this->Containers as $Name => $Container) {
                echo "\t\t\t\t\t\t<option value=\"$Name\"";
                if ($Name === ($Torrent['Container'] ?? false)) {
                    echo " selected";
                }
                echo ">$Name</option>\n";
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
        <option value="Autofill">Autofill</option>
        <?php
            foreach ($this->ContainersGames as $Name => $Container) {
                echo "\t\t\t\t\t\t<option value=\"$Name\"";
                if ($Name === ($Torrent['Container'] ?? false)) {
                    echo " selected";
                }
                echo ">$Name</option>\n";
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
        <option value="Autofill">Autofill</option>
        <?php
            foreach ($this->ContainersProt as $Name => $Container) {
                echo "\t\t\t\t\t\t<option value=\"$Name\"";
                if ($Name === ($Torrent['Container'] ?? false)) {
                    echo " selected";
                }
                echo ">$Name</option>\n";
            } ?>
      </select><br />
      Data file format, or detect from file list
    </td>
  </tr>

  <!-- 4 -->
  <tr id="container_extra_tr">
    <td class="label">
      Format
      <strong class="important_text">*</strong>
    </td>
    <td>
      <select id="container" name="container">
        <option value="Autofill">Autofill</option>
        <?php
            foreach ($this->ContainersExtra as $Name => $Container) {
                echo "\t\t\t\t\t\t<option value=\"$Name\"";
                if ($Name === ($Torrent['Container'] ?? false)) {
                    echo " selected";
                }
                echo ">$Name</option>\n";
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
      <select id="archive" name="archive">
        <option value="Autofill">Autofill</option>
        <?php
            foreach ($this->Archives as $Name => $Archive) {
                echo "\t\t\t\t\t\t<option value=\"$Name\"";
                if ($Name === ($Torrent['Archive'] ?? false)) {
                    echo " selected";
                }
                echo ">$Name</option>\n";
            } ?>
      </select><br />
      Compression algorithm, or detect from file list
    </td>
  </tr>
<!-- Line 618 -->

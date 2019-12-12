<?php ?>

<!-- Three container fields -->
<tr id="container_tr">
  <td class="label">
    Format
    <strong class="important_text">*</strong>
  </td>
  <td>
    <select name="container">
      <option value="Autofill">Autofill</option>
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
      <option value="Autofill">Autofill</option>
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
      <option value="Autofill">Autofill</option>
      <?php
          foreach ($this->ContainersProt as $Name => $Container) {
              echo "<option value='$Name'>$Name</option>\n";
          } ?>
    </select><br />
    Data file format, or detect from file list
  </td>
</tr>
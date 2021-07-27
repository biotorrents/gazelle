<?php
#declare(strict_types = 1);

View::show_header(
    'Create a collection',
    'vendor/easymde.min',
    'vendor/easymde.min'
);

if (!check_perms('site_collages_renamepersonal')) {
    $ChangeJS = " onchange=\"if ( this.options[this.selectedIndex].value == '0') { $('#namebox').ghide(); $('#personal').gshow(); } else { $('#namebox').gshow(); $('#personal').ghide(); }\"";
}

if (!check_perms('site_collages_renamepersonal') && $Category === '0') {
    $NoName = true;
}
?>
<div>
  <?php
if (isset($Err)) { ?>
  <div class="save_message error"><?=$Err?>
  </div>
  <br />
  <?php
} ?>
  <div class="box pad">
    <form class="create_form" name="collage" action="collages.php" method="post">
      <input type="hidden" name="action" value="new_handle" />
      <input type="hidden" name="auth"
        value="<?=$LoggedUser['AuthKey']?>" />
      <table class="layout">
        <tr id="collagename">
          <td class="label"></td>

          <td>
            <input type="text" <?=$NoName ? ' class="hidden"' : ''; ?>
            name="name" size="60" id="namebox"
            placeholder="Collection title"
            value="<?=display_str($Name)?>" />
            <span id="personal" <?=$NoName ? '' : ' class="hidden"'; ?>
              style="font-style: oblique;">
              <strong>
                <?=$LoggedUser['Username']?>'s
                personal collection
              </strong>
            </span>
          </td>
        </tr>

        <tr>
          <td class="label">
            <strong>Category</strong>
          </td>

          <td>
            <select name="category" <?=$ChangeJS?>>
              <?php
array_shift($CollageCats);
foreach ($CollageCats as $CatID => $CatName) { ?>
              <option value="<?=$CatID + 1 ?>" <?=(($CatID + 1 === $Category) ? ' selected="selected"' : '')?>><?=$CatName?>
              </option>
              <?php
}

$DB->query("
  SELECT COUNT(ID)
  FROM collages
  WHERE UserID = '$LoggedUser[ID]'
    AND CategoryID = '0'
    AND Deleted = '0'");
list($CollageCount) = $DB->next_record();
if (($CollageCount < $LoggedUser['Permissions']['MaxCollages']) && check_perms('site_collages_personal')) { ?>
              <option value="0" <?=(($Category === '0') ? ' selected="selected"' : '')?>>Personal
              </option>
              <?php
} ?>
            </select>
            <br />
            <ul>
              <li>
                <strong>Theme</strong>
                &ndash;
                A collection containing releases that all relate to a certain theme.
              </li>

              <li>
                <strong>Staff Picks</strong>
                &ndash;
                A listing of recommendations picked by the staff on special occasions.
              </li>

              <?php
  if (($CollageCount < $LoggedUser['Permissions']['MaxCollages']) && check_perms('site_collages_personal')) { ?>
              <li>
                <strong>Personal</strong>
                &ndash;
                You can put whatever you want here.
                It is your own personal collection.
              </li>
              <?php } ?>
            </ul>
          </td>
        </tr>

        <tr>
          <td class="label"></td>

          <td>
            <?php
new TEXTAREA_PREVIEW(
    $Name = 'description',
    $ID = 'description',
    $Value = display_str($Description) ?? '',
    $Placeholder = "Detailed description of the collection's purpose"
); ?>
          </td>
        </tr>
        <tr>

          <td class="label"></td>

          <td>
            <input type="text" id="tags" name="tags" size="60" placeholder="Tags (comma-separated)"
              value="<?=display_str($Tags)?>" />
          </td>
        </tr>

        <tr>
          <td colspan="2" class="center">
            <strong>
              Please ensure your collection will be allowed under the
              <a href="rules.php?p=collages">Collection Rules</a>.
            </strong>
          </td>
        </tr>

        <tr>
          <td colspan="2" class="center">
            <input type="submit" class="button-primary" value="Create" />
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<?php View::show_footer();

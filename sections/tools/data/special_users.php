<?php
#declare(strict_types=1);

if (!check_perms('admin_manage_permissions')) {
    error(403);
}

View::show_header('Special Users List');
?>
<div>
  <?php
$DB->query("
  SELECT ID
  FROM users_main
  WHERE CustomPermissions != ''
    AND CustomPermissions != 'a:0:{}'");

if ($DB->has_results()) {
    ?>
  <table width="100%">
    <tr class="colhead">
      <td>User</td>
      <td>Access</td>
    </tr>

    <?php
  while (list($UserID)=$DB->next_record()) {
      ?>
    <tr>
      <td>
        <?=Users::format_username($UserID, true, true, true, true)?>
      </td>

      <td>
        <a
          href="user.php?action=permissions&amp;userid=<?=$UserID?>">Manage</a>
      </td>
    </tr>
    <?php
  } ?>
  </table>
  <?php
} else { ?>
  <h2>There are no special users.</h2>
  <?php
} ?>
</div>
<?php
View::show_footer();

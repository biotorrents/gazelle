<?php

declare(strict_types=1);


/**
 * create or update a role
 */

$app = Gazelle\App::go();

$id ??= "create";
if ($id === "create") {
    $role = new Gazelle\Roles();
    $title = "Create a new role";
} else {
    $role = new Gazelle\Roles($id);
    $title = "Update the {$role->attributes->friendlyName} role";
}

if (!$role) {
    $app->error(404);
}

$allRoles = $role->getAllRoles();
$allPermissions = $role->getAllPermissions();

$app->twig->display("admin/rolesUpdate.twig", [
    "title" => $title,
    "pageTitle" => $title,
    "role" => $role,
    "allRoles" => $allRoles,
    "allPermissions" => $allPermissions,
]);


exit;


View::header('Manage Permissions');
?>
<script>
function confirmDelete(id) {
  if (confirm("Are you sure you want to remove this permission class?")) {
    location.href = "tools.php?action=permissions&removeid=" + id;
  }
  return false;
}
//]]>
</script>
<div>
  <div class="header">
    <div class="linkbox">
      <a href="tools.php?action=permissions&amp;id=new" class="brackets">Create a new permission set</a>
      <a href="tools.php" class="brackets">Back to tools</a>
    </div>
  </div>
<?php
$app->dbOld->prepared_query("
  SELECT
    p.ID,
    p.Name,
    p.Level,
    p.Secondary,
    COUNT(u.ID) + COUNT(DISTINCT l.UserID)
  FROM permissions AS p
    LEFT JOIN users_main AS u ON u.PermissionID = p.ID
    LEFT JOIN users_levels AS l ON l.PermissionID = p.ID
  GROUP BY p.ID
  ORDER BY p.Secondary ASC, p.Level ASC");
if ($app->dbOld->has_results()) {
    ?>
  <div class="box">
  <table class="skeletonFix">
    <tr class="colhead">
      <td>Name</td>
      <td>Level</td>
      <td>User count</td>
      <td class="center">Actions</td>
    </tr>
<?php while (list($ID, $Name, $Level, $Secondary, $UserCount) = $app->dbOld->next_record()) { ?>
    <tr>
      <td><?=\Gazelle\Text::esc($Name); ?></td>
      <td><?=($Secondary ? 'Secondary' : $Level) ?></td>
      <td><?=\Gazelle\Text::float($UserCount); ?></td>
      <td class="center">
        <a href="tools.php?action=permissions&amp;id=<?=$ID ?>" class="brackets">Edit</a>
        <a href="#" onclick="return confirmDelete(<?=$ID?>);" class="brackets">Remove</a>
      </td>
    </tr>
<?php } ?>
  </table>
  </div>
<?php
} else { ?>
  <h2>There are no permission classes.</h2>
<?php
} ?>
</div>
<?php
View::footer();
?>

<?php

declare(strict_types=1);


/**
 * miscellaneous values
 */

$app = \Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
Http::csrf();

if (!check_perms("admin_manage_permissions") && !check_perms("users_mod")) {
    error(403);
}

# query
$post = Http::request("post");
$post["databaseKey"] ??= null;

# create

# read
$query = "select * from misc";
$ref = $app->dbNew->multi($query, []);
!d($ref);
exit;

# update

# delete

# twig
$app->twig->display("admin/miscValues.twig", [
    "sidebar" => true,
]);

exit;

















if (!check_perms('admin_manage_permissions')) {
    View::header('Site Options');
    $app->dbOld->prepared_query("SELECT Name, First, Second FROM misc"); ?>

<div class="header">
  <h1>Miscellaneous Values</h1>
</div>

<table class="skeletonFix">
  <tr>
    <th>Name</th>
    <th>First</th>
    <th>Second</th>
  </tr>

  <?php
  while (list($Name, $First, $Second) = $app->dbOld->next_record()) {
      ?>
  <tr class="row">
    <td>
      <?=$Name?>
    </td>

    <td>
      <?=$First?>
    </td>

    <td>
      <?=$Second?>
    </td>
  </tr>
  <?php
  } ?>
</table>

<?php
  View::footer();
    error();
}

if (isset($_POST['submit'])) {
    authorize();

    if ($_POST['submit'] === 'Delete') {
        $Name = db_string($_POST['name']);
        $app->dbOld->prepared_query("DELETE FROM misc WHERE Name = '" . $Name . "'");
    } else {
        $Val->SetFields('name', '1', 'regex', 'The name must be separated by underscores. No spaces are allowed.', array('regex' => '/^[a-z][:_a-z0-9]{0,63}$/i'));
        $Val->SetFields('first', '1', 'string', 'You must specify the first value.');
        $Val->SetFields('second', '1', 'string', 'You must specify the second value.');

        $Error = $Val->ValidateForm($_POST);
        if ($Error) {
            error($Error);
        }

        $Name = db_string($_POST['name']);
        $First = db_string($_POST['first']);
        $Second = db_string($_POST['second']);

        if ($_POST['submit'] === 'Edit') {
            $app->dbOld->prepared_query("SELECT Name FROM misc WHERE ID = '" . db_string($_POST['id']) . "'");
            list($OldName) = $app->dbOld->next_record();

            $app->dbOld->prepared_query("
              UPDATE misc
              SET
                Name = '$Name',
                First = '$First',
                Second = '$Second'
              WHERE ID = '" . db_string($_POST['id']) . "'
            ");
        } else {
            $app->dbOld->prepared_query("
              INSERT INTO misc (Name, First, Second)
              VALUES ('$Name', '$First', '$Second')
            ");
        }
    }
}

$app->dbOld->prepared_query("
  SELECT
    ID,
    Name,
    First,
    Second
  FROM misc
  ORDER BY LOWER(Name) DESC
");

View::header('Miscellaneous Values');
?>

<div class="header">
  <h2>Miscellaneous Values</h2>
</div>

<div class="box slight_margin">
  <table>
    <tr class="colhead">
      <td>
        <span class="tooltip" title="Words must be separated by underscores">Name</span>
      </td>
      <td>First</td>
      <td>Second</td>
      <td>Submit</td>
    </tr>

    <tr>
      <form name="misc_values" action="" method="post">
        <input type="hidden" name="action" value="misc_values">
        <input type="hidden" name="auth"
          value="<?=$app->user->extra['AuthKey']?>">

        <td>
          <input type="text" size="20" name="name">
        </td>

        <td>
          <input type="text" size="50" name="first">
        </td>

        <td>
          <input type="text" size="50" name="second">
        </td>

        <td>
          <input type="submit" name="submit" class="button-primary" value="Create">
        </td>
      </form>
    </tr>

    <?php
while (list($ID, $Name, $First, $Second) = $app->dbOld->next_record()) {
    ?>
    <tr>
      <form class="manage_form" name="misc_values" action="" method="post">
        <input type="hidden" name="id" value="<?=$ID?>">
        <input type="hidden" name="action" value="misc_values">
        <input type="hidden" name="auth"
          value="<?=$app->user->extra['AuthKey']?>">

        <td>
          <input type="text" size="20" name="name"
            value="<?=$Name?>">
        </td>

        <td>
          <input type="text" size="50" name="first"
            value="<?=$First?>">
        </td>

        <td>
          <input type="text" size="50" name="second"
            value="<?=$Second?>">
        </td>

        <td>
          <input type="submit" name="submit" class="button-primary" value="Edit">
          <input type="submit" name="submit" value="Delete">
        </td>
      </form>
    </tr>
    <?php
}
?>
  </table>
</div>
<?php
View::footer();

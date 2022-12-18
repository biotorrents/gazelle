<?php
#declare(strict_types=1);

$Cost = 5000;

if (isset($_POST['title'])) {
    if (strlen($_POST['title']) > 30) {
        error("Title too long");
    }

    $Title = htmlspecialchars($_POST['title'], ENT_QUOTES);
    $UserID = $user['ID'];

    $db->prepared_query("
      SELECT BonusPoints
      FROM users_main
      WHERE ID = $UserID");

    if ($db->has_results()) {
        list($Points) = $db->next_record();

        if ($Points >= $Cost) {
            $db->prepared_query("
              UPDATE users_main
              SET BonusPoints = BonusPoints - $Cost,
                Title = ?
              WHERE ID = ?", $Title, $UserID);

            $db->prepared_query("
              UPDATE users_info
              SET AdminComment = CONCAT(NOW(), ' - Changed title to ', ?, ' via the store\n\n', AdminComment)
              WHERE UserID = ?", $Title, $UserID);

            $cache->delete_value('user_info_'.$UserID);
            $cache->delete_value('user_info_heavy_'.$UserID);
        } else {
            error("Not enough points");
        }
    }

    View::header('Store'); ?>
<div>
  <h2>Purchase Successful</h2>
  <div class="box">
    <p>You purchased the title
      "<?= $Title ?>"
    </p>
    <p>
      <a href="/store.php">Back to Store</a>
    </p>
  </div>
</div>
<?php
View::footer();
} else {
    View::header('Store'); ?>
<div>
  <div class="box text-align: center;">
    <form action="store.php" method="POST">
      <input type="hidden" name="item" value="title">
      <strong>
        Enter the title you want
      </strong>
      <br />
      <input type="text" name="title" maxlength="30" value="">
      <input type="submit">
    </form>
    <p>
      <a href="/store.php">Back to Store</a>
    </p>
  </div>
</div>
<?php
View::footer();
}

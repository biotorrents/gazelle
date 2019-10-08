<?
if (isset($_POST['title'])) {

  if (strlen($_POST['title']) > 30) error("Title too long");

  $Title = htmlspecialchars($_POST['title'], ENT_QUOTES);

  $UserID = $LoggedUser['ID'];

  $DB->query("
    SELECT BonusPoints
    FROM users_main
    WHERE ID = $UserID");
  if ($DB->has_results()) {
    list($Points) = $DB->next_record();

    if ($Points >= 50000) {

      $DB->query("
        UPDATE users_main
        SET BonusPoints = BonusPoints - 50000,
            Title = ?
        WHERE ID = ?", $Title, $UserID);
      $DB->query("
        UPDATE users_info
        SET AdminComment = CONCAT(NOW(), ' - Changed title to ', ?, ' via the store\n\n', AdminComment)
        WHERE UserID = ?", $Title, $UserID);
      $Cache->delete_value('user_info_'.$UserID);
      $Cache->delete_value('user_info_heavy_'.$UserID);

    } else {
      error("Not enough points");
    }
  }

  View::show_header('Store'); ?>
  <div class="thin">
    <h2 id="general">Purchase Successful</h2>
    <div class="box pad" style="padding: 10px 10px 10px 20px;">
      <p>You purchased the title "<? print $Title ?>"</p>
      <p><a href="/store.php">Back to Store</a></p>
    </div>
  </div>
  <? View::show_footer();

} else {

  View::show_header('Store'); ?>
  <div class="thin">
    <div class="box pad" style="padding: 10px 10px 10px 20px; text-align: center;">
      <form action="store.php" method="POST">
        <input type="hidden" name="item" value="title">
        <strong>
          Enter the title you want
        </strong>
        <br>
        <input type="text" name="title" maxlength="30" value="">
        <input type="submit">
      </form>
      <p><a href="/store.php">Back to Store</a></p>
    </div>
  </div>
  <? View::show_footer();
}
?>

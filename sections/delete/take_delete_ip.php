<?

enforce_login();
authorize();

if (!isset($_POST['ips']) || !is_array($_POST['ips'])) {
  error("Stop that.");
}

if (!apcu_exists('DBKEY')) {
  error(403);
}

$EncIPs = $_POST['ips'];

$Reason = $_POST['reason'] ?? '';

forEach ($EncIPs as $EncIP) {
  $DB->query("
    SELECT UserID
    FROM users_history_ips
    WHERE IP = '".db_string($EncIP)."'");

  if (!$DB->has_results()) {
    error('IP not found');
  }

  list($UserID) = $DB->next_record();

  if (!check_perms('users_mod') && ($UserID != $LoggedUser['ID'])) {
    error(403);
  }

  $DB->query("
    SELECT IP
    FROM users_main
    WHERE ID = '$UserID'");

  if (!$DB->has_results()) {
    error(404);
  }

  list($Curr) = $DB->next_record();
  $Curr = Crypto::decrypt($Curr);

  if ($Curr == Crypto::decrypt($EncIP)) {
    error("You can't delete your current IP.");
  }
}

//Okay I think everything checks out.

$DB->query("
  INSERT INTO deletion_requests
    (UserID, Type, Value, Reason, Time)
  VALUES
    ('$UserID', 'IP', '".db_string($EncIPs[0])."', '".db_string($Reason)."', NOW())");

$Cache->delete_value('num_deletion_requests');

View::show_header('IP Address Deletion Request');
?>

<div class="thin">
  <h2 id="general">IP Address Deletion Request</h2>
  <div class="box pad" style="padding: 10px 10px 10px 20px;">
    <p>Your request has been sent. Please wait for it to be acknowledged.</p>
    <p>After it's accepted or denied by staff, you will receive a PM response.</p>
    <p><a href="/index.php">Return</a></p>
  </div>
</div>
<? View::show_footer(); ?>

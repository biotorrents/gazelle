<?php
$DB->query("
  SELECT Password
  FROM bad_passwords
  WHERE Password = '".db_string($_POST['password'])."'");

echo ($DB->has_results() ? 'false' : 'true');
exit();
?>

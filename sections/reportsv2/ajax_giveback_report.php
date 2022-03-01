<?php
if (!check_perms('admin_reports')) {
  error('403');
}

if (!is_number($_GET['id'])) {
  error();
}

$db->prepared_query("
  SELECT Status
  FROM reportsv2
  WHERE ID = ".$_GET['id']);
list($Status) = $db->next_record();
if (isset($Status)) {
  $db->prepared_query("
    UPDATE reportsv2
    SET Status = 'New', ResolverID = 0
    WHERE ID = ".$_GET['id']);
}
?>

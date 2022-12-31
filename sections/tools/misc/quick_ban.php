<?php

#declare(strict_types=1);

if (!check_perms('admin_manage_ipbans')) {
    error(403);
}
if (isset($_GET['perform'])) {
    $IPA = substr($_GET['ip'], 0, strcspn($_GET['ip'], '.'));
    if ($_GET['perform'] == 'delete') {
        if (!is_number($_GET['id']) || $_GET['id'] == '') {
            error(0);
        }
        $db->query('DELETE FROM ip_bans WHERE ID='.$_GET['id']);
        $Bans = $cache->delete_value('ip_bans_'.$IPA);
    } elseif ($_GET['perform'] == 'create') {
        $Notes = db_string($_GET['notes']);
        $IP = Tools::ip_to_unsigned($_GET['ip']); //Sanitized by Validation regex
        $db->query("
      INSERT INTO ip_bans (FromIP, ToIP, Reason)
      VALUES ('$IP','$IP', '$Notes')");
        $cache->delete_value('ip_bans_'.$IPA);
    }
}

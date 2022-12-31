<?php

authorize();

if (!check_perms('admin_whitelist')) {
    error(403);
}

if ($_POST['submit'] == 'Delete') {
    if (!is_number($_POST['id']) || $_POST['id'] == '') {
        error('1');
    }

    $db->query('
    SELECT peer_id
    FROM xbt_client_whitelist
    WHERE id = '.$_POST['id']);
    list($PeerID) = $db->next_record();
    $db->query('
    DELETE FROM xbt_client_whitelist
    WHERE id = '.$_POST['id']);
    Tracker::update_tracker('remove_whitelist', array('peer_id' => $PeerID));
} else { //Edit & Create, Shared Validation

    if (empty($_POST['client']) || empty($_POST['peer_id'])) {
        print_r($_POST);
        error();
    }

    $Client = db_string($_POST['client']);
    $PeerID = db_string($_POST['peer_id']);

    if ($_POST['submit'] == 'Edit') { //Edit
        if (empty($_POST['id']) || !is_number($_POST['id'])) {
            error('3');
        } else {
            $db->query('
        SELECT peer_id
        FROM xbt_client_whitelist
        WHERE id = '.$_POST['id']);
            list($OldPeerID) = $db->next_record();
            $db->query("
        UPDATE xbt_client_whitelist
        SET
          vstring = '$Client',
          peer_id = '$PeerID'
        WHERE ID = ".$_POST['id']);
            Tracker::update_tracker('edit_whitelist', array('old_peer_id' => $OldPeerID, 'new_peer_id' => $PeerID));
        }
    } else { //Create
        $db->query("
      INSERT INTO xbt_client_whitelist
        (vstring, peer_id)
      VALUES
        ('$Client', '$PeerID')");
        Tracker::update_tracker('add_whitelist', array('peer_id' => $PeerID));
    }
}

$cache->delete_value('whitelisted_clients');

// Go back
header('Location: tools.php?action=whitelist');

<?php


$app = \Gazelle\App::go();



if ($_POST['submit'] == 'Delete') {
    if (!is_numeric($_POST['id']) || $_POST['id'] == '') {
        error('1');
    }

    $app->dbOld->query('
    SELECT peer_id
    FROM xbt_client_whitelist
    WHERE id = '.$_POST['id']);
    list($PeerID) = $app->dbOld->next_record();
    $app->dbOld->query('
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
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            error('3');
        } else {
            $app->dbOld->query('
        SELECT peer_id
        FROM xbt_client_whitelist
        WHERE id = '.$_POST['id']);
            list($OldPeerID) = $app->dbOld->next_record();
            $app->dbOld->query("
        UPDATE xbt_client_whitelist
        SET
          vstring = '$Client',
          peer_id = '$PeerID'
        WHERE ID = ".$_POST['id']);
            Tracker::update_tracker('edit_whitelist', array('old_peer_id' => $OldPeerID, 'new_peer_id' => $PeerID));
        }
    } else { //Create
        $app->dbOld->query("
      INSERT INTO xbt_client_whitelist
        (vstring, peer_id)
      VALUES
        ('$Client', '$PeerID')");
        Tracker::update_tracker('add_whitelist', array('peer_id' => $PeerID));
    }
}

$app->cache->delete('whitelisted_clients');

// Go back
header('Location: tools.php?action=whitelist');

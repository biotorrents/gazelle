<?php

$app = \Gazelle\App::go();

authorize();


if (empty($_POST['toid'])) {
    error(404);
}

if (!empty($app->user->extra['DisablePM']) && !isset($StaffIDs[$_POST['toid']])) {
    error(403);
}


if (isset($_POST['convid']) && is_numeric($_POST['convid'])) {
    $ConvID = $_POST['convid'];
    $Subject = '';
    $ToID = explode(',', $_POST['toid']);
    foreach ($ToID as $TID) {
        if (!is_numeric($TID)) {
            $Err = 'A recipient does not exist.';
        }
    }
    $app->dbOld->query("
    SELECT UserID
    FROM pm_conversations_users
    WHERE UserID = '{$app->user->core['id']}'
      AND ConvID = '$ConvID'");
    if (!$app->dbOld->has_results()) {
        error(403);
    }
} else {
    $ConvID = '';
    if (!is_numeric($_POST['toid'])) {
        $Err = 'This recipient does not exist.';
    } else {
        $ToID = $_POST['toid'];
    }
    $Subject = trim($_POST['subject']);
    if (empty($Subject)) {
        $Err = "You can't send a message without a subject.";
    }
}
$Body = trim($_POST['body']);
if ($Body === '' || $Body === false) {
    $Err = "You can't send a message without a body!";
}

if (!empty($Err)) {
    error($Err);
    //header('Location: inbox.php?action=compose&to='.$_POST['toid']);
    $ToID = $_POST['toid'];
    $Return = true;
    include(serverRoot.'/sections/inbox/compose.php');
    die();
}

$ConvID = Misc::send_pm($ToID, $app->user->core['id'], $Subject, $Body, $ConvID);

Http::redirect("reports.php");

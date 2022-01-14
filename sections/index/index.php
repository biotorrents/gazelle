<?php
declare(strict_types = 1);

if (isset($LoggedUser['ID'])) {
    if (!isset($_REQUEST['action'])) {
        include('private.php');
    } else {
        switch ($_REQUEST['action']) {
            case 'poll':
                include(SERVER_ROOT.'/sections/forums/poll_vote.php');
                break;

            default:
                error(400);
        }
    }
} else {
    header('Location: login.php');
}

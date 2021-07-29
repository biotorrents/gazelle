<?php
#declare(strict_types=1);

// Already done in /sections/ajax/index.php
//enforce_login();

if (!empty($LoggedUser['DisableForums'])) {
    echo json_encode(array('status' => 'failure'));
    error();
} else {
    // Replace the old hard-coded forum categories
    $ForumCats = Forums::get_forum_categories();

    // This variable contains all our lovely forum data
    $Forums = Forums::get_forums();

    if (empty($_GET['type']) || $_GET['type'] === 'main') {
        include SERVER_ROOT.'/sections/ajax/forum/main.php';
    } else {
        switch ($_GET['type']) {
        case 'viewforum':
          include SERVER_ROOT.'/sections/ajax/forum/forum.php';
          break;

        case 'viewthread':
          include SERVER_ROOT.'/sections/ajax/forum/thread.php';
          break;

        default:
          echo json_encode(array('status' => 'failure'));
          break;
        }
    }
}

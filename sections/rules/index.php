<?php

enforce_login();

if (!isset($_GET['p'])) {
    require_once SERVER_ROOT.'/sections/rules/rules.php';
} else {
    switch ($_GET['p']) {
    case 'ratio':
      require_once SERVER_ROOT.'/sections/rules/ratio.php';
      break;
      
    case 'clients':
      require_once SERVER_ROOT.'/sections/rules/clients.php';
      break;

    case 'chat':
      require_once SERVER_ROOT.'/sections/rules/chat.php';
      break;

    case 'upload':
      require_once SERVER_ROOT.'/sections/rules/upload.php';
      break;

    case 'requests':
      require_once SERVER_ROOT.'/sections/rules/requests.php';
      break;

    case 'collages':
      require_once SERVER_ROOT.'/sections/rules/collages.php';
      break;

    case 'tag':
      require_once SERVER_ROOT.'/sections/rules/tag.php';
      break;

    default:
      error(404);
  }
}

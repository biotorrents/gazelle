<?php
declare(strict_types=1);

enforce_login();

if (isset($_GET['method'])) {
    switch ($_GET['method']) {
    case 'single':
      require_once SERVER_ROOT.'/sections/better/single.php';
      break;
  
    case 'screenshots':
      require_once SERVER_ROOT.'/sections/better/screenshots.php';
      break;

    case 'covers':
      require_once SERVER_ROOT.'/sections/better/covers.php';
      break;
      
    case 'folders':
      require_once SERVER_ROOT.'/sections/better/folders.php';
      break;

    case 'tags':
      require_once SERVER_ROOT.'/sections/better/tags.php';
      break;
  
    default:
      error(404);
      break;
  }
} else {
    require_once SERVER_ROOT.'/sections/better/better.php';
}

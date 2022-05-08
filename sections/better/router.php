<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
Flight::start();


/** LEGACY ROUTES */


#enforce_login();

if (isset($_GET['method'])) {
    switch ($_GET['method']) {
    case 'single':
      require_once SERVER_ROOT.'/sections/better/single.php';
      break;
  
    case 'literature':
      require_once SERVER_ROOT.'/sections/better/literature.php';
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

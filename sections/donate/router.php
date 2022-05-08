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


$ENV = ENV::go();
if (!$ENV->FEATURE_DONATE) {
    header('Location: index.php');
    error();
}

if (!isset($_REQUEST['action'])) {
    include SERVER_ROOT.'/sections/donate/donate.php';
} else {
    switch ($_REQUEST['action']) {
    case 'complete':
      include SERVER_ROOT.'/sections/donate/complete.php';
      break;
      
    case 'cancel':
      include SERVER_ROOT.'/sections/donate/cancel.php';
      break;
  }
}

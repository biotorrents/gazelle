<?php

declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


enforce_login();

if (empty($_REQUEST['action'])) {
    $_REQUEST['action'] = '';
}

switch ($_REQUEST['action']) {
  case 'report':
    include('report.php');
    break;
  case 'takereport':
    include('takereport.php');
    break;
  case 'takeresolve':
    include('takeresolve.php');
    break;
  case 'stats':
    include(serverRoot.'/sections/reports/stats.php');
    break;
  case 'compose':
    include(serverRoot.'/sections/reports/compose.php');
    break;
  case 'takecompose':
    include(serverRoot.'/sections/reports/takecompose.php');
    break;
  case 'add_notes':
    include(serverRoot.'/sections/reports/ajax_add_notes.php');
    break;
  case 'claim':
    include(serverRoot.'/sections/reports/ajax_claim_report.php');
    break;
  case 'unclaim':
    include(serverRoot.'/sections/reports/ajax_unclaim_report.php');
    break;
  case 'resolve':
    include(serverRoot.'/sections/reports/ajax_resolve_report.php');
    break;
  default:
    include(serverRoot.'/sections/reports/reports.php');
    break;
}

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


/*
 * This is the index page, it is pretty much reponsible only for the switch statement.
 */

enforce_login();

include(serverRoot.'/sections/reportsv2/array.php');

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
    case 'report':
      include(serverRoot.'/sections/reportsv2/report.php');
      break;
    case 'takereport':
      include(serverRoot.'/sections/reportsv2/takereport.php');
      break;
    case 'takeresolve':
      include(serverRoot.'/sections/reportsv2/takeresolve.php');
      break;
    case 'take_pm':
      include(serverRoot.'/sections/reportsv2/take_pm.php');
      break;
    case 'search':
      include(serverRoot.'/sections/reportsv2/search.php');
      break;
    case 'new':
      include(serverRoot.'/sections/reportsv2/reports.php');
      break;
    case 'ajax_new_report':
      include(serverRoot.'/sections/reportsv2/ajax_new_report.php');
      break;
    case 'ajax_report':
      include(serverRoot.'/sections/reportsv2/ajax_report.php');
      break;
    case 'ajax_change_resolve':
      include(serverRoot.'/sections/reportsv2/ajax_change_resolve.php');
      break;
    case 'ajax_take_pm':
      include(serverRoot.'/sections/reportsv2/ajax_take_pm.php');
      break;
    case 'ajax_grab_report':
      include(serverRoot.'/sections/reportsv2/ajax_grab_report.php');
      break;
    case 'ajax_giveback_report':
      include(serverRoot.'/sections/reportsv2/ajax_giveback_report.php');
      break;
    case 'ajax_update_comment':
      include(serverRoot.'/sections/reportsv2/ajax_update_comment.php');
      break;
    case 'ajax_update_resolve':
      include(serverRoot.'/sections/reportsv2/ajax_update_resolve.php');
      break;
    case 'ajax_create_report':
      include(serverRoot.'/sections/reportsv2/ajax_create_report.php');
      break;
  }
} else {
    if (isset($_GET['view'])) {
        include(serverRoot.'/sections/reportsv2/static.php');
    } else {
        include(serverRoot.'/sections/reportsv2/views.php');
    }
}

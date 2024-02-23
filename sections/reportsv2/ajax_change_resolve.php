<?php
/*
 * This is the page that gets the values of whether to delete/disable upload/warning duration
 * every time you change the resolve type on one of the two reports pages.
 */

if ($app->user->cant(["admin" => "reports"])) {
    error(403);
}

if (is_numeric($_GET['id'])) {
    $ReportID = $_GET['id'];
} else {
    echo 'HAX on report ID';
    error();
}

if (!isset($_GET['categoryid'])) {
    echo 'HAX on categoryid';
    error();
} else {
    $CategoryID = $_GET['categoryid'];
}

if (!isset($_GET['type'])) {
    error(404);
} elseif (array_key_exists($_GET['type'], $Types[$CategoryID])) {
    $ReportType = $Types[$CategoryID][$_GET['type']];
} elseif (array_key_exists($_GET['type'], $Types['master'])) {
    $ReportType = $Types['master'][$_GET['type']];
} else {
    //There was a type but it wasn't an option!
    echo 'HAX on section type';
    error();
}

$Array = [];
$Array[0] = $ReportType['resolve_options']['delete'];
$Array[1] = $ReportType['resolve_options']['upload'];
$Array[2] = $ReportType['resolve_options']['warn'];

echo json_encode($Array);

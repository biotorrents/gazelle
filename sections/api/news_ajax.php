<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

// Don't allow bigger queries than specified below regardless of called function
$SizeLimit = 10;

$Count = (int)$_GET['count'];
$Offset = (int)$_GET['offset'];

if (!isset($_GET['count']) || !isset($_GET['offset']) || $Count <= 0 || $Offset < 0 || $Count > $SizeLimit) {
    json_die('failure');
}

$app->dbOld->query("
    SELECT
      ID,
      Title,
      Body,
      Time
    FROM news
    ORDER BY Time DESC
    LIMIT $Offset, $Count");
$News = $app->dbOld->to_array(false, MYSQLI_NUM, false);

$NewsResponse = [];
foreach ($News as $NewsItem) {
    list($NewsID, $Title, $Body, $NewsTime) = $NewsItem;
    array_push(
        $NewsResponse,
        array(
      $NewsID,
      \Gazelle\Text::parse($Title),
      time_diff($NewsTime),
      \Gazelle\Text::parse($Body)
    )
    );
}

json_die('success', json_encode($NewsResponse));

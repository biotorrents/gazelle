<?php
#declare(strict_types=1);

# todo: Go through line by line
if (isset($_GET['details'])) {
    if (in_array($_GET['details'], array('ul','dl','numul','uls','dls'))) {
        $Details = $_GET['details'];
    } else {
        echo json_encode(array('status' => 'failure'));
        error();
    }
} else {
    $Details = 'all';
}

// Defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, array(10,100,250)) ? $Limit : 10;

$BaseQuery = "
  SELECT
    u.ID,
    u.Username,
    ui.JoinDate,
    u.Uploaded,
    u.Downloaded,
    ABS(u.Uploaded-524288000) / (".time()." - UNIX_TIMESTAMP(ui.JoinDate)) AS UpSpeed,
    u.Downloaded / (".time()." - UNIX_TIMESTAMP(ui.JoinDate)) AS DownSpeed,
    COUNT(t.ID) AS NumUploads
  FROM users_main AS u
    JOIN users_info AS ui ON ui.UserID = u.ID
    LEFT JOIN torrents AS t ON t.UserID = u.ID
  WHERE u.Enabled = '1'
    AND Uploaded > '". 5 * 1024 * 1024 * 1024 ."'
    AND Downloaded > '". 5 * 1024 * 1024 * 1024 ."'
    AND (Paranoia IS NULL OR (Paranoia NOT LIKE '%\"uploaded\"%' AND Paranoia NOT LIKE '%\"downloaded\"%'))
  GROUP BY u.ID";

$OuterResults = [];

if ($Details == 'all' || $Details == 'ul') {
    if (!$TopUserUploads = $cache->get_value("topuser_ul_$Limit")) {
        $db->query("
      $BaseQuery
      ORDER BY u.Uploaded DESC
      LIMIT $Limit;");
        $TopUserUploads = $db->to_array();
        $cache->cache_value("topuser_ul_$Limit", $TopUserUploads, 3600 * 12);
    }
    $OuterResults[] = generate_user_json('Uploaders', 'ul', $TopUserUploads, $Limit);
}

if ($Details == 'all' || $Details == 'dl') {
    if (!$TopUserDownloads = $cache->get_value("topuser_dl_$Limit")) {
        $db->query("
      $BaseQuery
      ORDER BY u.Downloaded DESC
      LIMIT $Limit;");
        $TopUserDownloads = $db->to_array();
        $cache->cache_value("topuser_dl_$Limit", $TopUserDownloads, 3600 * 12);
    }
    $OuterResults[] = generate_user_json('Downloaders', 'dl', $TopUserDownloads, $Limit);
}

if ($Details == 'all' || $Details == 'numul') {
    if (!$TopUserNumUploads = $cache->get_value("topuser_numul_$Limit")) {
        $db->query("
      $BaseQuery
      ORDER BY NumUploads DESC
      LIMIT $Limit;");
        $TopUserNumUploads = $db->to_array();
        $cache->cache_value("topuser_numul_$Limit", $TopUserNumUploads, 3600 * 12);
    }
    $OuterResults[] = generate_user_json('Torrents Uploaded', 'numul', $TopUserNumUploads, $Limit);
}

if ($Details == 'all' || $Details == 'uls') {
    if (!$TopUserUploadSpeed = $cache->get_value("topuser_ulspeed_$Limit")) {
        $db->query("
      $BaseQuery
      ORDER BY UpSpeed DESC
      LIMIT $Limit;");
        $TopUserUploadSpeed = $db->to_array();
        $cache->cache_value("topuser_ulspeed_$Limit", $TopUserUploadSpeed, 3600 * 12);
    }
    $OuterResults[] = generate_user_json('Fastest Uploaders', 'uls', $TopUserUploadSpeed, $Limit);
}

if ($Details == 'all' || $Details == 'dls') {
    if (!$TopUserDownloadSpeed = $cache->get_value("topuser_dlspeed_$Limit")) {
        $db->query("
      $BaseQuery
      ORDER BY DownSpeed DESC
      LIMIT $Limit;");
        $TopUserDownloadSpeed = $db->to_array();
        $cache->cache_value("topuser_dlspeed_$Limit", $TopUserDownloadSpeed, 3600 * 12);
    }
    $OuterResults[] = generate_user_json('Fastest Downloaders', 'dls', $TopUserDownloadSpeed, $Limit);
}

print
  json_encode(
      array(
      'status' => 'success',
      'response' => $OuterResults
    )
  );

function generate_user_json($Caption, $Tag, $Details, $Limit)
{
    $results = [];
    foreach ($Details as $Detail) {
        $results[] = array(
      'id' => (int)$Detail['ID'],
      'username' => $Detail['Username'],
      'uploaded' => (float)$Detail['Uploaded'],
      'upSpeed' => (float)$Detail['UpSpeed'],
      'downloaded' => (float)$Detail['Downloaded'],
      'downSpeed' => (float)$Detail['DownSpeed'],
      'numUploads' => (int)$Detail['NumUploads'],
      'joinDate' => $Detail['JoinDate']
    );
    }
    return array(
    'caption' => $Caption,
    'tag' => $Tag,
    'limit' => (int)$Limit,
    'results' => $results
    );
}

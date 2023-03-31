<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

# todo: Go through line by line

// Error out on invalid requests (before caching)
if (isset($_GET['details'])) {
    if (in_array($_GET['details'], ['ut','ur'])) {
        $Details = $_GET['details'];
    } else {
        echo json_encode(['status' => 'failure']);
        error();
    }
} else {
    $Details = 'all';
}

// Defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, [10, 100, 250]) ? $Limit : 10;
$OuterResults = [];

if ($Details == 'all' || $Details == 'ut') {
    if (!$TopUsedTags = $app->cacheNew->get("topusedtag_$Limit")) {
        $app->dbOld->query("
      SELECT
        t.ID,
        t.Name,
        COUNT(tt.GroupID) AS Uses
      FROM tags AS t
        JOIN torrents_tags AS tt ON tt.TagID = t.ID
      GROUP BY tt.TagID
      ORDER BY Uses DESC
      LIMIT $Limit");
        $TopUsedTags = $app->dbOld->to_array();
        $app->cacheNew->set("topusedtag_$Limit", $TopUsedTags, 3600 * 12);
    }

    $OuterResults[] = generate_tag_json('Most Used Torrent Tags', 'ut', $TopUsedTags, $Limit);
}

if ($Details == 'all' || $Details == 'ur') {
    if (!$TopRequestTags = $app->cacheNew->get("toprequesttag_$Limit")) {
        $app->dbOld->query("
      SELECT
        t.ID,
        t.Name,
        COUNT(r.RequestID) AS Uses,
        '',''
      FROM tags AS t
        JOIN requests_tags AS r ON r.TagID = t.ID
      GROUP BY r.TagID
      ORDER BY Uses DESC
      LIMIT $Limit");
        $TopRequestTags = $app->dbOld->to_array();
        $app->cacheNew->set("toprequesttag_$Limit", $TopRequestTags, 3600 * 12);
    }

    $OuterResults[] = generate_tag_json('Most Used Request Tags', 'ur', $TopRequestTags, $Limit);
}

echo json_encode([
  'status' => 'success',
  'response' => $OuterResults
]);

function generate_tag_json($Caption, $Tag, $Details, $Limit)
{
    $results = [];
    foreach ($Details as $Detail) {
        $results[] = [
      'name' => $Detail['Name'],
      'uses' => (int)$Detail['Uses']
    ];
    }

    return [
    'caption' => $Caption,
    'tag' => $Tag,
    'limit' => (int)$Limit,
    'results' => $results
  ];
}

<?php

#declare(strict_types=1);

$app = \Gazelle\App::go();

if (empty($_GET['query'])) {
    error(400);
}

header('Content-Type: application/json; charset=utf-8');
$FullName = rawurldecode($_GET['query']);

$MaxKeySize = 4;
if (strtolower(substr($FullName, 0, 4)) === 'the ') {
    $MaxKeySize += 4;
}

$KeySize = min($MaxKeySize, max(1, strlen($FullName)));
$Letters = strtolower(substr($FullName, 0, $KeySize));
$AutoSuggest = $app->cacheOld->get('autocomplete_artist_'.$KeySize.'_'.$Letters);

if (!$AutoSuggest) {
    $Limit = (($KeySize === $MaxKeySize) ? 250 : 10);
    $app->dbOld->query("
    SELECT
      a.ArtistID,
      a.Name
    FROM artists_group AS a
      INNER JOIN torrents_artists AS ta ON ta.ArtistID=a.ArtistID
      INNER JOIN torrents AS t ON t.GroupID=ta.GroupID
    WHERE a.Name LIKE '".db_string(str_replace('\\', '\\\\', $Letters), true)."%'
    GROUP BY ta.ArtistID
    ORDER BY t.Snatched DESC
    LIMIT $Limit");
    $AutoSuggest = $app->dbOld->to_array(false, MYSQLI_NUM, false);
    $app->cacheNew->set('autocomplete_artist_'.$KeySize.'_'.$Letters, $AutoSuggest, 1800 + 7200 * ($MaxKeySize - $KeySize)); // Can't cache things for too long in case names are edited
}

$Matched = 0;
$ArtistIDs = [];
$Response = array(
  'query' => $FullName,
  'suggestions' => []
);

foreach ($AutoSuggest as $Suggestion) {
    list($ID, $Name) = $Suggestion;

    if (stripos($Name, $FullName) === 0) {
        $Response['suggestions'][] = array('value' => $Name, 'data' => $ID);

        if (++$Matched > 9) {
            break;
        }
    }
}
echo json_encode($Response);

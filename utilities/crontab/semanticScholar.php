<?php
declare(strict_types=1);


/**
 * scrape the semantic scholar api
 * find torrents with identifiers
 * attempt to find and save the data
 */

# cli bootstrap
require_once __DIR__."/../../bootstrap/cli.php";

# find torrent groups with identifiers
$query = "select id, identifier from torrents_group where identifier is not null";
$torrentGroups = $app->dbNew->row($query, []);

# update torrents
foreach ($torrentGroups as $group) {
    try {
        Text::figlet("scraping identifier {$group["identifier"]}");
        $semanticScholar = new SemanticScholar(["paperId" => $group["identifier"]]);
        $semanticScholar->scrape(true);
    } catch (Exception $e) {
        Text::figlet("failure!", "red");
        !d($e->getMesage());
    }
}

# update artists
# todo

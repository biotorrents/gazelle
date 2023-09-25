<?php

declare(strict_types=1);


/**
 * semanticScholar
 *
 * 1. find torrents with identifiers
 * 2. scrape the semantic scholar api
 * 3. attempt to find and save the data
 * 4. ???
 * 5. profit
 *
 * CREATE TABLE `semanticScholar` (
 *   `id` VARCHAR(128) NOT NULL,
 *   `groupId` INT,
 *   `artistIds` VARCHAR(255),
 *   `externalIds` VARCHAR(255),
 *   `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   `updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *   `json` LONGTEXT,
 *   KEY `id` (`id`) USING BTREE,
 *   PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB;
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

# find torrent groups with identifiers
$query = "select * from torrents_doi";
$ref = $app->dbNew->multi($query, []);

foreach ($ref as $row) {
    try {
        # update torrents
        Gazelle\Text::figlet("scraping paper", "green");
        !d($row["URI"]);

        $semanticScholar = new SemanticScholar(["paperId" => $row["URI"]]);
        $options = ["groupId" => $row["TorrentID"]];
        $semanticScholar->scrape(true, $options);
    } catch (Throwable $e) {
        Gazelle\Text::figlet("failure", "red");
        !d($e);
        continue;
    }
} # foreach
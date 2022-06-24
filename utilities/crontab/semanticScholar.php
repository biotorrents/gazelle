<?php
declare(strict_types=1);


/**
 * scrape the semantic scholar api
 * find torrents with identifiers
 * attempt to find and save the data
 *
CREATE TABLE `semanticScholar` (
    `id` VARCHAR(100) NOT NULL,
    `torrentGroupId` INT,
    `artistIds` VARCHAR(255),
    `externalIds` VARCHAR(255),
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `json` LONGTEXT,
    KEY `id` (`id`) USING BTREE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;
 *
 */

# cli bootstrap
require_once __DIR__."/../../bootstrap/cli.php";

# unlimit
App::unlimit();

# find torrent groups with identifiers
$query = "select * from torrents_doi";
$ref = $app->dbNew->multi($query, []);
#!d($torrentPapers);exit;

foreach ($ref as $row) {
    try {
        # update torrents
        Text::figlet("scraping paper", "green");
        !d($row["URI"]);

        $semanticScholar = new SemanticScholar(["paperId" => $row["URI"]]);
        $options = ["torrentGroupId" => $row["TorrentID"]];
        $semanticScholar->scrape(true, $options);

        # save memory
        $semanticScholar = null;
        unset($semanticScholar);
    } catch (PDOException $e) {
        Text::figlet("failure!", "red");
        !d($e);
        continue;
    }

    # update artists
    # todo
} # foreach

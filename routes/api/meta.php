<?php

declare(strict_types=1);


/**
 * meta
 */

# manifest
Flight::get("/api/meta/manifest", ["Gazelle\Api\Meta", "manifest"]);


# ontology
Flight::get("/api/meta/ontology", ["Gazelle\Api\Meta", "ontology"]);


# torrentStats
Flight::get("/api/meta/torrentStats", ["Gazelle\Api\Meta", "torrentStats"]);


# userStats
Flight::get("/api/meta/userStats", ["Gazelle\Api\Meta", "userStats"]);

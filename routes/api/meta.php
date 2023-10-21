<?php

declare(strict_types=1);


/**
 * meta
 */

# manifest
Flight::route("GET /api/meta/manifest", ["Gazelle\Api\Meta", "manifest"]);


# ontology
Flight::route("GET /api/meta/ontology", ["Gazelle\Api\Meta", "ontology"]);


# torrentStats
Flight::route("GET /api/meta/torrentStats", ["Gazelle\Api\Meta", "torrentStats"]);


# userStats
Flight::route("GET /api/meta/userStats", ["Gazelle\Api\Meta", "userStats"]);

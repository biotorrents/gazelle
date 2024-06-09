<?php

declare(strict_types=1);


/**
 * meta
 */

# manifest
Flight::route("/api/meta/manifest", ["Gazelle\Api\Meta", "manifest"]);


# ontology
Flight::route("/api/meta/ontology", ["Gazelle\Api\Meta", "ontology"]);


# torrentStats
Flight::route("/api/meta/torrentStats", ["Gazelle\Api\Meta", "torrentStats"]);


# userStats
Flight::route("/api/meta/userStats", ["Gazelle\Api\Meta", "userStats"]);

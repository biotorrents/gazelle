<?php

declare(strict_types=1);


/**
 * meta
 */

# manifest
Flight::route("GET /api/meta/manifest", ["Gazelle\API\Meta", "manifest"]);

# ontology
Flight::route("GET /api/meta/ontology", ["Gazelle\API\Meta", "ontology"]);

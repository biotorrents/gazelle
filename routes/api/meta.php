<?php

declare(strict_types=1);


/**
 * meta
 */

# manifest
Flight::route("GET /api/meta/manifest", ["Gazelle\Api\Meta", "manifest"]);

# ontology
Flight::route("GET /api/meta/ontology", ["Gazelle\Api\Meta", "ontology"]);

<?php

declare(strict_types=1);


/**
 * collages
 */

# browse
Flight::route("POST /api/collages/browse", ["Gazelle\API\Collages", "browse"]);

# collages
Flight::route("POST /api/collages", ["Gazelle\API\Collages", "create"]);
Flight::route("GET /api/collages/@identifier", ["Gazelle\API\Collages", "read"]);
Flight::route("PATCH /api/collages/@identifier", ["Gazelle\API\Collages", "update"]);
Flight::route("DELETE /api/collages/@identifier", ["Gazelle\API\Collages", "delete"]);

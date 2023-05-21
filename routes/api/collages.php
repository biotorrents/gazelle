<?php

declare(strict_types=1);


/**
 * collages
 */

# browse
Flight::route("POST /api/collages/browse", ["Gazelle\Api\Collages", "browse"]);

# crud
Flight::route("POST /api/collages", ["Gazelle\Api\Collages", "create"]);
Flight::route("GET /api/collages/@identifier", ["Gazelle\Api\Collages", "read"]);
Flight::route("PATCH /api/collages/@identifier", ["Gazelle\Api\Collages", "update"]);
Flight::route("DELETE /api/collages/@identifier", ["Gazelle\Api\Collages", "delete"]);

<?php

declare(strict_types=1);


/**
 * groups
 */

# browse
Flight::route("POST /api/groups/browse", ["Gazelle\Api\Groups", "browse"]);


# crud
Flight::route("POST /api/groups", ["Gazelle\Api\Groups", "create"]);
Flight::route("GET /api/groups/@identifier", ["Gazelle\Api\Groups", "read"]);
Flight::route("PATCH /api/groups/@identifier", ["Gazelle\Api\Groups", "update"]);
Flight::route("DELETE /api/groups/@identifier", ["Gazelle\Api\Groups", "delete"]);

<?php

declare(strict_types=1);


/**
 * groups
 */

# browse
Flight::route("POST /api/groups/browse", ["Gazelle\API\Groups", "browse"]);

# groups
Flight::route("POST /api/groups", ["Gazelle\API\Groups", "create"]);
Flight::route("GET /api/groups/@identifier", ["Gazelle\API\Groups", "read"]);
Flight::route("PATCH /api/groups/@identifier", ["Gazelle\API\Groups", "update"]);
Flight::route("DELETE /api/groups/@identifier", ["Gazelle\API\Groups", "delete"]);

<?php

declare(strict_types=1);


/**
 * creators
 */

# browse
Flight::route("POST /api/creators/browse", ["Gazelle\Api\Creators", "browse"]);

# crud
Flight::route("POST /api/creators", ["Gazelle\Api\Creators", "create"]);
Flight::route("GET /api/creators/@identifier", ["Gazelle\Api\Creators", "read"]);
Flight::route("PATCH /api/creators/@identifier", ["Gazelle\Api\Creators", "update"]);
Flight::route("DELETE /api/creators/@identifier", ["Gazelle\Api\Creators", "delete"]);

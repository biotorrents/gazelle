<?php

declare(strict_types=1);


/**
 * creators
 */

# browse
Flight::route("POST /api/creators/browse", ["Gazelle\API\Creators", "browse"]);

# creators
Flight::route("POST /api/creators", ["Gazelle\API\Creators", "create"]);
Flight::route("GET /api/creators/@identifier", ["Gazelle\API\Creators", "read"]);
Flight::route("PATCH /api/creators/@identifier", ["Gazelle\API\Creators", "update"]);
Flight::route("DELETE /api/creators/@identifier", ["Gazelle\API\Creators", "delete"]);

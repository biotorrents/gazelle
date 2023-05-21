<?php

declare(strict_types=1);


/**
 * friends
 */

# crud
Flight::route("POST /api/friends", ["Gazelle\Api\Friends", "create"]);
Flight::route("GET /api/friends", ["Gazelle\Api\Friends", "read"]);
Flight::route("PATCH /api/friends", ["Gazelle\Api\Friends", "update"]);
Flight::route("DELETE /api/friends", ["Gazelle\Api\Friends", "delete"]);

<?php

declare(strict_types=1);


/**
 * friends
 */

Flight::route("POST /api/friends", ["Gazelle\API\Friends", "create"]);
Flight::route("GET /api/friends", ["Gazelle\API\Friends", "read"]);
Flight::route("PUT /api/friends", ["Gazelle\API\Friends", "update"]);
Flight::route("DELETE /api/friends", ["Gazelle\API\Friends", "delete"]);

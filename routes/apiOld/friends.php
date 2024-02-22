<?php

declare(strict_types=1);


/**
 * friends
 */

# crud
Flight::route("POST /api/friends", ["Gazelle\Api\Friends", "create"]);
Flight::route("GET /api/friends(/@identifier)", ["Gazelle\Api\Friends", "read"]);
Flight::route("PATCH /api/friends/@identifier", ["Gazelle\Api\Friends", "update"]);
Flight::route("DELETE /api/friends/@identifier", ["Gazelle\Api\Friends", "delete"]);

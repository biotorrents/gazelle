<?php

declare(strict_types=1);


/**
 * wiki
 */

# crud
Flight::route("POST /api/wiki", ["Gazelle\Api\Wiki", "create"]);
Flight::route("GET /api/wiki/@identifier", ["Gazelle\Api\Wiki", "read"]);
Flight::route("PATCH /api/wiki/@identifier", ["Gazelle\Api\Wiki", "update"]);
Flight::route("DELETE /api/wiki/@identifier", ["Gazelle\Api\Wiki", "delete"]);

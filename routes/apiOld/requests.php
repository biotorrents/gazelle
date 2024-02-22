<?php

declare(strict_types=1);


/**
 * requests
 */

# browse
Flight::route("POST /api/requests/browse", ["Gazelle\Api\Requests", "browse"]);


# crud
Flight::route("POST /api/requests", ["Gazelle\Api\Requests", "create"]);
Flight::route("GET /api/requests/@identifier", ["Gazelle\Api\Requests", "read"]);
Flight::route("PATCH /api/requests/@identifier", ["Gazelle\Api\Requests", "update"]);
Flight::route("DELETE /api/requests/@identifier", ["Gazelle\Api\Requests", "delete"]);

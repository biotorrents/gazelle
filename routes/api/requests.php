<?php

declare(strict_types=1);


/**
 * requests
 */

# browse
Flight::route("POST /api/requests/browse", ["Gazelle\API\Requests", "browse"]);

# requests
Flight::route("POST /api/requests", ["Gazelle\API\Requests", "create"]);
Flight::route("GET /api/requests/@identifier", ["Gazelle\API\Requests", "read"]);
Flight::route("PATCH /api/requests/@identifier", ["Gazelle\API\Requests", "update"]);
Flight::route("DELETE /api/requests/@identifier", ["Gazelle\API\Requests", "delete"]);

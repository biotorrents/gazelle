<?php

declare(strict_types=1);


/**
 * torrents
 */

# crud
Flight::route("POST /api/torrents", ["Gazelle\Api\Torrents", "create"]);
Flight::route("GET /api/torrents/@identifier", ["Gazelle\Api\Torrents", "read"]);
Flight::route("PATCH /api/torrents/@identifier", ["Gazelle\Api\Torrents", "update"]);
Flight::route("DELETE /api/torrents/@identifier", ["Gazelle\Api\Torrents", "delete"]);

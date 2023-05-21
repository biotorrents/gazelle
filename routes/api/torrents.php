<?php

declare(strict_types=1);


/**
 * torrents
 */

# torrents
Flight::route("POST /api/torrents", ["Gazelle\API\Torrents", "create"]);
Flight::route("GET /api/torrents/@identifier", ["Gazelle\API\Torrents", "read"]);
Flight::route("PATCH /api/torrents/@identifier", ["Gazelle\API\Torrents", "update"]);
Flight::route("DELETE /api/torrents/@identifier", ["Gazelle\API\Torrents", "delete"]);

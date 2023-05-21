<?php

declare(strict_types=1);


/**
 * torrents
 */

# browse
Flight::route("POST /api/torrents/browse", ["Gazelle\API\Torrent", "browse"]);

# torrents
Flight::route("POST /api/torrents", ["Gazelle\API\Torrent", "create"]);
Flight::route("GET /api/torrents/@identifier", ["Gazelle\API\Torrent", "read"]);
Flight::route("PATCH /api/torrents/@identifier", ["Gazelle\API\Torrent", "update"]);
Flight::route("DELETE /api/torrents/@identifier", ["Gazelle\API\Torrent", "delete"]);

<?php

declare(strict_types=1);


/**
 * top10
 */

# torrents
Flight::route("GET /api/top10/torrents(/@limit)", ["Gazelle\Api\Top10", "torrents"]);

# tags
Flight::route("GET /api/top10/tags(/@limit)", ["Gazelle\Api\Top10", "tags"]);

# users
Flight::route("GET /api/top10/users(/@limit)", ["Gazelle\Api\Top10", "users"]);

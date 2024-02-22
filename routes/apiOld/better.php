<?php

declare(strict_types=1);


/**
 * better
 */

# badFolders
Flight::route("GET /api/better/badFolders(/@snatchedOnly)", ["Gazelle\Api\Better", "badFolders"]);


# badTags
Flight::route("GET /api/better/badTags(/@snatchedOnly)", ["Gazelle\Api\Better", "badTags"]);


# missingCitations
Flight::route("GET /api/better/missingCitations(/@snatchedOnly)", ["Gazelle\Api\Better", "missingCitations"]);


# missingPictures
Flight::route("GET /api/better/missingPictures(/@snatchedOnly)", ["Gazelle\Api\Better", "missingPictures"]);


# singleSeeder
Flight::route("GET /api/better/singleSeeder(/@snatchedOnly)", ["Gazelle\Api\Better", "singleSeeder"]);

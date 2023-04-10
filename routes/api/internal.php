<?php

declare(strict_types=1);


/**
 * internal api routes
 * yeah, we getting crudded
 */

/*
Flight::route("/api/internal/foo", function () {
    #require_once "bar";
});
*/

# 2fa (totp)
Flight::route("POST /api/internal/createTwoFactor", ["Gazelle\API\Internal", "createTwoFactor"]);
Flight::route("POST /api/internal/deleteTwoFactor", ["Gazelle\API\Internal", "deleteTwoFactor"]);

# suggest a passphrase
Flight::route("POST /api/internal/createPassphrase", ["Gazelle\API\Internal", "createPassphrase"]);

# manage bookmarks
Flight::route("POST /api/internal/createBookmark", ["Gazelle\API\Internal", "createBookmark"]);
Flight::route("POST /api/internal/deleteBookmark", ["Gazelle\API\Internal", "deleteBookmark"]);

# doi number autofill
Flight::route("POST /api/internal/doiNumberAutofill", ["Gazelle\API\Internal", "doiNumberAutofill"]);

# friends
Flight::route("POST /api/internal/createFriend", ["Gazelle\API\Internal", "createFriend"]);
Flight::route("POST /api/internal/updateFriend", ["Gazelle\API\Internal", "updateFriend"]);
Flight::route("POST /api/internal/deleteFriend", ["Gazelle\API\Internal", "deleteFriend"]);

# bearer tokens
Flight::route("POST /api/internal/createBearerToken", ["Gazelle\API\Internal", "createBearerToken"]);
Flight::route("POST /api/internal/deleteBearerToken", ["Gazelle\API\Internal", "deleteBearerToken"]);

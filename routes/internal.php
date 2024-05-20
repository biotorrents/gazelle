<?php

declare(strict_types=1);


/**
 * internal api routes
 */

# 2fa (totp)
Flight::route("POST /api/internal/createTwoFactor", ["Gazelle\Api\Internal", "createTwoFactor"]);
Flight::route("POST /api/internal/deleteTwoFactor", ["Gazelle\Api\Internal", "deleteTwoFactor"]);


# webauthn (fido2)
Flight::route("GET /api/internal/webAuthn/creationRequest", ["Gazelle\Api\Internal", "webAuthnCreationRequest"]);
Flight::route("POST /api/internal/webAuthn/creationResponse", ["Gazelle\Api\Internal", "webAuthnCreationResponse"]);

Flight::route("GET /api/internal/webAuthn/assertionRequest/@username", ["Gazelle\Api\Internal", "webAuthnAssertionRequest"]);
Flight::route("POST /api/internal/webAuthn/assertionResponse", ["Gazelle\Api\Internal", "webAuthnAssertionResponse"]);

Flight::route("POST /api/internal/webAuthn/delete", ["Gazelle\Api\Internal", "deleteWebAuthn"]);


# suggest a passphrase
Flight::route("GET /api/internal/createPassphrase", ["Gazelle\Api\Internal", "createPassphrase"]);


# manage bookmarks
Flight::route("POST /api/internal/createBookmark", ["Gazelle\Api\Internal", "createBookmark"]);
Flight::route("POST /api/internal/deleteBookmark", ["Gazelle\Api\Internal", "deleteBookmark"]);


# doi number autofill
Flight::route("POST /api/internal/doiNumberAutofill", ["Gazelle\Api\Internal", "doiNumberAutofill"]);


# friends
Flight::route("POST /api/internal/createFriend", ["Gazelle\Api\Internal", "createFriend"]);
Flight::route("POST /api/internal/updateFriend", ["Gazelle\Api\Internal", "updateFriend"]);
Flight::route("POST /api/internal/deleteFriend", ["Gazelle\Api\Internal", "deleteFriend"]);


# bearer tokens
Flight::route("POST /api/internal/createBearerToken", ["Gazelle\Api\Internal", "createBearerToken"]);
Flight::route("POST /api/internal/deleteBearerToken", ["Gazelle\Api\Internal", "deleteBearerToken"]);


# torrents and groups
Flight::route("POST /api/internal/deleteGroupTags", ["Gazelle\Api\Internal", "deleteGroupTags"]);


# wiki
Flight::route("POST /api/internal/createUpdateWikiArticle", ["Gazelle\Api\Internal", "createUpdateWikiArticle"]);
Flight::route("POST /api/internal/createWikiAlias", ["Gazelle\Api\Internal", "createWikiAlias"]);
Flight::route("POST /api/internal/deleteWikiAlias", ["Gazelle\Api\Internal", "deleteWikiAlias"]);


# conversation reactions
Flight::route("POST /api/internal/reactToMessage", ["Gazelle\Api\Internal", "reactToMessage"]);


# not found
Flight::route("*", function () {
    Gazelle\Api\Base::failure(404, "not found");
});


# start the router
Flight::start();

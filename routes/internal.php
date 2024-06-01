<?php

declare(strict_types=1);


/**
 * internal api routes
 */

# 2fa (totp)
Flight::post("/api/internal/createTwoFactor", ["Gazelle\Api\Internal", "createTwoFactor"]);
Flight::post("/api/internal/deleteTwoFactor", ["Gazelle\Api\Internal", "deleteTwoFactor"]);


# webauthn (fido2)
Flight::get("/api/internal/webAuthn/creationRequest", ["Gazelle\Api\Internal", "webAuthnCreationRequest"]);
Flight::post("/api/internal/webAuthn/creationResponse", ["Gazelle\Api\Internal", "webAuthnCreationResponse"]);

Flight::get("/api/internal/webAuthn/assertionRequest/@username", ["Gazelle\Api\Internal", "webAuthnAssertionRequest"]);
Flight::post("/api/internal/webAuthn/assertionResponse", ["Gazelle\Api\Internal", "webAuthnAssertionResponse"]);

Flight::post("/api/internal/webAuthn/delete", ["Gazelle\Api\Internal", "deleteWebAuthn"]);


# suggest a passphrase
Flight::get("/api/internal/createPassphrase", ["Gazelle\Api\Internal", "createPassphrase"]);


# manage bookmarks
Flight::post("/api/internal/createBookmark", ["Gazelle\Api\Internal", "createBookmark"]);
Flight::post("/api/internal/deleteBookmark", ["Gazelle\Api\Internal", "deleteBookmark"]);


# semantic scholar integrations
Flight::post("/api/internal/doiNumberAutofill", ["Gazelle\Api\Internal", "doiNumberAutofill"]);
Flight::post("/api/internal/searchSemanticScholar", ["Gazelle\Api\Internal", "searchSemanticScholar"]);


# friends
Flight::post("/api/internal/createFriend", ["Gazelle\Api\Internal", "createFriend"]);
Flight::post("/api/internal/updateFriend", ["Gazelle\Api\Internal", "updateFriend"]);
Flight::post("/api/internal/deleteFriend", ["Gazelle\Api\Internal", "deleteFriend"]);


# bearer tokens
Flight::post("/api/internal/createBearerToken", ["Gazelle\Api\Internal", "createBearerToken"]);
Flight::post("/api/internal/deleteBearerToken", ["Gazelle\Api\Internal", "deleteBearerToken"]);


# torrents and groups
Flight::post("/api/internal/deleteGroupTags", ["Gazelle\Api\Internal", "deleteGroupTags"]);


# wiki
Flight::post("/api/internal/createUpdateWikiArticle", ["Gazelle\Api\Internal", "createUpdateWikiArticle"]);
Flight::post("/api/internal/createWikiAlias", ["Gazelle\Api\Internal", "createWikiAlias"]);
Flight::post("/api/internal/deleteWikiAlias", ["Gazelle\Api\Internal", "deleteWikiAlias"]);


# conversation reactions
Flight::post("/api/internal/reactToMessage", ["Gazelle\Api\Internal", "reactToMessage"]);


# not found
Flight::route("*", function () {
    Gazelle\Api\Base::failure(404, "not found");
});


# start the router
Flight::start();

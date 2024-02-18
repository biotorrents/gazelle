<?php

declare(strict_types=1);


/**
 * Gazelle\Api\Internal
 *
 * Some little widgetry for site Ajax calls and such.
 * Mostly used for passing silly willies every which way.
 */

namespace Gazelle\Api;

class Internal extends Base
{
    /** 2fa */


    /**
     * createTwoFactor
     *
     * Creates a new 2FA secret for the user.
     *
     * @return void
     */
    public static function createTwoFactor(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["secret"] ??= null;
        $request["code"] ??= null;

        if (empty($request["secret"]) || empty($request["code"])) {
            self::failure(400, "empty 2fa secret or code");
        }

        try {
            $app->user->create2FA($request["secret"], $request["code"]);

            self::success(200, "created 2fa [{$request["secret"]} => {$request["code"]}]");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteTwoFactor
     *
     * Deletes a 2FA secret for the user.
     *
     * @return void
     */
    public static function deleteTwoFactor(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["secret"] ??= null;
        $request["code"] ??= null;

        if (empty($request["secret"]) || empty($request["code"])) {
            self::failure(400, "empty 2fa secret or code");
        }

        try {
            $app->user->delete2FA($request["secret"], $request["code"]);

            self::success(200, "deleted 2fa [{$request["secret"]} => {$request["code"]}]");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** webauthn */


    /**
     * webAuthnCreationRequest
     *
     * Creates a WebAuthn device for the user.
     *
     * @return void
     */
    public static function webAuthnCreationRequest(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        try {
            $webAuthn = new \Gazelle\WebAuthn\Base();
            $request = $webAuthn->creationRequest();

            # return the raw request
            print $request;
            exit;
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * webAuthnCreationResponse
     *
     * Creates a WebAuthn device for the user.
     *
     * @return void
     */
    public static function webAuthnCreationResponse(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        # get the raw request
        $creationRequest = file_get_contents("php://input");

        try {
            $webAuthn = new \Gazelle\WebAuthn\Base();
            $response = $webAuthn->creationResponse($creationRequest)->jsonSerialize();

            # return the raw response
            print json_encode($response);
            exit;
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * webAuthnAssertionRequest
     *
     * Validates a WebAuthn device for the user.
     *
     * @return void
     */
    public static function webAuthnAssertionRequest(string $username): void
    {
        $app = \Gazelle\App::go();

        try {
            $userEntityRepository = new \Gazelle\WebAuthn\UserEntityRepository();
            $userEntity = $userEntityRepository->findOneByUsername($username);

            $webAuthn = new \Gazelle\WebAuthn\Base();
            $request = $webAuthn->assertionRequest($userEntity);

            # return the raw request
            print $request;
            exit;
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * webAuthnAssertionResponse
     *
     * Validates a WebAuthn device for the user.
     *
     * @return void
     */
    public static function webAuthnAssertionResponse(): void
    {
        $app = \Gazelle\App::go();

        # get the raw request
        $assertionRequest = file_get_contents("php://input");

        try {
            # webauthn
            $webAuthn = new \Gazelle\WebAuthn\Base();
            $response = $webAuthn->assertionResponse($assertionRequest)->jsonSerialize();

            # gazelle auth
            $auth = new \Auth();

            # get the userId to log in as
            $query = "
                select users.id from users
                join webauthn on webauthn.userId = users.uuid
                where webauthn.credentialId = ? and users.verified = 1 and webauthn.deleted_at is null
            ";
            $userId = $app->dbNew->single($query, [ $response["publicKeyCredentialId"] ]);

            # try to login
            $auth->library->admin()->logInAsUserById($userId);
            $auth->createSession($userId); # todo: rememberMe?

            # return the raw response
            print json_encode($response);
            exit;
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteWebAuthn
     *
     * Deletes a WebAuthn device for the user.
     *
     * @return void
     */
    public static function deleteWebAuthn(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["credentialId"] ??= null;

        if (empty($request["credentialId"])) {
            self::failure(400, "credentialId required");
        }

        try {
            $webAuthn = new \Gazelle\WebAuthn\Base();
            $webAuthn->publicKeyCredentialSourceRepository->deleteCredentialSource($request["credentialId"]);

            self::success(200, "deleted credentialId {$request["credentialId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** bearer tokens */


    /**
     * createBearerToken
     *
     * Creates a bearer token for the user.
     *
     * @return void
     */
    public static function createBearerToken(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["name"] ??= null;
        $request["permissions"] ??= [];

        try {
            $token = \Auth::createBearerToken($request["name"], $request["permissions"]);

            self::success(200, $token);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteBearerToken
     *
     * Deletes a bearer token for the user.
     *
     * @return void
     */
    public static function deleteBearerToken(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["tokenId"] ??= null;

        if (empty($request["tokenId"])) {
            self::failure(400, "tokenId required");
        }

        try {
            \Auth::deleteBearerToken(intval($request["tokenId"]));

            self::success(200, "deleted tokenId {$request["tokenId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** pwgen */


    /**
     * createPassphrase
     *
     * Suggests a passphrase for the user.
     *
     * @param string $type
     * @return void
     */
    public static function createPassphrase(string $type = "diceware"): void
    {
        $app = \Gazelle\App::go();

        #self::validateFrontendHash();

        # diceware
        if ($type === "diceware") {
            # load the dictionary
            require_once "{$app->env->serverRoot}/resources/php/wordlist.php";

            # passphrase length (words)
            $passphraseLength = 5;

            # containers
            $dice = [];
            $passphrase = "";

            # how many times to roll?
            foreach (range(1, $passphraseLength) as $i) {
                $x = "";

                foreach (range(1, 5) as $y) {
                    $x .= random_int(1, 6);
                }

                array_push($dice, intval($x));
            }

            # concatenate wordlist entries
            foreach ($dice as $die) {
                $passphrase .= "{$eff_large_wordlist[$die]} ";
            }

            # the passphrase string
            $passphrase = trim($passphrase);
        }

        # random data hash
        if ($type === "hash") {
            # vomit hashes of secure randomness
            $passphrase = password_hash(random_bytes(256), PASSWORD_DEFAULT);
        }

        # success
        if (!empty($passphrase)) {
            self::success(200, $passphrase);
        }

        # failure
        self::failure();
    }


    /** torrent search */


    /**
     * createDefaultSearch
     *
     * Adds the "make default" feature on the torrent search.
     *
     * @param int $userId
     * @param string $queryString
     * @return void
     */
    public static function createDefaultSearch(int $userId, string $queryString): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        # get existing siteOptions
        $query = "select siteOptions from users_info where userId = ?";
        $siteOptions = $app->dbNew->single($query, [$userId]);

        # validate
        $siteOptions = json_decode($siteOptions, true);
        if (!$siteOptions) {
            self::failure(400, "bad userId or siteOptions");
        }

        # set defaultSearch
        $siteOptions["defaultSearch"] ??= null;
        $siteOptions["defaultSearch"] = $queryString;

        # update
        $query = "update users_info set siteOptions = ? where userId = ?";
        $app->dbNew->do($query, [json_encode($siteOptions), $userId]);

        self::success(200, $siteOptions);
    }


    /**
     * deleteDefaultSearch
     *
     * Adds the "clear default" feature on the torrent search.
     *
     * @param int $userId
     * @return void
     */
    public static function deleteDefaultSearch(int $userId): void
    {
        self::createDefaultSearch($userId, []);
    }


    /** torrents and groups */


    /**
     * deleteGroupTags
     *
     * Deletes torrent group tags.
     *
     * @return void
     */
    public static function deleteGroupTags(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["groupId"] ??= null;
        $request["tagIds"] ??= null;

        if (!$request["groupId"] || empty($request["tagIds"])) {
            self::failure(400, "groupId and tagIds required");
        }

        $groupId = intval($request["groupId"]);
        $tagIds = array_unique($request["tagIds"]);

        try {
            \Tags::deleteGroupTags($groupId, $tagIds);

            self::success(200, "deleted tags " . implode(", ", $request["tagIds"]) . " from group {$request["groupId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }

    }


    /** bookmarks */


    /**
     * createBookmark
     *
     * Adds a bookmark to the user's bookmark list.
     *
     * @return void
     */
    public static function createBookmark(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();

        try {
            \Bookmarks::create(
                strval($request["contentType"] ?? null),
                intval($request["contentId"] ?? null)
            );

            self::success(200, "created bookmark [{$request["contentType"]} => {$request["contentId"]}]");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteBookmark
     *
     * Deletes a bookmark from the user's bookmark list.
     *
     * @return void
     */
    public static function deleteBookmark(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();

        try {
            \Bookmarks::delete(
                strval($request["contentType"] ?? null),
                intval($request["contentId"] ?? null)
            );

            self::success(200, "deleted bookmark [{$request["contentType"]} => {$request["contentId"]}]");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** autofill */


    /**
     * doiNumberAutofill
     *
     * Fills out the torrent form with Semantic Scholar data.
     *
     * @return void
     */
    public static function doiNumberAutofill(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $paperId = trim($request["paperId"] ?? null);

        if (empty($paperId)) {
            self::failure(400, "paperId required");
        }

        try {
            $semanticScholar = new \SemanticScholar([
                "paperId" => $paperId,
            ]);

            $response = $semanticScholar->paper();
            #~d($response);exit;

            # format the result for the upload form
            # todo: prolly move this to the SS class
            $data = [
                "title" => $response["title"] ?? null,
                "groupDescription" => $response["abstract"] ?? null,
                "year" => $response["year"] ?? null,
            ];

            # doi numbers: the paper itself is first
            $data["literature"] = [ $response["externalIds"]["DOI"] ?? null ];

            # sort citations by citationCount, descending
            usort($response["citations"], function ($a, $b) {
                if ($a["citationCount"] === $b["citationCount"]) {
                    return 0;
                }

                return ($a["citationCount"] > $b["citationCount"])
                    ? -1
                    : 1;
            });

            # grab the top nine influentian citations
            $citationCount = 1;
            $citationLimit = 9;

            foreach ($response["citations"] as $citation) {
                $citation["externalIds"]["DOI"] ??= null;
                if (!$citation["externalIds"]["DOI"]) {
                    continue;
                }

                if ($citationCount > $citationLimit) {
                    break;
                }

                # add the doi number to the array
                $data["literature"][] = $citation["externalIds"]["DOI"];
                $citationCount++;
            } # foreach ($response["citations"] as $citation)

            # creatorList
            foreach ($response["authors"] as $creator) {
                if (!$creator["name"] || empty($creator["name"])) {
                    continue;
                }

                # get the longest name
                $canonicalName = $creator["name"];
                $longestAlias = "";

                # check aliases
                $creator["aliases"] ??= null;
                if (!empty($creator["aliases"])) {
                    $longestAlias = max($creator["aliases"]);
                }

                # compare lengths
                if (strlen($canonicalName) < strlen($longestAlias)) {
                    $creatorName = $longestAlias;
                } else {
                    $creatorName = $canonicalName;
                }

                # add longest name only
                $data["creatorList"][] = $creator["name"];

                # get the last author's affiliation
                # (or the last affiliation in the response)
                $creator["affiliations"] ??= null;
                if (!empty($creator["affiliations"])) {
                    $data["workgroup"] = current($creator["affiliations"]);
                }
            } # foreach ($response["authors"] as $creator)

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** friends */


    /**
     * createFriend
     *
     * Adds a friend to the user's friend list.
     *
     * @return void
     */
    public static function createFriend(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();

        try {
            \Gazelle\Friends::create($request);

            self::success(200, "created friendId {$request["friendId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * updateFriend
     *
     * Updates a friend's comment.
     *
     * @return void
     */
    public static function updateFriend(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["friendId"] ??= null;
        $request["comment"] ??= null;

        if (empty($request["friendId"])) {
            self::failure(400, "friendId required");
        }

        try {
            \Gazelle\Friends::update($request["friendId"], $request["comment"]);

            self::success(200, "updated friendId {$request["friendId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteFriend
     *
     * Deletes a friend from the user's friend list.
     *
     * @return void
     */
    public static function deleteFriend(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["friendId"] ??= null;

        if (empty($request["friendId"])) {
            self::failure(400, "friendId required");
        }

        try {
            \Gazelle\Friends::delete($request["friendId"]);

            self::success(200, "deleted friendId {$request["friendId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** wiki */


    /**
     * createWikiAlias
     *
     * Creates a new wiki article alias.
     *
     * @return void
     */
    public static function createWikiAlias(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["articleId"] ??= null;
        $request["alias"] ??= null;

        try {
            $article = new \Gazelle\Wiki($request["articleId"]);
            $article->createAlias($request["alias"]);

            self::success(200, "created alias {$request["alias"]} for article {$request["articleId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteWikiAlias
     *
     * Deletes a wiki article alias.
     *
     * @return void
     */
    public static function deleteWikiAlias(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["articleId"] ??= null;
        $request["alias"] ??= null;

        try {
            $article = new \Gazelle\Wiki($request["articleId"]);
            $article->deleteAlias($request["alias"]);

            self::success(200, "deleted alias {$request["alias"]} for article {$request["articleId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * createUpdateWikiArticle
     *
     * Creates a new wiki article or updates its contents.
     *
     * @return void
     */
    public static function createUpdateWikiArticle(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Gazelle\Http::json();
        $request["articleId"] ??= null;

        try {
            # try to load the requested article
            $article = new \Gazelle\Wiki($request["articleId"]);

            if (!$article->id) {
                # change articleId to just id
                $request["id"] = $request["articleId"];
                unset($request["articleId"]);

                # no id loaded from database, create
                $article->create($request);
                self::success(200, $article);
            } else {
                # yes id loaded from database, update
                $article->update($request["articleId"], $request);
                self::success(200, $article);
            }
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class

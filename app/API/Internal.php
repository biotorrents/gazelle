<?php

declare(strict_types=1);


/**
 * Gazelle\API\Internal
 *
 * Some little widgetry for site Ajax calls and such.
 * Mostly used for passing silly willies every which way.
 */

namespace Gazelle\API;

class Internal extends Base
{
    /**
     * validateFrontendHash
     *
     * Checks a frontend key against a backend one.
     * The key is hash(sessionId . siteApiSecret).
     */
    private static function validateFrontendHash(): void
    {
        $app = \Gazelle\App::go();

        /** */

        # escape bearer token
        $server = \Http::request("server");

        # no header present
        if (empty($server["HTTP_AUTHORIZATION"])) {
            self::failure(401, "no authorization header present");
        }

        # https://tools.ietf.org/html/rfc6750
        $authorizationHeader = explode(" ", $server["HTTP_AUTHORIZATION"]);

        # too much whitespace
        if (count($authorizationHeader) !== 2) {
            self::failure(401, "token must be given as \"Authorization: Bearer {\$token}\"");
        }

        # not rfc compliant
        if ($authorizationHeader[0] !== "Bearer") {
            self::failure(401, "token must be given as \"Authorization: Bearer {\$token}\"");
        }

        # we have a token!
        $token = $authorizationHeader[1];

        # empty token
        if (empty($token)) {
            self::failure(401, "empty token provided");
        }

        /** */

        $query = "select sessionId from users_sessions where userId = ? order by expires desc";
        $ref = $app->dbNew->multi($query, [ $app->user->core["id"] ]);

        $good = false;
        foreach ($ref as $row) {
            $backendKey = implode(".", [$row["sessionId"], $app->env->getPriv("siteApiSecret")]);
            $good = password_verify($backendKey, $token);

            if ($good) {
                break;
            }
        }

        if (!$good) {
            self::failure(401, "invalid token");
        }
    }


    /** 2fa */


    /**
     * createTwoFactor
     */
    public static function createTwoFactor(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();
        $request["secret"] ??= null;
        $request["code"] ??= null;

        if (empty($request["secret"]) || empty($request["code"])) {
            self::failure(400, "empty 2fa secret or code");
        }

        try {
            $app->user->create2FA($request["secret"], $request["code"]);

            self::success("created 2fa [{$request["secret"]} => {$request["code"]}]");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteTwoFactor
     */
    public static function deleteTwoFactor(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();
        $request["secret"] ??= null;
        $request["code"] ??= null;

        if (empty($request["secret"]) || empty($request["code"])) {
            self::failure(400, "empty 2fa secret or code");
        }

        try {
            $app->user->delete2FA($request["secret"], $request["code"]);

            self::success("deleted 2fa [{$request["secret"]} => {$request["code"]}]");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** webauthn */


    /**
     * webAuthnCreationRequest
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
                join webauthn where webauthn.credentialId = ?
                and webauthn.deleted_at is null
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
     */
    public static function deleteWebAuthn(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();
        $request["credentialId"] ??= null;

        if (empty($request["credentialId"])) {
            self::failure(400, "credentialId required");
        }

        try {
            $webAuthn = new \Gazelle\WebAuthn\Base();
            $webAuthn->publicKeyCredentialSourceRepository->deleteCredentialSource($request["credentialId"]);

            self::success("deleted credentialId {$request["credentialId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** */


    /**
     * createPassphrase
     */
    public static function createPassphrase(string $type = "diceware"): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

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
            self::success($passphrase);
        }

        # failure
        self::failure();
    }


    /**
     * createBearerToken
     *
     * Creates a bearer token for the user.
     */
    public static function createBearerToken(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();
        $request["name"] ??= null;

        try {
            $token = \Auth::createBearerToken($request["name"]);

            self::success($token);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteBearerToken
     *
     * Deletes a bearer token for the user.
     */
    public static function deleteBearerToken(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();
        $request["tokenId"] ??= null;

        if (empty($request["tokenId"])) {
            self::failure(400, "tokenId required");
        }

        try {
            \Auth::deleteBearerToken(intval($request["tokenId"]));

            self::success("deleted tokenId {$request["tokenId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** */


    /**
     * createDefaultSearch
     *
     * Adds the "make default" feature on the torrent search.
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

        self::success($siteOptions);
    }


    /**
     * deleteDefaultSearch
     *
     * Adds the "clear default" feature on the torrent search.
     */
    public static function deleteDefaultSearch(int $userId): void
    {
        self::createDefaultSearch($userId, []);
    }


    /** */


    /**
     * createBookmark
     */
    public static function createBookmark(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();

        try {
            \Bookmarks::create(
                strval($request["contentType"] ?? null),
                intval($request["contentId"] ?? null)
            );

            self::success("created bookmark [{$request["contentType"]} => {$request["contentId"]}]");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteBookmark
     */
    public static function deleteBookmark(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();

        try {
            \Bookmarks::delete(
                strval($request["contentType"] ?? null),
                intval($request["contentId"] ?? null)
            );

            self::success("deleted bookmark [{$request["contentType"]} => {$request["contentId"]}]");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** */


    /**
     * doiNumberAutofill
     *
     * Fills out the torrent form with Semantic Scholar data.
     */
    public static function doiNumberAutofill(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();
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

            self::success($data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** */


    /**
     * createFriend
     *
     * Adds a friend to the user's friend list.
     */
    public static function createFriend(): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();
        $request["friendId"] ??= null;
        $request["comment"] ??= null;

        if (empty($request["friendId"])) {
            self::failure(400, "friendId required");
        }

        try {
            Friends::create($request["friendId"], $request["comment"]);

            self::success("created friendId {$request["friendId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * updateFriend
     *
     * Updates a friend's comment.
     */
    public static function updateFriend(int $friendId, string $comment = ""): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();
        $request["friendId"] ??= null;
        $request["comment"] ??= null;

        if (empty($request["friendId"])) {
            self::failure(400, "friendId required");
        }

        try {
            Friends::update($request["friendId"], $request["comment"]);

            self::success("updated friendId {$request["friendId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteFriend
     *
     * Deletes a friend from the user's friend list.
     */
    public static function deleteFriend(int $friendId): void
    {
        $app = \Gazelle\App::go();

        self::validateFrontendHash();

        $request = \Http::json();
        $request["friendId"] ??= null;

        if (empty($request["friendId"])) {
            self::failure(400, "friendId required");
        }

        try {
            Friends::delete($request["friendId"]);

            self::success("deleted friendId {$request["friendId"]}");
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class

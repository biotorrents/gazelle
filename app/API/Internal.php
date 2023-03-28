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
        $app = \App::go();

        if (headers_sent()) {
            self::failure();
        }

        $post = \Http::query("post");
        $frontendHash = $post["frontendHash"] ??= null;

        if (!$frontendHash) {
            self::failure();
        }

        $query = "select sessionId from users_sessions where userId = ? order by expires desc limit 1";
        $sessionId = $app->dbNew->single($query, [ $app->userNew->core["id"] ]);

        $backendKey = implode(".", [$sessionId, $app->env->getPriv("siteApiSecret")]);
        $good = \Auth::checkHash($backendKey, $frontendHash);

        if (!$good) {
            self::failure();
        }
    }


    /** */


    /**
     * createTwoFactor
     */
    public static function createTwoFactor(): void
    {
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");
        $post["secret"] ??= null;
        $post["code"] ??= null;

        if (empty($post["secret"]) || empty($post["code"])) {
            self::failure(400, "empty 2fa secret or code");
        }

        try {
            $app->userNew->create2FA($post["secret"], $post["code"]);

            self::success("successfully created a 2fa key");
        } catch (\Exception $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteTwoFactor
     */
    public static function deleteTwoFactor(): void
    {
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");
        $post["secret"] ??= null;
        $post["code"] ??= null;

        if (empty($post["secret"]) || empty($post["code"])) {
            self::failure(400, "empty 2fa secret or code");
        }

        try {
            $app->userNew->delete2FA($post["secret"], $post["code"]);

            self::success("successfully deleted a 2fa key");
        } catch (\Exception $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * createPassphrase
     */
    public static function createPassphrase(string $type = "diceware"): void
    {
        $app = \App::go();

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
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");
        $post["name"] ??= null;

        /*
        if (empty($post["name"])) {
            self::failure(400, "empty name");
        }
        */

        try {
            $token = \Auth::createBearerToken($post["name"]);

            self::success($token);
        } catch (\Exception $e) {
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
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");
        $post["tokenId"] ??= null;

        if (empty($post["tokenId"])) {
            self::failure(400, "tokenId required");
        }

        try {
            \Auth::deleteBearerToken(intval($post["tokenId"]));

            self::success("successfully deleted a bearer token");
        } catch (\Exception $e) {
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
        $app = \App::go();

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
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");

        try {
            \Bookmarks::create(
                strval($post["contentType"] ?? null),
                intval($post["contentId"] ?? null)
            );

            self::success("bookmark created");
        } catch (\Exception $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteBookmark
     */
    public static function deleteBookmark(): void
    {
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");

        try {
            \Bookmarks::delete(
                strval($post["contentType"] ?? null),
                intval($post["contentId"] ?? null)
            );

            self::success("bookmark deleted");
        } catch (\Exception $e) {
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
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");

        try {
            $paperId = trim($post["paperId"] ?? null);
            if (!$paperId) {
                self::failure();
            }

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
        } catch (\Exception $e) {
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
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");

        try {
            $post["friendId"] ??= null;
            $post["comment"] ??= null;

            Friends::create($post["friendId"], $post["comment"]);

            self::success("successfully created a friend");
        } catch (\Exception $e) {
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
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");

        try {
            $post["friendId"] ??= null;
            $post["comment"] ??= null;

            Friends::update($post["friendId"], $post["comment"]);

            self::success("successfully updated a friend");
        } catch (\Exception $e) {
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
        $app = \App::go();

        self::validateFrontendHash();

        $post = \Http::query("post");

        try {
            $post["friendId"] ??= null;

            Friends::delete($post["friendId"]);

            self::success("successfully deleted a friend");
        } catch (\Exception $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class

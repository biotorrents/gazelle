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
        } catch (\Exception $e) {
            self::failure(400, $e->getMessage());
        }

        self::success("successfully created a 2fa key");
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
        } catch (\Exception $e) {
            self::failure(400, $e->getMessage());
        }

        self::success("successfully deleted a 2fa key");
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
        $query = "update into users_info set siteOptions = ? where userId = ?";
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
                $app->userNew->core["id"] ?? null,
                intval($post["contentId"] ?? null),
                strval($post["contentType"] ?? null)
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
                $app->userNew->core["id"] ?? null,
                intval($post["contentId"] ?? null),
                strval($post["contentType"] ?? null)
            );

            self::success("bookmark deleted");
        } catch (\Exception $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class

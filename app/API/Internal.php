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
     * proxyToken
     *
     * Securely adds a token to an internal request.
     * At least, it's not exposed to the frontend...
     */
    private static function proxyToken()
    {
        $app = \App::go();

        if (headers_sent()) {
            return false;
        }

        $siteApiKey = $app->env->getPriv("siteApiKey");
        $good = self::checkToken(0, $siteApiKey); # hardcoded

        if (!$good) {
            self::failure();
        }

        return true;
    }


    /**
     * createTwoFactor
     */
    public static function createTwoFactor()
    {
        $app = \App::go();

        self::proxyToken();

        $post = \Http::query("post");

        $post["secret"] ??= null;
        $post["code"] ??= null;

        if (empty($post["secret"]) || empty($post["code"])) {
            $this->failure(400, "empty 2fa secret or code");
        }

        try {
            $app->userNew->create2FA($post["secret"], $post["code"]);
        } catch (\Exception $e) {
            $this->failure(400, $e->getMessage());
        }

        $this->success("successfully created a 2fa key");
    }


    /**
     * deleteTwoFactor
     */
    public static function deleteTwoFactor()
    {
        $app = \App::go();

        self::proxyToken();

        $post = \Http::query("post");

        $post["secret"] ??= null;
        $post["code"] ??= null;

        if (empty($post["secret"]) || empty($post["code"])) {
            $this->failure(400, "empty 2fa secret or code");
        }

        try {
            $app->userNew->delete2FA($post["secret"], $post["code"]);
        } catch (\Exception $e) {
            $this->failure(400, $e->getMessage());
        }

        $this->success("successfully deleted a 2fa key");
    }


    /**
     * createPassphrase
     */
    public static function createPassphrase(string $type = "diceware")
    {
        $app = \App::go();

        self::proxyToken();

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
} # class

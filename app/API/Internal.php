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
        $good = $this->checkToken(0, $siteApiKey); # hardcoded

        if (!$good) {
            $this->failure();
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
} # class

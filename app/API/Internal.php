<?php

declare(strict_types=1);


/**
 * Gazelle\API\Internal
 */

namespace Gazelle\API;

class Internal extends Base
{
    /**
     * proxyToken
     *
     * Securely adds a token to an internal request.
     */
    private static function proxyToken()
    {
        $app = \App::go();

        if (headers_sent()) {
            return false;
        }

        $json = new \Json();

        $siteApiKey = $app->env->getPriv("siteApiKey");
        $good = $json->checkToken(0, $siteApiKey); # hardcoded

        if (!$good) {
            $json->failure();
            exit;
        }

        return true;
    }


    /**
     * verifyTwoFactor
     */
    public static function verifyTwoFactor()
    {
        # self::proxyToken();

        $app = \App::go();

        $json = new \Json();
        $post = \Http::query("post");


        $post["secret"] ??= null;
        $post["code"] ??= null;

        if (empty($post["secret"]) || empty($post["code"])) {
            return $json->failure(400, "empty 2fa secret or code");
        }

        try {
            $app->userNew->create2FA($post["secret"], $post["code"]);
        } catch (\Exception $e) {
            return $json->failure(400, $e->getMessage());
        }

        return $json->success("successfully created a 2fa key");
    }


    /** */


    /**
     * create
     */
    public function create(array $options = [])
    {
        return false;
    }


    /**
     * read
     */
    public function read(array $options = [])
    {
        return false;
    }


    /**
     * update
     */
    public function update(array $options = [])
    {
        return false;
    }


    /**
     * delete
     */
    public function delete(array $options = [])
    {
        return false;
    }
} # class

<?php

declare(strict_types=1);


/**
 * Gazelle\API\Base
 *
 * Adapted from OPS's abstract class.
 *
 * @see https://github.com/OPSnet/Gazelle/blob/master/app/Json.php
 */

namespace Gazelle\API;

class Base
{
    private static $source = null;
    private static $version = 1;


    /**
     * validateBearerToken
     *
     * Validates an authorization header and API token.
     *
     * @return ?array
     */
    public static function validateBearerToken(): ?array
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
        if (!preg_match("/^Bearer\s+(.+)$/", $server["HTTP_AUTHORIZATION"], $matches)) {
            self::failure(401, "invalid authorization header format");
        }

        # we have a token!
        $token = $matches[1];

        # empty token
        if (empty($token)) {
            self::failure(401, "empty token provided");
        }

        /** */

        # check the database
        $query = "select userId, token from api_tokens use index (userId_token) where deleted_at is null";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $good = password_verify($token, $row["token"]);
            if ($good) {
                # is the user disabled?
                if (\User::isDisabled($row["userId"])) {
                    self::failure(401, "user disabled");
                }

                # return the data
                return $row;
            }
        }

        # default failure
        self::failure(401, "invalid token");
    }


    /**
     * validateFrontendHash
     *
     * Checks a frontend key against a backend one.
     * The key is hash(sessionId . siteApiSecret).
     */
    public static function validateFrontendHash(): void
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
        if (!preg_match("/^Bearer\s+(.+)$/", $server["HTTP_AUTHORIZATION"], $matches)) {
            self::failure(401, "invalid authorization header format");
        }

        # we have a token!
        $token = $matches[1];

        # empty token
        if (empty($token)) {
            self::failure(401, "empty token provided");
        }

        /** */

        $query = "select sessionId from users_sessions where userId = ? order by expires desc limit 10";
        $ref = $app->dbNew->multi($query, [ $app->user->core["id"] ]);

        foreach ($ref as $row) {
            $backendKey = implode(".", [$row["sessionId"], $app->env->getPriv("siteApiSecret")]);
            $good = password_verify($backendKey, $token);

            if ($good) {
                return;
            }
        }

        # default failure
        self::failure(401, "invalid token");
    }


    /** token permissions */


    /**
     * validatePermissions
     *
     * Checks a token's permissions against a list of required permissions.
     */
    public static function validatePermissions(int $tokenId, array $permissions = []): void
    {
        $app = \Gazelle\App::go();

        # quick sanity check
        $permissions = arrap_map("strtolower", $permissions);
        $allowedPermissions = ["create", "read", "update", "delete"];

        foreach ($permissions as $permission) {
            if (!in_array($permission, $allowedPermissions)) {
                self::failure(401, "invalid permission");
            }
        }

    }


    /** responses */


    /**
      * success
      *
      * @see https://jsonapi.org/examples/
      */
    public static function success(array|string $data): void
    {
        $app = \Gazelle\App::go();

        if (empty($data)) {
            self::failure(500, "the server provided no payload");
        }

        \Http::response(200);
        header("Content-Type: application/json; charset=utf-8");

        print json_encode(
            [
                "id" => uniqid(),
                "code" => 200,

                "data" => $data,

                "meta" => [
                    "info" => self::info(),
                    "debug" => self::debug(),
                ],
            ],
        );

        exit;
    }


    /**
     * failure
     *
     * General failure routine for when bad things happen.
     *
     * @param string $message The error set in the JSON response
     * @param $response HTTP error code (usually 4xx client errors)
     *
     * @see https://jsonapi.org/format/#error-objects
     */
    public static function failure(int $code = 400, array|string $data = "bad request"): void
    {
        $app = \Gazelle\App::go();

        if (empty($data)) {
            self::failure(500, "the server provided no payload");
        }

        \Http::response($code);
        header("Content-Type: application/json; charset=utf-8");

        print json_encode(
            [
                "id" => uniqid(),
                "code" => $code,

                "data" => $data,

                "meta" => [
                    "info" => self::info(),
                    "debug" => self::debug(),
                ],
            ],
        );

        exit;
    }


    /**
     * info
     */
    private static function info()
    {
        $app = \Gazelle\App::go();

        return [
            "source" => $app->env->siteName,
            "version" => self::$version,
        ];
    }


    /**
     * debug
     *
     * todo
     */
    private static function debug()
    {
        return [];

        /*
        $app = \Gazelle\App::go();

        if ($app->env->dev) {
            return [
                "debug" => [
                    "queries"  => $app->debug->get_queries(),
                ],
            ];
        } else {
            return [];
        }
        */
    }
} # class

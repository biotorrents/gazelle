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
     * checkToken
     *
     * Validates an authorization header and API token.
     */
    public static function checkToken(int $userId, string $token = ""): void
    {
        $app = \Gazelle\App::go();

        /** */

        # get the token off the headers
        if (empty($token)) {
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
        } # if (empty($token))

        /** */

        # check the database
        $query = "select userId, token, revoked from api_user_tokens where UserID = ?";
        $row = $app->dbNew->row($query, [$userId]);
        #~d($row);exit;

        if (!$row) {
            self::failure(401, "token not found");
        }

        # user revoked the token
        if (intval($row["revoked"]) === 1) {
            self::failure(401, "token revoked");
        }

        # user doesn't own that token
        if ($userId !== intval($row["userId"])) {
            self::failure(401, "token user mismatch");
        }

        /*
        # user is disabled
        if (\User::isDisabled($userId)) {
            self::failure(401, "user disabled");
        }
        */

        # wrong token provided
        if (!password_verify($token, $row["token"])) {
            self::failure(401, "wrong token provided");
        }
    }


    /**
      * success
      *
      * @see https://jsonapi.org/examples/
      */
    public static function success(array|string $response): void
    {
        $app = \Gazelle\App::go();

        if (empty($response)) {
            self::failure(500, "the server provided no payload");
        }

        \Http::response(200);
        header("Content-Type: application/json; charset=utf-8");
        print json_encode(
            [
                "id" => uniqid(),
                "code" => 200,

                "data" => $response,

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
    public static function failure(int $code = 400, array|string $response = "bad request"): void
    {
        \Http::response($code);
        header("Content-Type: application/json; charset=utf-8");
        print json_encode(
            [
                "id" => uniqid(),
                "code" => $code,

                "data" => $response,

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

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
    private static $mode = 0;
    private static $source = null;
    private static $version = 1;


    /**
     * checkToken
     *
     * Validates an authorization header and API token.
     */
    public static function checkToken(int $userId, string $token = "")
    {
        $app = \Gazelle\App::go();

        # get the token off the headers
        if (empty($token)) {
            # escape bearer token
            $server = \Http::query("server");

            # no header present
            if (empty($server["HTTP_AUTHORIZATION"])) {
                return self::failure(401, "no authorization header present");
            }

            # https://tools.ietf.org/html/rfc6750
            $authorizationHeader = explode(" ", $server["HTTP_AUTHORIZATION"]);

            # too much whitespace
            if (count($authorizationHeader) !== 2) {
                return self::failure(401, "token must be given as \"Authorization: Bearer {\$token}\"");
            }

            # not rfc compliant
            if ($authorizationHeader[0] !== "Bearer") {
                return self::failure(401, "token must be given as \"Authorization: Bearer {\$token}\"");
            }

            # we have a token!
            $token = $authorizationHeader[1];

            # empty token
            if (empty($token)) {
                return self::failure(401, "empty token provided");
            }
        } # if (empty($token))

        # check the database
        $query = "select UserID, Token, Revoked from api_user_tokens where UserID = ?";
        $row = $app->dbNew->row($query, [$userId]);
        #~d($row);exit;

        if (!$row) {
            return self::failure(401, "token not found");
        }

        # user revoked the token
        if (intval($row["Revoked"]) === 1) {
            return self::failure(401, "token revoked");
        }

        # user doesn't own that token
        if ($userId !== intval($row["UserID"])) {
            return self::failure(401, "token user mismatch");
        }

        /*
        # user is disabled
        if (\User::isDisabled($userId)) {
            return self::failure(401, "user disabled");
        }
        */

        # wrong token provided
        if (!password_verify($token, strval($row["Token"]))) {
            return self::failure(401, "wrong token provided");
        }

        # okay
        return true;
    }


    /**
     * success
     *
     * @see https://jsonapi.org/examples/
     */
    public static function success($response)
    {
        if (headers_sent()) {
            return false;
        }

        if (empty($response)) {
            return self::failure("the server provided no payload", 500);
        }

        header("Content-Type: application/json; charset=utf-8");
        print json_encode(
            [
                "id" => uniqid(),
                "status" => "success",
                "code" => 200,

                "data" => $response,

                "meta" => [
                    "info" => self::info(),
                    "debug" => self::debug(),
                    "mode" => self::$mode,
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
    public static function failure(int $code = 400, string $response = "bad request")
    {
        if (headers_sent()) {
            return false;
        }

        header("Content-Type: application/json; charset=utf-8");
        print json_encode(
            [
                "id" => uniqid(),
                "status" => "failure",
                "code" => $code,

                "data" => $response,

                "meta" => [
                    "info" => self::info(),
                    "debug" => self::debug(),
                    "mode" => self::$mode,
                ],
            ],
        );

        exit;
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
     * selfTest
     */
    public static function selfTest()
    {
    }
} # class

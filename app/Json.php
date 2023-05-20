<?php

declare(strict_types=1);


/**
 * Json
 *
 * Adapted from OPS's abstract class.
 *
 * @see https://github.com/OPSnet/Gazelle/blob/master/app/Json.php
 */

class Json
{
    private $mode = null;
    private $source = null;
    private $version = null;


    /**
     * __construct
     */
    public function __construct()
    {
        $app = \Gazelle\App::go();

        $this->mode = 0;
        $this->source = $app->env->siteName;
        $this->version = 1;
    }


    /**
     * checkToken
     *
     * Validates an authorization header and API token.
     */
    public function checkToken(int $userId, string $token = "")
    {
        $app = \Gazelle\App::go();

        # get the token off the headers
        if (empty($token)) {
            # escape bearer token
            $server = Http::request("server");

            # no header present
            if (empty($server["HTTP_AUTHORIZATION"])) {
                return $this->failure(401, "no authorization header present");
            }

            # https://tools.ietf.org/html/rfc6750
            $authorizationHeader = explode(" ", $server["HTTP_AUTHORIZATION"]);

            # too much whitespace
            if (count($authorizationHeader) !== 2) {
                return $this->failure(401, "token must be given as \"Authorization: Bearer {\$token}\"");
            }

            # not rfc compliant
            if ($authorizationHeader[0] !== "Bearer") {
                return $this->failure(401, "token must be given as \"Authorization: Bearer {\$token}\"");
            }

            # we have a token!
            $token = $authorizationHeader[1];

            # empty token
            if (empty($token)) {
                return $this->failure(401, "empty token provided");
            }
        } # if (empty($token))

        # check the database
        $query = "select UserID, Token, Revoked from api_tokens where UserID = ?";
        $row = $app->dbNew->row($query, [$userId]);
        #~d($row);exit;

        if (!$row) {
            return $this->failure(401, "token not found");
        }

        # user revoked the token
        if (intval($row["Revoked"]) === 1) {
            return $this->failure(401, "token revoked");
        }

        # user doesn't own that token
        if ($userId !== intval($row["UserID"])) {
            return $this->failure(401, "token user mismatch");
        }

        /*
        # user is disabled
        if (User::isDisabled($userId)) {
            return $this->failure(401, "user disabled");
        }
        */

        # wrong token provided
        if (!password_verify($token, strval($row["Token"]))) {
            return $this->failure(401, "wrong token provided");
        }

        # okay
        return true;
    }


    /**
     * success
     *
     * @see https://jsonapi.org/examples/
     */
    public function success($response)
    {
        if (headers_sent()) {
            return false;
        }

        if (empty($response)) {
            return $this->failure("the server provided no payload", 500);
        }

        header("Content-Type: application/json; charset=utf-8");
        print json_encode(
            [
                "id" => uniqid(),
                "status" => "success",
                "code" => 200,

                "data" => $response,

                "meta" => [
                    "info" => $this->info(),
                    "debug" => $this->debug(),
                    "mode" => $this->mode,
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
    public function failure(int $code = 400, string $response = "bad request")
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
                    "info" => $this->info(),
                    "debug" => $this->debug(),
                    "mode" => $this->mode,
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
    private function debug()
    {
        return [];

        /*
        $app = \Gazelle\App::go();
        $debug = Debug::go();

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
    private function info()
    {
        return [
            "info" => [
                "source" => $this->source,
                "version" => $this->version,
            ],
        ];
    }


    /**
     * selfTest
     */
    public function selfTest()
    {
    }
} # class

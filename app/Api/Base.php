<?php

declare(strict_types=1);


/**
 * Gazelle\Api\Base
 *
 * Adapted from OPS's abstract class.
 *
 * @see https://github.com/OPSnet/Gazelle/blob/master/app/Json.php
 */

namespace Gazelle\Api;

class Base
{
    # https://jsonapi.org/format/#document-jsonapi-object
    private static string $version = "1.2.0";


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
            self::failure(401, "unauthorized");
        }

        # https://tools.ietf.org/html/rfc6750
        if (!preg_match("/^Bearer\s+(.+)$/", $server["HTTP_AUTHORIZATION"], $matches)) {
            self::failure(401, "unauthorized");
        }

        # we have a token!
        $token = $matches[1];

        # empty token
        if (empty($token)) {
            self::failure(401, "unauthorized");
        }

        /** */

        # check the database
        $query = "select id, userId, token from api_tokens use index (userId_token) where deleted_at is null";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $good = password_verify($token, $row["token"]);
            if ($good) {
                /*
                # is the user disabled?
                if (\User::isDisabled($row["userId"])) {
                    self::failure(401, "user disabled");
                }
                */

                # return the data
                return $row;
            }
        }

        # default failure
        self::failure(401, "unauthorized");
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
            self::failure(401, "unauthorized");
            #self::failure(401, "no authorization header present");
        }

        # https://tools.ietf.org/html/rfc6750
        if (!preg_match("/^Bearer\s+(.+)$/", $server["HTTP_AUTHORIZATION"], $matches)) {
            self::failure(401, "unauthorized");
        }

        # we have a token!
        $token = $matches[1];

        # empty token
        if (empty($token)) {
            self::failure(401, "unauthorized");
        }

        /** */

        $query = "select sessionId from users_sessions where userId = ? order by expires desc limit 10";
        $ref = $app->dbNew->multi($query, [ $app->user->core["id"] ]);

        foreach ($ref as $row) {
            $backendKey = implode(".", [$row["sessionId"], $app->env->private("siteApiSecret")]);
            $good = password_verify($backendKey, $token);

            if ($good) {
                return;
            }
        }

        # default failure
        self::failure(401, "unauthorized");
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
        $permissions = array_map("strtolower", $permissions);
        $allowedPermissions = ["create", "read", "update", "delete"];

        # check that all permissions are valid
        if (array_intersect($permissions, $allowedPermissions) !== $permissions) {
            self::failure(403, "forbidden");
        }

        # check the token's permissions
        $query = "select permissions from api_tokens where id = ?";
        $ref = $app->dbNew->single($query, [$tokenId]);

        if (empty($ref)) {
            self::failure(403, "forbidden");
        }

        # check that all required permissions are present
        $tokenPermissions = json_decode($ref, true);
        if (array_intersect($permissions, $tokenPermissions) !== $permissions) {
            self::failure(403, "forbidden");
        }
    }


    /** responses */


    /**
     * success
     *
     * @param $response HTTP success code (usually 2xx)
     * @param $data the data set in the JSON response
     *
     * @see https://jsonapi.org/format/#document-structure
     */
    public static function success(int $code = 200, $data = []): void
    {
        $app = \Gazelle\App::go();

        $response = [
            "data" => $data,

            "meta" => [
                "id" => $app->dbNew->stringUuid($app->dbNew->uuid()),
                "count" => (is_array($data) ? count($data) : 1),
                "status" => "success",
                "version" => self::$version,
            ],
        ];

        if ($app->env->dev) {
            #$response["meta"]["debug"] = self::debug();
        }

        /** */

        http_response_code($code);
        header("Content-Type: application/vnd.api+json; charset=utf-8");

        echo json_encode($response);
        exit;
    }


    /**
     * failure
     *
     * @param $response HTTP error code (usually 4xx)
     * @param string $data the error set in the JSON response
     *
     * @see https://jsonapi.org/format/#errors
     */
    public static function failure(int $code = 400, $data = "bad request"): void
    {
        $app = \Gazelle\App::go();

        $response = [
            "errors" => $data,

            "meta" => [
                "id" => $app->dbNew->stringUuid($app->dbNew->uuid()),
                "count" => (is_array($data) ? count($data) : 1),
                "status" => "failure",
                "version" => self::$version,
            ],
        ];

        if ($app->env->dev) {
            $response["meta"]["debug"] = self::debug();
        }

        /** */

        http_response_code($code);
        header("Content-Type: application/vnd.api+json; charset=utf-8");

        echo json_encode($response);
        exit;
    }


    /**
     * debug
     */
    private static function debug()
    {
        $app = \Gazelle\App::go();

        $data = [
            "database" => $app->dbNew->meta(),
            "git" => \Debug::gitInfo(),
            "session" => $_SESSION["token"] ?? "no session",
        ];

        $includes = get_included_files();
        foreach ($includes as $include) {
            if (!str_starts_with($include, "{$app->env->serverRoot}/vendor")) {
                $data["includes"][] = $include;
            }
        }

        return $data;
    }
} # class

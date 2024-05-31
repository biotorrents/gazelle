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

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class Base
{
    # https://jsonapi.org/format/#document-jsonapi-object
    private static string $version = "1.2.0";

    # https://github.com/firebase/php-jwt
    private static string $algorithm = "EdDSA";
    private static int $expiresIn = 60 * 60; # 1 hour


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

        # escape bearer token
        $server = \Gazelle\Http::request("server");

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
     *
     * @return void
     */
    public static function validateFrontendHash(): void
    {
        $app = \Gazelle\App::go();

        # escape bearer token
        $server = \Gazelle\Http::request("server");

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


    /**
     * jwtPayload
     *
     * Returns a complete JWT if $debug = false,
     * and an unencoded array if $debug = true.
     *
     * @return array
     */
    public static function jwtPayload(bool $debug = false): array
    {
        $app = \Gazelle\App::go();

        # escape client credentials data
        $json = \Gazelle\Http::json();

        $now = time();
        $payload = [
            "aud" => $app->env->siteDomain, # audience
            "exp" => $now + self::$expiresIn, # expiration time
            "iat" => $now, # issued at
            "iss" => $app->env->siteDomain, # issuer
            "jti" => $app->dbNew->shortUuid(), # jwt id
            "nbf" => $now, # not before
        ];

        # different meta object for api and web
        # https://jsonapi.org/format/1.2/#document-meta
        match ($app->executionContext) {
            "api" => $payload["meta"] = [
                "clientId" => $json["clientId"] ?? null,
                "clientSecret" => $json["clientSecret"] ?? null,
            ],

            "web" => $payload["meta"] = [
                "sessionId" => session_id() ?? null,
                "userId" => $app->user->core["id"] ?? null,
            ],

            default => throw new \Exception("bad request"),
        };

        if ($debug) {
            return $payload;
        }

        $jwt = JWT::encode($payload, $app->env->private("jwtPrivateKey"), self::$algorithm);
        return [
            "accessToken" => $jwt,
            "tokenType" => "Bearer",
            "expiresIn" => self::$expiresIn,
        ];


    }


    /**
     * getJwt
     *
     * Issues a new JWT in response to this POST request:
     * { "clientId": "string", "clientSecret": "string" }
     *
     * @param bool $debug = false
     * @return void
     */
    public static function getJwt(): void
    {
        $app = \Gazelle\App::go();

        # escape client credentials data
        $json = \Gazelle\Http::json();

        $json["clientId"] ??= null;
        $json["clientSecret"] ??= null;

        if (!$json["clientId"] || !$json["clientSecret"]) {
            self::failure(401, "unauthorized");
        }

        # check the database
        $query = "select id, userId, token from api_tokens use index (userId_token) where id = ? and deleted_at is null";
        $row = $app->dbNew->row($query, [ $json["clientId"] ]);

        if (empty($row)) {
            self::failure(401, "unauthorized");
        }

        # verify the clientSecret against the token hash
        $good = password_verify($json["clientSecret"], $row["token"]);
        if (!$good) {
            self::failure(401, "unauthorized");
        }

        $response = self::jwtPayload();
        self::success(200, $response);
    }


    /**
     * validateJwt
     *
     * Validates an authorization header and JWT.
     *
     * @return ?array
     */
    public static function validateJwt(): ?array
    {
        $app = \Gazelle\App::go();

        /** */

        # escape bearer token
        $server = \Gazelle\Http::request("server");

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

        try {
            $decoded = JWT::decode($token, new Key($app->env->private("jwtPublicKey"), self::$algorithm));
        } catch (InvalidArgumentException $e) {
            # provided key/key-array is empty or malformed
            self::failure(400, $e->getMessage());
        } catch (DomainException $e) {
            # provided algorithm is unsupported OR
            # provided key is invalid OR
            # unknown error thrown in openSSL or libsodium OR
            # libsodium is required but not available
            self::failure(400, $e->getMessage());
        } catch (SignatureInvalidException $e) {
            # provided JWT signature verification failed
            self::failure(400, $e->getMessage());
        } catch (BeforeValidException $e) {
            # provided JWT is trying to be used before "nbf" claim OR
            # provided JWT is trying to be used before "iat" claim
            self::failure(400, $e->getMessage());
        } catch (ExpiredException $e) {
            # provided JWT is trying to be used after "exp" claim
            self::failure(400, $e->getMessage());
        } catch (UnexpectedValueException $e) {
            # provided JWT is malformed OR
            # provided JWT is missing an algorithm / using an unsupported algorithm OR
            # provided JWT algorithm does not match provided key OR
            # provided key ID in key/key-array is empty or invalid
            self::failure(400, $e->getMessage());
        }

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


    /** token permissions */


    /**
     * validatePermissions
     *
     * Checks a token's permissions against a list of required permissions.
     *
     * @param int $tokenId the token's id
     * @param array $permissions the required permissions
     * @return void
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
     * @return void
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
     * @return void
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
     *
     * Gathers various debug information.
     *
     * @return array
     */
    private static function debug(): array
    {
        $app = \Gazelle\App::go();

        $data = [
            "database" => $app->dbNew->meta(),
            "git" => \Gazelle\Debug::gitInfo(),
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

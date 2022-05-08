<?php
declare(strict_types = 1);

/**
 * Adapted from
 * https://github.com/OPSnet/Gazelle/blob/master/app/Json.php
 */

class Json
{
    private $mode;
    private $source;
    private $version;


    /**
     * __construct
     */
    public function __construct()
    {
        $ENV = ENV::go();

        $this->mode = 0;
        $this->source = $ENV->SITE_NAME;
        $this->version = 1;
    }


    /**
     * checkToken
     *
     * Validates an anthorization header.
     */
    public function checkToken()
    {
        $ENV = ENV::go();
        
        # no header present
        if (empty($_SERVER["HTTP_AUTHORIZATION"])) {
            return $this->failure("no authorization header present");
        }

        # https://tools.ietf.org/html/rfc6750
        $authorizationHeader = explode(" ", esc($_SERVER["HTTP_AUTHORIZATION"]));

        # too much whitespace
        if (count($authorizationHeader) !== 2) {
            return $this->failure("token must be given as \"Authorization: Bearer {\$token}\"");
        }

        # not rfc compliant
        if ($authorizationHeader[0] !== "Bearer") {
            return $this->failure("token must be given as \"Authorization: Bearer {\$token}\"");
        }

        # we have a token
        $token = $authorizationHeader[1];

        # revoke by default
        $revoked = 1;
        $userId = intval(
            substr(
                Crypto::decrypt(
                    base64UrlDecode($token),
                    $ENV->getPriv('ENCKEY')
                ),
                32
            )
        );
    
        # check database
        if (!empty($userId)) {
            [$user['ID'], $revoked] = G::$db->row("select UserID, Revoked from api_user_tokens where UserID = ?", [$userId]);
        } else {
            return $this->failure('invalid token format');
        }
    
        # no user or revoked API token
        if (empty($user['ID']) || intval($revoked) === 1) {
            return $this->failure('token user mismatch');
        }
    
        # user doesn't own that token
        if (!is_null($token) && !Users::hasApiToken($userId, $token)) {
            return $this->failure('token revoked');
        }
        
        # user is disabled
        if (Users::isDisabled($userId)) {
            return $this->failure('user disabled');
        }

        # okay
        return true;
    }


    /**
     * success
     */
    public function success(array $payload)
    {
        if (headers_sent()) {
            return false;
        }

        if (empty($payload)) {
            return $this->failure(message: "the server provided no payload", response: 500);
        }

        header("Content-Type: application/json; charset=utf-8");
        print json_encode(
            array_merge(
                [
                    "status" => "success",
                    "response" => $payload,
                ],
                $this->info(),
                $this->debug()
            ),
            $this->mode
        );
    }


    /**
     * failure
     *
     * General failure routine for when bad things happen.
     *
     * @param string $message The error set in the JSON response
     * @param $response HTTP error code (usually 4xx client errors)
     */
    public function failure(string $message = "bad request", int $response = 400)
    {
        if (headers_sent()) {
            return false;
        }

        header("Content-Type: application/json; charset=utf-8");
        print json_encode(
            array_merge(
                [
                    "status" => "failure",
                    "response" => $response,
                    "error" => $message,
                ],
                $this->info(),
                $this->debug(),
            ),
            $this->mode
        );
    }


    /**
     * debug
     */
    private function debug()
    {
        $ENV = ENV::go();
        $debug = Debug::go();

        if ($ENV->DEV) {
            return [
                "debug" => [
                    "queries"  => $debug->get_queries(),
                    "searches" => $debug->get_sphinxql_queries(),
                ],
            ];
        } else {
            return [];
        }
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
            ]
        ];
    }


    /**
     * fetch
     *
     * Get resources over the API to populate Gazelle display.
     * Instead of copy-pasting the same SQL queries in many places.
     *
     * Takes a query string, e.g., "action=torrentgroup&id=1."
     * Requires an API key for the user ID 0 (minor database surgery).
     */
    public function fetch(string $action, array $params = [])
    {
        $ENV = ENV::go();

        $token = $ENV->getPriv("SELF_API");
        $params = implode("&", $params);

        $ch = curl_init();

        # todo: Make this use localhost and not HTTPS
        curl_setopt($ch, CURLOPT_URL, "https://{$ENV->SITE_DOMAIN}/api.php?action={$action}&{$params}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        # https://docs.biotorrents.de
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                "Accept: application/json",
                "Authorization: Bearer {$token}",
            ]
        );

        $data = curl_exec($ch);
        curl_close($ch);

        # Error out on bad query
        if ($data) {
            return $this->success($data);
        } else {
            return $this->failure();
        }
    }
}

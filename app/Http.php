<?php

declare(strict_types=1);


/**
 * Http
 *
 * For sending raw HTTP interactions,
 * e.g., response codes and headers.
 */

class Http
{
    # cookie params
    private static $cookiePrefix = "__Secure-";
    private static $cookieDuration = "tomorrow";


    /**
     * redirect
     *
     * Simple header("Location: foo") wrapper.
     * Handles checks, format, and exiting.
     * Goes to "/" by default with no argument.
     */
    public static function redirect(string $uri = ""): void
    {
        if (headers_sent()) {
            exit;
        }

        $parsed = parse_url($uri);
        $uri = htmlentities($uri);

        $parsed["scheme"] ??= null;
        $parsed["host"] ??= null;

        # local
        if (!$parsed["scheme"] || !$parsed["host"]) {
            header("Location: /{$uri}");
        }

        # remote
        else {
            header("Location: {$uri}");
        }

        exit;
    }


    /**
     * csrf
     *
     * @see https://github.com/paragonie/anti-csrf
     */
    public static function csrf()
    {
        $app = \Gazelle\App::go();

        try {
            $csrf = new ParagonIE\AntiCSRF\AntiCSRF();
            if (!empty($_POST)) {
                if ($csrf->validateRequest()) {
                    return true;
                } else {
                    # todo: this just results in a blank page
                    self::response(403);
                }
            }
        } catch (\Throwable $e) {
            return null;
        }
    }


    /**
     * request
     *
     * Validates and escapes request parameters.
     *
     * @param string $method the method to filter, if any
     * @return array $safe the filtered superglobal
     */
    public static function request(string $method = ""): array
    {
        # lowercase
        $method = strtolower($method);

        # hold escapes
        $safe = [
            "cookie" => [],
            "files" => [],
            "get" => [],
            "post" => [],
            "request" => [],
            "server" => [],
        ];

        # error out on bad input
        if (!empty($method) && !in_array($method, array_keys($safe))) {
            throw new Exception("the method {$method} isn't supported");
        }

        # escape each untrusted superglobal
        # sucks less than filter_input_array

        # cookie
        $safe["cookie"] = $_COOKIE;
        array_walk_recursive($safe["cookie"], function ($value) {
            return \Gazelle\Text::esc($value);
        });

        # files
        $safe["files"] = $_FILES;
        array_walk_recursive($safe["files"], function ($value) {
            return \Gazelle\Text::esc($value);
        });

        # get
        $safe["get"] = $_GET;
        array_walk_recursive($safe["get"], function ($value) {
            return \Gazelle\Text::esc($value);
        });

        # post
        $safe["post"] = $_POST;
        array_walk_recursive($safe["post"], function ($value) {
            return \Gazelle\Text::esc($value);
        });

        # request
        $safe["request"] = $_REQUEST;
        array_walk_recursive($safe["request"], function ($value) {
            return \Gazelle\Text::esc($value);
        });

        # server
        $safe["server"] = $_SERVER;
        array_walk_recursive($safe["server"], function ($value) {
            return \Gazelle\Text::esc($value);
        });

        # should be okay
        if (!empty($method)) {
            return $safe[$method];
        }

        return $safe;
    }


    /**
     * json
     *
     * Parse a Content-Type: application/vnd.api+json request.
     */
    public static function json(): array
    {
        $app = \Gazelle\App::go();

        $json = json_decode(file_get_contents("php://input"), true);

        /*
        if (json_last_error() !== JSON_ERROR_NONE) {
            $app->log->error("json error: " . json_last_error_msg());
            self::response(400);
        }
        */

        return $json;
    }


    /**
     * cookie
     *
     * Helper for self::request("cookie").
     */
    public static function cookie(): array
    {
        return self::request("cookie");
    }


    /**
     * files
     *
     * Helper for self::request("files").
     */
    public static function files(): array
    {
        return self::request("files");
    }


    /**
     * get
     *
     * Helper for self::request("get").
     */
    public static function get(): array
    {
        return self::request("get");
    }


    /**
     * post
     *
     * Helper for self::request("post").
     */
    public static function post(): array
    {
        return self::request("post");
    }


    /**
     * server
     *
     * Helper for self::request("server").
     */
    public static function server(): array
    {
        return self::request("server");
    }


    /**
     * assertRequest
     *
     * Used to check if keys in $_POST and $_GET are all set, and throws an error if not.
     * This reduces "if" statement redundancy for a lot of variables.
     *
     * @param array $request either $_POST or $_GET
     * @param array $keys the keys to ensure are set
     * @param boolean $allowEmpty if true, empty keys won't error
     * @param int $error the error code absent an asserted key
     */
    public static function assertRequest(array $request, array $keys = null, bool $allowEmpty = false, int $error = 400): bool
    {
        # keys exists
        if (isset($keys)) {
            foreach ($keys as $k) {
                if (!isset($request[$k]) || ($allowEmpty === false && $request[$k] === "")) {
                    self::response($error);
                    return false;
                }
            }
        }

        # generic empty
        else {
            foreach ($request as $r) {
                if (!isset($r) || ($allowEmpty === false && $r === "")) {
                    self::response($error);
                    return false;
                }
            }
        }

        # conditions met
        return true;
    }


    /**
     * response
     *
     * Send a well-formed HTTP response string.
     * Updated to modern HTTP/2 protocol and codes.
     *
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     * @see https://www.php.net/manual/en/function.http-response-code.php#107261
     */
    public static function response(int $code = 200): void
    {
        if (headers_sent()) {
            exit;
        }

        switch ($code) {
            case 100: $text = "Continue";
                break;

            case 101: $text = "Switching Protocols";
                break;

                /** */

            case 200: $text = "OK";
                break;

            case 201: $text = "Created";
                break;

            case 202: $text = "Accepted";
                break;

            case 203: $text = "Non-Authoritative Information";
                break;

            case 204: $text = "No Content";
                break;

            case 205: $text = "Reset Content";
                break;

            case 206: $text = "Partial Content";
                break;

                /** */

            case 300: $text = "Multiple Choices";
                break;

            case 301: $text = "Moved Permanently";
                break;

            case 302: $text = "Found";
                break;

            case 303: $text = "See Other";
                break;

            case 304: $text = "Not Modified";
                break;

            case 305: $text = "Use Proxy";
                break;

                /** */

            case 400: $text = "Bad Request";
                break;

            case 401: $text = "Unauthorized";
                break;

            case 402: $text = "Payment Required";
                break;

            case 403: $text = "Forbidden";
                break;

            case 404: $text = "Not Found";
                break;

            case 405: $text = "Method Not Allowed";
                break;

            case 406: $text = "Not Acceptable";
                break;

            case 407: $text = "Proxy Authentication Required";
                break;

            case 408: $text = "Request Timeout";
                break;

            case 409: $text = "Conflict";
                break;

            case 410: $text = "Gone";
                break;

            case 411: $text = "Length Required";
                break;

            case 412: $text = "Precondition Failed";
                break;

            case 413: $text = "Payload Too Large";
                break;

            case 414: $text = "URI Too Long";
                break;

            case 415: $text = "Unsupported Media Type";
                break;

                /** */

            case 500: $text = "Internal Server Error";
                break;

            case 501: $text = "Not Implemented";
                break;

            case 502: $text = "Bad Gateway";
                break;

            case 503: $text = "Service Unavailable";
                break;

            case 504: $text = "Gateway Timeout";
                break;

            case 505: $text = "HTTP Version Not Supported";
                break;

                /** */

            default:
                exit("unknown http status code " . htmlentities($code));
                break;
        }

        $protocol = $_SERVER["SERVER_PROTOCOL"] ?? "HTTP/2";
        $GLOBALS["http_response_code"] = $code;

        header("{$protocol} {$code} {$text}");
        #exit;
    }


    /** cookie crud */


    /**
     * createCookie
     *
     * Sets secure cookies from an associative array.
     * Note that $secure and $httponly are hardcoded.
     *
     * @param array $cookie ["key => "value", "foo" => "bar"]
     * @param string $when strtotime format
     * @return void setcookie
     *
     * @see https://www.php.net/manual/en/function.setcookie.php
     */
    public static function createCookie(array $cookie, string $when = "tomorrow"): void
    {
        $app = \Gazelle\App::go();

        foreach ($cookie as $key => $value) {
            if (empty($key)) {
                continue;
            }

            # set time or use default
            $time = strtotime($when) ?? self::$cookieDuration;

            setcookie(
                self::$cookiePrefix.$key,
                \Gazelle\Text::esc($value),
                [
                    "expires" => $time,
                    "path" => "/",
                    "domain" => $app->env->siteDomain,
                    "secure" => true,
                    "httponly" => true,
                    "samesite" => "Lax",
                    #"samesite" => "Strict",
                ]
            );
        }
    }


    /**
     * readCookie
     *
     * Untrustworthy user input.
     * Reads from $_COOKIE superglobal.
     *
     * @param string $key the cookie key
     * @return ?string the sanitized cookie
     */
    public static function readCookie(string $key): ?string
    {
        $cookie = self::request("cookie");

        return $cookie[self::$cookiePrefix.$key] ?? null;
    }


    /**
     * updateCookie
     *
     * Updates a cookie by key.
     */
    public static function updateCookie(array $cookie, string $when = "tomorrow"): void
    {
        self::createCookie($cookie, $when);
    }


    /**
     * deleteCookie
     *
     * Deletes a cookie by key.
     *
     * @param string $key the cookie key
     * @return bool self::createCookie
     */
    public static function deleteCookie(string $key): void
    {
        self::createCookie([self::$cookiePrefix.$key, ""], "now");
    }


    /**
     * flushCookies
     *
     * Delete all user cookies.
     * Uses the $_COOKIE superglobal.
     *
     * @return bool self::deleteCookie
     */
    public static function flushCookies(): void
    {
        $cookie = self::request("cookie");

        foreach ($cookie as $key => $value) {
            self::deleteCookie($key);
        }
    }
} # class

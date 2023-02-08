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

        if (!$parsed["scheme"] || !$parsed["host"]) {
            # local
            header("Location: /{$uri}");
        } else {
            # remote
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
        $csrf = new ParagonIE\AntiCSRF\AntiCSRF();
        if (!empty($_POST)) {
            if ($csrf->validateRequest()) {
                return true;
            } else {
                self::response(403);
            }
        }
    }


    /**
     * query
     *
     * Validates and escapes request parameters.
     *
     * @param string $method the method to filter, if any
     * @return array $safe the filtered superglobal
     */
    public static function query(string $method = ""): array
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
            return Text::esc($value);
        });

        /*
        foreach ($_COOKIE as $key => $value) {
            $key = Text::esc($key);
            $value = Text::esc($value);
            $safe["cookie"][$key] = $value;
        }
        */

        # files
        $safe["files"] = $_FILES;
        array_walk_recursive($safe["files"], function ($value) {
            return Text::esc($value);
        });

        /*
        foreach ($_FILES as $key => $value) {
            # not recursive
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $k = Text::esc($k);
                    $v = Text::esc($v);
                    $safe["files"][$key][$v] = $v;
                }
            }
        }
        */

        # get
        $safe["get"] = $_GET;
        array_walk_recursive($safe["get"], function ($value) {
            return Text::esc($value);
        });

        /*
        foreach ($_GET as $key => $value) {
            # not recursive
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $k = Text::esc($k);
                    $v = Text::esc($v);
                    $safe["get"][$key][$v] = $v;
                }
            }

            # normal key => value
            else {
                $key = Text::esc($key);
                $value = Text::esc($value);
                $safe["get"][$key] = $value;
            }
        }
        */

        # post
        $safe["post"] = $_POST;
        array_walk_recursive($safe["post"], function ($value) {
            return Text::esc($value);
        });

        /*
        foreach ($_POST as $key => $value) {
            # not recursive
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $k = Text::esc($k);
                    $v = Text::esc($v);
                    $safe["post"][$key][$v] = $v;
                }
            }

            # normal key => value
            else {
                $key = Text::esc($key);
                $value = Text::esc($value);
                $safe["post"][$key] = $value;
            }
        }
        */

        # request
        $safe["request"] = $_REQUEST;
        array_walk_recursive($safe["request"], function ($value) {
            return Text::esc($value);
        });

        /*
        foreach ($_REQUEST as $key => $value) {
            # not recursive
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $k = Text::esc($k);
                    $v = Text::esc($v);
                    $safe["request"][$key][$v] = $v;
                }
            }

            # normal key => value
            else {
                $key = Text::esc($key);
                $value = Text::esc($value);
                $safe["request"][$key] = $value;
            }
        }
        */

        # server
        $safe["server"] = $_SERVER;
        array_walk_recursive($safe["server"], function ($value) {
            return Text::esc($value);
        });

        /*
        foreach ($_SERVER as $key => $value) {
            # sanitize client spoofed keys
            if (str_starts_with($key, "HTTP_") || str_starts_with($key, "REMOTE_")) {
                $key = Text::esc($key);
                $value = Text::esc($value);
                $safe["server"][$key] = $value;
            }

            # make server keys available
            # NOT NECESSARILY SAFE VALUES!
            else {
                $safe["server"][$key] = $value;
            }
        }
        */

        # should be okay
        if (!empty($method)) {
            return $safe[$method];
        }

        return $safe;
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
            # 1xx informational response
            case 100: $text = "Continue";
                break;
            case 101: $text = "Switching Protocols";
                break;

                # 2xx success
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

                # 3xx redirection
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

                # 4xx client errors
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

                # 5xx server errors
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

            default:
                exit("unknown http status code " . htmlentities($code));
                break;
        }

        $protocol = (isset($_SERVER["SERVER_PROTOCOL"]))
            ? $_SERVER["SERVER_PROTOCOL"]
            : "HTTP/2";

        $GLOBALS["http_response_code"] = $code;
        header("{$protocol} {$code} {$text}");
        exit;
    }


    /** cookies */


    /**
     * getCookie
     *
     * Untrustworthy user input.
     * Reads from $_COOKIE superglobal.
     *
     * @param string $key the cookie key
     * @return the sanitized cookie or false
     */
    public static function getCookie(string $key): string|bool
    {
        $cookie = self::query("cookie");

        return (isset($cookie[self::$cookiePrefix.$key]))
            ? $cookie[self::$cookiePrefix.$key]
            : false;
    }


    /**
     * setCookie
     *
     * Sets secure cookies from an associative array.
     * Note that $secure and $httponly are hardcoded.
     *
     * @see https://www.php.net/manual/en/function.setcookie.php
     *
     * @param array $cookie ["key => "value", "foo" => "bar"]
     * @param string $when strtotime format
     * @return bool setcookie
     */
    public static function setCookie(array $cookie, string $when = "tomorrow"): void
    {
        $app = App::go();

        foreach ($cookie as $key => $value) {
            if (empty($key)) {
                continue;
            }

            # set time or use default
            $time = strtotime($when) ?? self::$cookieDuration;

            setcookie(
                self::$cookiePrefix.$key,
                Text::esc($value),
                [
                    "expires" => $time,
                    "path" => "/",
                    "domain" => $app->env->siteDomain,
                    "secure" => true,
                    "httponly" => true,
                    "samesite" => "Strict",
                ]
            );
        }
    }


    /**
     * deleteCookie
     *
     * Deletes a cookie by key.
     *
     * @param string $key The cookie key
     * @return bool self::setCookie (setcookie)
     */
    public static function deleteCookie(string $key): void
    {
        self::setCookie([self::$cookiePrefix.$key, ""], "now");
    }


    /**
     * flushCookies
     *
     * Delete all user cookies.
     * Uses the $_COOKIE superglobal.
     *
     * @return bool self::del (setcookie)
     */
    public static function flushCookies(): void
    {
        $cookie = self::query("cookie");

        foreach ($cookie as $key => $value) {
            self::deleteCookie($key);
        }
    }
} # class

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
    # optional cookie params
    private static $cookiePrefix = "";
    private static $cookieDuration = "tomorrow";


    /**
     * redirect
     *
     * Simple header("Location: foo") wrapper.
     * Handles checks, format, and exiting.
     */
    public static function redirect(string $uri)
    {
        if (headers_sent()) {
            return false;
        }

        $uri = htmlentities($uri);
        header("Location: /{$uri}");
        exit;
    }


    /**
     * query
     *
     * Validates and escapes request parameters.
     *
     * @param string $method The HTTP method to filter, if any
     * @return array $safe The filtered
     */
    public static function query(string $method = ""): array
    {
        # lowercase
        $method = strtolower($method);

        # hold escapes
        $safe = [
            "get" => [],
            "post" => [],
            "cookie" => [],
            "files" => [],
            "server" => [],
        ];

        # error out on bad input
        if (!empty($method) && !in_array($method, array_keys($safe))) {
            throw new Exception("Supplied method {$method} isn't supported");
        }

        # escape each untrusted superglobal
        # sucks less than filter_input_array
        foreach ($_GET as $key => $value) {
            array_push($safe["get"], [Text::esc($key), Text::esc($value)]);
        }

        foreach ($_POST as $key => $value) {
            array_push($safe["post"], [Text::esc($key), Text::esc($value)]);
        }

        foreach ($_COOKIE as $key => $value) {
            array_push($safe["cookie"], [Text::esc($key), Text::esc($value)]);
        }
        
        foreach ($_FILES as $key => $value) {
            array_push($safe["files"], [Text::esc($key), Text::esc($value)]);
        }

        foreach ($_SERVER as $key => $value) {
            # sanitize client spoofed keys
            if (str_starts_with($key, "HTTP")) {
                array_push($safe["server"], [Text::esc($key), Text::esc($value)]);
            }

            # make server keys available
            # note strip_tags not Text::esc
            else {
                array_push($safe["server"], [strip_tags($key), strip_tags($value)]);
            }
        }


        # should be okay
        if (!empty($method)) {
            return $safe[$method];
        } else {
            return $safe;
        }
    }


    /**
     * assertRequest
     *
     * Used to check if keys in $_POST and $_GET are all set, and throws an error if not.
     * This reduces "if" statement redundancy for a lot of variables.
     *
     * @param array $request Either $_POST or $_GET, or whatever other array you want to check.
     * @param array $keys The keys to ensure are set.
     * @param boolean $allowEmpty If set to true, a key that is in the request but blank will not throw an error.
     * @param int $error The error code to throw if one of the keys isn't in the array.
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
    public static function response(int $code = 200)
    {
        if (headers_sent()) {
            return false;
        }

        switch ($code) {
            # 1xx informational response
            case 100: $text = "Continue"; break;
            case 101: $text = "Switching Protocols"; break;

            # 2xx success
            case 200: $text = "OK"; break;
            case 201: $text = "Created"; break;
            case 202: $text = "Accepted"; break;
            case 203: $text = "Non-Authoritative Information"; break;
            case 204: $text = "No Content"; break;
            case 205: $text = "Reset Content"; break;
            case 206: $text = "Partial Content"; break;

            # 3xx redirection
            case 300: $text = "Multiple Choices"; break;
            case 301: $text = "Moved Permanently"; break;
            case 302: $text = "Found"; break;
            case 303: $text = "See Other"; break;
            case 304: $text = "Not Modified"; break;
            case 305: $text = "Use Proxy"; break;

            # 4xx client errors
            case 400: $text = "Bad Request"; break;
            case 401: $text = "Unauthorized"; break;
            case 402: $text = "Payment Required"; break;
            case 403: $text = "Forbidden"; break;
            case 404: $text = "Not Found"; break;
            case 405: $text = "Method Not Allowed"; break;
            case 406: $text = "Not Acceptable"; break;
            case 407: $text = "Proxy Authentication Required"; break;
            case 408: $text = "Request Timeout"; break;
            case 409: $text = "Conflict"; break;
            case 410: $text = "Gone"; break;
            case 411: $text = "Length Required"; break;
            case 412: $text = "Precondition Failed"; break;
            case 413: $text = "Payload Too Large"; break;
            case 414: $text = "URI Too Long"; break;
            case 415: $text = "Unsupported Media Type"; break;

            # 5xx server errors
            case 500: $text = "Internal Server Error"; break;
            case 501: $text = "Not Implemented"; break;
            case 502: $text = "Bad Gateway"; break;
            case 503: $text = "Service Unavailable"; break;
            case 504: $text = "Gateway Timeout"; break;
            case 505: $text = "HTTP Version Not Supported"; break;

            default:
                exit("Unknown HTTP status code " . htmlentities($code));
                break;
        }

        $protocol = (isset($_SERVER["SERVER_PROTOCOL"]))
            ? $_SERVER["SERVER_PROTOCOL"]
            : "HTTP/2";

        $GLOBALS["http_response_code"] = $code;
        header("{$protocol} {$code} {$text}");
        exit;
    }


    /** COOKIES */


    /**
     * getCookie
     *
     * Untrustworthy user input.
     * Reads from $_COOKIE superglobal.
     *
     * @param string $key The cookie key
     * @return The sanitized cookie or false
     */
    public static function getCookie(string $key)
    {
        $cookies = self::query("cookie");

        return (isset($cookies[self::$cookiePrefix.$key]))
            ? $cookies[self::$cookiePrefix.$key]
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
     * @param array $cookies ["key => "value", "foo" => "bar"]
     * @param string $when The time in strtotime format
     * @return bool setcookie
     */
    public static function setCookie(array $cookies, string $when = "tomorrow")
    {
        $ENV = ENV::go();

        foreach ($cookies as $key => $value) {
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
                    "domain" => $ENV->SITE_DOMAIN,
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
    public static function deleteCookie(string $key)
    {
        return self::setCookie([self::$cookiePrefix.$key, ""], "now");
    }


    /**
     * flushCookies
     *
     * Delete all user cookies.
     * Uses the $_COOKIE superglobal.
     *
     * @return bool self::del (setcookie)
     */
    public static function flushCookies()
    {
        $cookies = self::query("cookie");

        foreach ($cookies as $key => $value) {
            self::deleteCookie($key);
        }
    }
} # class

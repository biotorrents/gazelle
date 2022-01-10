<?php
declare(strict_types=1);

/**
 * HTTP class
 *
 * For sending raw HTTP interactions,
 * e.g., response codes and headers.
 */

class Http
{
    /**
     * response
     *
     * Send a well-formed HTTP response string.
     * Updated to modern HTTP/2 protocol and codes.
     *
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     * @see https://www.php.net/manual/en/function.http-response-code.php#107261
     */
    public static function response($code = null)
    {
        if ($code !== null) {
            switch ($code) {
                # 1xx informational response
                case 100: $text = 'Continue'; break;
                case 101: $text = 'Switching Protocols'; break;

                # 2xx success
                case 200: $text = 'OK'; break;
                case 201: $text = 'Created'; break;
                case 202: $text = 'Accepted'; break;
                case 203: $text = 'Non-Authoritative Information'; break;
                case 204: $text = 'No Content'; break;
                case 205: $text = 'Reset Content'; break;
                case 206: $text = 'Partial Content'; break;

                # 3xx redirection
                case 300: $text = 'Multiple Choices'; break;
                case 301: $text = 'Moved Permanently'; break;
                case 302: $text = 'Found'; break;
                case 303: $text = 'See Other'; break;
                case 304: $text = 'Not Modified'; break;
                case 305: $text = 'Use Proxy'; break;

                # 4xx client errors
                case 400: $text = 'Bad Request'; break;
                case 401: $text = 'Unauthorized'; break;
                case 402: $text = 'Payment Required'; break;
                case 403: $text = 'Forbidden'; break;
                case 404: $text = 'Not Found'; break;
                case 405: $text = 'Method Not Allowed'; break;
                case 406: $text = 'Not Acceptable'; break;
                case 407: $text = 'Proxy Authentication Required'; break;
                case 408: $text = 'Request Timeout'; break;
                case 409: $text = 'Conflict'; break;
                case 410: $text = 'Gone'; break;
                case 411: $text = 'Length Required'; break;
                case 412: $text = 'Precondition Failed'; break;
                case 413: $text = 'Payload Too Large'; break;
                case 414: $text = 'URI Too Long'; break;
                case 415: $text = 'Unsupported Media Type'; break;

                # 5xx server errors
                case 500: $text = 'Internal Server Error'; break;
                case 501: $text = 'Not Implemented'; break;
                case 502: $text = 'Bad Gateway'; break;
                case 503: $text = 'Service Unavailable'; break;
                case 504: $text = 'Gateway Timeout'; break;
                case 505: $text = 'HTTP Version Not Supported'; break;

                default:
                    exit('Unknown HTTP status code ' . htmlentities($code));
                    break;
                }

            $protocol = (isset($_SERVER['SERVER_PROTOCOL']))
                ? $_SERVER['SERVER_PROTOCOL']
                : 'HTTP/2';

            header("$protocol $code $text");
            $GLOBALS['http_response_code'] = $code;
        }

        # Default 200
        else {
            $code = (isset($GLOBALS['http_response_code']))
                ? $GLOBALS['http_response_code']
                : 200;
        }

        return $code;
    }
}

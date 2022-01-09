<?php
#declare(strict_types = 1);

// This is a file of miscellaneous functions that are called so damn often
// that it'd just be annoying to stick them in namespaces.

/**
 * Return true if the given string is numeric.
 * Should be \Security::checkInt but it's used a lot.
 *
 * @param mixed $Str
 * @return bool
 */
function is_number($Str)
{
    # todo: Strict equality breaks everything
    return $Str == strval(intval($Str));
}


/**
 * HTML-escape a string for output.
 * This is preferable to htmlspecialchars because it doesn't screw up upon a double escape.
 * There needs to be exactly one database-safe string escape funtion.
 *
 * @param string $Str
 * @return string escaped string.
 */
function display_str($Str)
{
    if ($Str === null || $Str === false || is_array($Str)) {
        return '';
    }

    if ($Str !== '' && !is_number($Str)) {
        $Str = Format::make_utf8($Str);
        $Str = mb_convert_encoding($Str, 'HTML-ENTITIES', 'UTF-8');
        $Str = preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,6};)/m", '&amp;', $Str);

        $Replace = array(
            "'",'"',"<",">",
            '&#128;','&#130;','&#131;','&#132;','&#133;','&#134;','&#135;','&#136;',
            '&#137;','&#138;','&#139;','&#140;','&#142;','&#145;','&#146;','&#147;',
            '&#148;','&#149;','&#150;','&#151;','&#152;','&#153;','&#154;','&#155;',
            '&#156;','&#158;','&#159;'
        );

        $With = array(
            '&#39;','&quot;','&lt;','&gt;',
            '&#8364;','&#8218;','&#402;','&#8222;','&#8230;','&#8224;','&#8225;','&#710;',
            '&#8240;','&#352;','&#8249;','&#338;','&#381;','&#8216;','&#8217;','&#8220;',
            '&#8221;','&#8226;','&#8211;','&#8212;','&#732;','&#8482;','&#353;','&#8250;',
            '&#339;','&#382;','&#376;'
        );

        $Str = str_replace($Replace, $With, $Str);
    }

    return $Str;
}


/**
 * Send a message to an IRC bot listening on SOCKET_LISTEN_PORT
 *
 * @param string $Raw An IRC protocol snippet to send.
 */
function send_irc($Channels = null, $Message = '')
{
    $ENV = ENV::go();

    // Check if IRC is enabled
    if (!$ENV->FEATURE_IRC || !$Channels) {
        return false;
    }

    # The fn takes an array or string
    $Dest = [];

    # Quick missed connection fix
    if (is_string($Channels)) {
        $Channels = explode(' ', $Channels);
    }
    
    # Strip leading #channel hash
    foreach ($Channels as $c) {
        array_push($Dest, preg_replace('/^#/', '', $c));
    }

    # Specific to AB's kana bot
    # https://github.com/anniemaybytes/kana
    $Command =
    implode('-', $Dest)
    . '|%|'
    . html_entity_decode(
        display_str($Message),
        ENT_QUOTES
    );

    # Original input sanitization
    $Command = str_replace(array("\n", "\r"), '', $Command);

    # Send the raw echo
    $IRCSocket = fsockopen(SOCKET_LISTEN_ADDRESS, SOCKET_LISTEN_PORT);
    fwrite($IRCSocket, $Command);
    fclose($IRCSocket);
}


/**
 * Advanced error handling
 *
 * Displays an HTTP status code with description and triggers an error.
 * If you use your own string for $Error, it becomes the error description.
 *
 * @param int|string $Error Error type
 * The available HTTP status codes are
 *  - Client:  [ 400, 403, 404, 405, 408, 413, 429 ]
 *  - Server:  [ 500, 502, 504 ]
 *  - Gazelle: [ -1, 0, !! ]
 *
 * @param boolean $NoHTML If true, the header/footer won't be shown, just the error.
 * @param string $Log If true, the user is given a link to search $Log in the site log.
 */
function error($Error = 1, $NoHTML = false, $Log = false)
{
    $ENV = ENV::go();

    # Error out on erroneous $Error
    (!$Error || $Error === null)
        ?? trigger_error('No $Error.', E_USER_ERROR);

    (!is_int($Error) || !is_string($Error))
        ?? trigger_error('$Error must be int or string.', E_USER_ERROR);

    # Formerly in sections/error/index.php
    if (!empty($_GET['e']) && is_int($_GET['e'])) {
        # Request error, i.e., /nonexistent_page.php
        $Error = $_GET['e'];
    }

    # https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
    switch ($Error) {
        /**
         * Client errors
         */
        case 400:
        case 1: # Probably the user's fault
            $Title = '400 Bad Request';
            $Message = 'The server cannot or will not process the request due to an apparent client error
                (e.g., malformed request syntax, size too large, invalid request message framing, or deceptive request routing).';
            break;

        case 403:
            $Title = '403 Forbidden';
            $Message = 'The request contained valid data and was understood by the server, but the server is refusing action.
                This may be due to the user not having the necessary permissions for a resource or needing an account of some sort, or attempting a prohibited action
                (e.g., creating a duplicate record where only one is allowed).
                The request should not be repeated.';
            break;

        case 404:
            $Title = '404 Not Found';
            $Message = 'The requested resource could not be found but may be available in the future.
                Subsequent requests by the client are permissible.';
            break;

        case 405:
            $Title = '405 Method Not Allowed';
            $Message = 'A request method is not supported for the requested resource;
                for example, a GET request on a form that requires data to be presented via POST,
                or a PUT request on a read-only resource.';
            break;
  
        case 408:
            $Title = '408 Request Timeout';
            $Message = 'The server timed out waiting for the request.
                According to HTTP specifications:
                "The client did not produce a request within the time that the server was prepared to wait.
                The client MAY repeat the request without modifications at any later time."';
            break;

        case 413:
            $Title = '413 Payload Too Large';
            $Message = 'The request is larger than the server is willing or able to process.';
            break;

        case 429:
            $Title = '429 Too Many Requests';
            $Message = 'The user has sent too many requests in a given amount of time.';
            break;

        /**
         * Server errors
         */
        case 500:
            $Title = '500 Internal Server Error';
            $Message = 'A generic error message,
                given when an unexpected condition was encountered and no more specific message is suitable.';
        break;

        case 502:
            $Title = '502 Bad Gateway';
            $Message = 'The server was acting as a gateway or proxy and received an invalid response from the upstream server.';
        break;

        case 504:
            $Title = '504 Gateway Timeout';
            $Message = 'The server was acting as a gateway or proxy and did not receive a timely response from the upstream server.';
        break;

        /**
         * Gazelle errors
         */
        case -1:
        #case 0: # Matches custom error strings
            $Title = 'Invalid Input';
            $Message = 'Something was wrong with the input provided with your request, and the server is refusing to fulfill it.';
        break;

        case '!!':
            $Title = 'Unexpected Error';
            $Message = 'You have encountered an unexpected error.';
        break;

        default:
            $Title = 'Other Error';
            $Message = "A function supplied its own error message: $Error";
    }

    # Normalize whitespace before adding features
    $Message = preg_replace('/\s{2,}/', ' ', $Message);

    /**
     * Append $Log
     * Formerly in sections/error/index.php
     */
    if ($Log) {
        $Message .= " <a href='log.php?search=$Title'>Search Log</a>";
    }

    /**
     * Append $DevInfo
     */
    if ($ENV->DEV) {
        $DateTime = strftime('%c', $_SERVER['REQUEST_TIME']);
        $BackTrace = !d();
        #$BackTrace = debug_string_backtrace();

        $Message .= ($NoHTML)
            ? $BackTrace
            : <<<HTML
        <br /><br />
        Please include the server response below,
        as in a <a href="/staff.php">Staff PM</a>,
        to help with debugging.

<pre>
```
$DateTime
{$_SERVER['SERVER_PROTOCOL']} {$_SERVER['REQUEST_METHOD']} $Title

{$_SERVER['SCRIPT_FILENAME']}
{$_SERVER['REQUEST_URI']}

$BackTrace
```
</pre>
HTML;
    }

    /**
     * Display HTML
     * Formerly in sections/error/index.php
     */
    if (empty($NoHTML)) {
        View::show_header($Title);
        echo $HTML = <<<HTML
        <div>
          <h2 class="header">$Title</h2>

          <div class="box pad">
            <p>$Message</p>
          </div>
        </div>
HTML;
        View::show_footer();
    }

    # Trigger the error
    $Debug = \Debug::go();
    #$Debug->profile();
    trigger_error("$Title - $Message", E_USER_ERROR);
    throw new Exception("$Title - $Message");
}


/**
 * debug_string_backtrace
 * https://stackoverflow.com/a/7039409
 */
function debug_string_backtrace()
{
    $e = new Exception;
    return $e->getTraceAsString();
}


/**
 * Convenience function. See doc in permissions.class.php
 */
function check_perms($PermissionName, $MinClass = 0)
{
    return Permissions::check_perms($PermissionName, $MinClass);
}


/**
 * Print the site's URL including the appropriate URI scheme, including the trailing slash
 */
function site_url()
{
    return 'https://' . SITE_DOMAIN . '/';
}
# End OT/Bio Gazelle util.php


    /**
     * OPS JSON functions
     * @see https://github.com/OPSnet/Gazelle/blob/master/classes/util.php
     */

/**
 * Print JSON status result with an optional message and die.
 */
function json_die($Status, $Message = 'bad parameters')
{
    json_print($Status, $Message);
    die();
}

/**
 * Print JSON status result with an optional message.
 */
function json_print($Status, $Message)
{
    if ($Status === 'success' && $Message) {
        $response = ['status' => $Status, 'response' => $Message];
    } elseif ($Message) {
        $response = ['status' => $Status, 'error' => $Message];
    } else {
        $response = ['status' => $Status, 'response' => []];
    }

    print(
        json_encode(
            add_json_info($response),
            JSON_UNESCAPED_SLASHES
        )
    );
}

/**
 * json_error
 */
function json_error($Code)
{
    echo json_encode(
        add_json_info(
            [
                'status' => 'failure',
                'error' => $Code,
                'response' => []
            ]
        )
    );
    die();
}

/**
 * json_or_error
 */
function json_or_error($JsonError, $Error = null, $NoHTML = false)
{
    if (defined('AJAX')) {
        json_error($JsonError);
    } else {
        error($Error ?? $JsonError, $NoHTML);
    }
}

/**
 * add_json_info
 */
function add_json_info($Json)
{
    $ENV = ENV::go();

    if (!isset($Json['info'])) {
        $Json = array_merge($Json, [
            'info' => [
                'source' => $ENV->SITE_NAME,
                'version' => 1,
            ],
        ]);
    }
    if (!isset($Json['debug']) && check_perms('site_debug')) {
        /** @var DEBUG $Debug */
        #global $Debug;
        $Debug = \Debug::go();
        $Json = array_merge($Json, [
            'debug' => [
                'queries' => $Debug->get_queries(),
                'searches' => $Debug->get_sphinxql_queries()
            ],
        ]);
    }
    return $Json;
}

# End OPS JSON functions
# Start OPS misc functions

/**
 * Hydrate an array from a query string (everything that follow '?')
 * This reimplements parse_str() and side-steps the issue of max_input_vars limits.
 *
 * Example:
 * in: li[]=14&li[]=31&li[]=58&li[]=68&li[]=69&li[]=54&li[]=5, param=li[]
 * parsed: ['li[]' => ['14', '31, '58', '68', '69', '5']]
 * out: ['14', '31, '58', '68', '69', '5']
 *
 * @param string query string from url
 * @param string url param to extract
 * @return array hydrated equivalent
 */
function parseUrlArgs(string $urlArgs, string $param): array
{
    $list = [];
    $pairs = explode('&', $urlArgs);
    foreach ($pairs as $p) {
        [$name, $value] = explode('=', $p, 2);
        if (!isset($list[$name])) {
            $list[$name] = $value;
        } else {
            if (!is_array($list[$name])) {
                $list[$name] = [$list[$name]];
            }
            $list[$name][] = $value;
        }
    }
    return array_key_exists($param, $list) ? $list[$param] : [];
}


/**
 * base64UrlEncode
 * base64UrlDecode
 * @see https://github.com/OPSnet/Gazelle/blob/master/app/Util/Text.php
 */
function base64UrlEncode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data)
{
    return base64_decode(str_pad(
        strtr($data, '-_', '+/'),
        strlen($data) % 4,
        '=',
        STR_PAD_RIGHT
    ));
}

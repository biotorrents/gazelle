<?php
#declare(strict_types = 1);

/**
 * Utilities
 *
 * Miscellaneous function not in classes.
 * All the non-classes are now in this file.
 */


    /**
     *
     * FROM BOOTSTRAP/APP.PHP
     *
     */


/**
 * Log out the current session
 */
function logout()
{
    global $SessionID;
    G::$user['ID'] = G::$user['ID'] ?? null;
    
    Http::deleteCookie('session');
    Http::deleteCookie('userid');
    Http::deleteCookie('keeplogged');
    
    #Http::flushCookies();

    if ($SessionID) {
        G::$db->prepared_query("
        DELETE FROM users_sessions
          WHERE UserID = '" . G::$user['ID'] . "'
          AND SessionID = '".db_string($SessionID)."'");

        G::$cache->begin_transaction('users_sessions_' . G::$user['ID']);
        G::$cache->delete_row($SessionID);
        G::$cache->commit_transaction(0);
    }

    G::$cache->delete_value('user_info_' . G::$user['ID']);
    G::$cache->delete_value('user_stats_' . G::$user['ID']);
    G::$cache->delete_value('user_info_heavy_' . G::$user['ID']);

    Http::redirect('login');
}

/**
 * logout_all_sessions
 */
function logout_all_sessions()
{
    $UserID = G::$user['ID'];

    G::$db->prepared_query("
    DELETE FROM users_sessions
      WHERE UserID = '$UserID'");

    G::$cache->delete_value('users_sessions_' . $UserID);
    logout();
}

/**
 * enforce_login
 */
function enforce_login()
{
    global $SessionID;
    
    if (!$SessionID || !G::$user) {
        Http::setCookie(['redirect' => $_SERVER['REQUEST_URI']]);
        logout();
    }
}

/**
 * Make sure $_GET['auth'] is the same as the user's authorization key.
 * Should be used for any user action that relies solely on GET.
 *
 * @param Are we using ajax?
 * @return authorisation status. Prints an error message to DEBUG_CHAN on IRC on failure.
 */
function authorize($Ajax = false)
{
    # Ugly workaround for API tokens
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) && $Document === 'api') {
        return true;
    } else {
        if (empty($_REQUEST['auth']) || $_REQUEST['auth'] !== G::$user['AuthKey']) {
            send_irc(DEBUG_CHAN, G::$user['Username']." just failed authorize on ".$_SERVER['REQUEST_URI'].(!empty($_SERVER['HTTP_REFERER']) ? " coming from ".$_SERVER['HTTP_REFERER'] : ""));
            error('Invalid authorization key. Go back, refresh, and try again.', $NoHTML = true);
            return false;
        }
    }
}


    /**
     *
     * THE ORIGINAL UTIL.PHP
     *
     */


/**
 * Return true if the given string is numeric.
 * Should be Security::checkInt but it's used a lot.
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
 * Send a message to an IRC bot listening on SOCKET_LISTEN_PORT
 *
 * @param string $Raw An IRC protocol snippet to send.
 *
 * THIS IS GOING AWAY.
 * MOVED TO ANNOUNCE CLASS.
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
        Text::esc($Message),
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
 * Error handling
 *
 * Displays an HTTP status code with description and triggers an error.
 * If you use your own string for $error, it becomes the error description.
 *
 * @param int|string $error Error type or message
 */
function error(int|string $error = 400, $NoHTML = false, $Log = false)
{
    $ENV = ENV::go();
    $twig = Twig::go();

    # https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
    $map = [
        400 => [
            '400 Bad Request',
            'The server cannot or will not process the request due to an apparent client error (e.g., malformed request syntax, size too large, invalid request message framing, or deceptive request routing).',
        ],

        403 => [
            '403 Forbidden',
            'The request contained valid data and was understood by the server, but the server is refusing action. This may be due to the user not having the necessary permissions for a resource or needing an account of some sort, or attempting a prohibited action (e.g. creating a duplicate record where only one is allowed). This code is also typically used if the request provided authentication by answering the WWW-Authenticate header field challenge, but the server did not accept that authentication. The request should not be repeated.',
        ],

        404 => [
            '404 Not Found',
            'The requested resource could not be found but may be available in the future. Subsequent requests by the client are permissible.',
        ],
    ];

    if (array_key_exists($error, $map)) {
        $title = $map[$error][0];
        $body = $map[$error][1];
    } else {
        $title = 'Other Error';
        $body = "A function supplied this error message: $error";
    }

    # Output HTML error page
    View::header($title);

    echo $twig->render(
        'error.twig',
        ['title' => $title, 'body' => $body]
    );

    View::footer();
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
        /** @var DEBUG $debug */
        #global $debug;
        $debug = Debug::go();
        $Json = array_merge($Json, [
            'debug' => [
                'queries' => $debug->get_queries(),
                'searches' => $debug->get_sphinxql_queries()
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


    /**
     *
     * FROM CLASSES/PARANOIA.CLASS.PHP
     *
     */


// Note: at the time this file is loaded, check_perms is not defined.
// Don't call check_paranoia in /bootstrap/app.php without ensuring check_perms has been defined

// The following are used throughout the site:
// uploaded, ratio, downloaded: stats
// lastseen: approximate time the user last used the site
// uploads: the full list of the user's uploads
// uploads+: just how many torrents the user has uploaded
// snatched, seeding, leeching: the list of the user's snatched torrents, seeding torrents, and leeching torrents respectively
// snatched+, seeding+, leeching+: the length of those lists respectively
// uniquegroups, perfectflacs: the list of the user's uploads satisfying a particular criterion
// uniquegroups+, perfectflacs+: the length of those lists
// If "uploads+" is disallowed, so is "uploads". So if "uploads" is in the array, the user is a little paranoid, "uploads+", very paranoid.

// The following are almost only used in /sections/user/user.php:
// requiredratio
// requestsfilled_count: the number of requests the user has filled
//   requestsfilled_bounty: the bounty thus earned
//   requestsfilled_list: the actual list of requests the user has filled
// requestsvoted_...: similar
// artistsadded: the number of artists the user has added
// torrentcomments: the list of comments the user has added to torrents
//   +
// collages: the list of collages the user has created
//   +
// collagecontribs: the list of collages the user has contributed to
//   +
// invitedcount: the number of users this user has directly invited

/**
 * Return whether currently logged in user can see $Property on a user with $Paranoia, $UserClass and (optionally) $UserID
 * If $Property is an array of properties, returns whether currently logged in user can see *all* $Property ...
 *
 * @param $Property The property to check, or an array of properties.
 * @param $Paranoia The paranoia level to check against.
 * @param $UserClass The user class to check against (Staff can see through paranoia of lower classed staff)
 * @param $UserID Optional. The user ID of the person being viewed
 * @return mixed 1 representing the user has normal access
 *               2 representing that the paranoia was overridden,
 *               false representing access denied.
 */
define("PARANOIA_ALLOWED", 1);
define("PARANOIA_OVERRIDDEN", 2);

function check_paranoia($Property, $Paranoia = false, $UserClass = false, $UserID = false)
{
    global $Classes;
    if ($Property == false) {
        return false;
    }

    if (!is_array($Paranoia)) {
        $Paranoia = json_decode($Paranoia, true);
    }

    if (!is_array($Paranoia)) {
        $Paranoia = [];
    }

    if (is_array($Property)) {
        $all = true;
        foreach ($Property as $P) {
            $all = $all && check_paranoia($P, $Paranoia, $UserClass, $UserID);
        }
        return $all;
    } else {
        if (($UserID !== false) && (G::$user['ID'] == $UserID)) {
            return PARANOIA_ALLOWED;
        }

        $May = !in_array($Property, $Paranoia) && !in_array($Property . '+', $Paranoia);
        if ($May) {
            return PARANOIA_ALLOWED;
        }

        if (check_perms('users_override_paranoia', $UserClass)) {
            return PARANOIA_OVERRIDDEN;
        }

        $Override=false;
        switch ($Property) {
          case 'downloaded':
          case 'ratio':
          case 'uploaded':
          case 'lastseen':
            if (check_perms('users_mod', $UserClass)) {
                return PARANOIA_OVERRIDDEN;
            }
            break;

          case 'snatched': case 'snatched+':
            if (check_perms('users_view_torrents_snatchlist', $UserClass)) {
                return PARANOIA_OVERRIDDEN;
            }
            break;

          case 'uploads': case 'uploads+':
          case 'seeding': case 'seeding+':
          case 'leeching': case 'leeching+':
            if (check_perms('users_view_seedleech', $UserClass)) {
                return PARANOIA_OVERRIDDEN;
            }
            break;
            
          case 'invitedcount':
            if (check_perms('users_view_invites', $UserClass)) {
                return PARANOIA_OVERRIDDEN;
            }
            break;
        }
        return false;
    }
}


    /**
     *
     * FROM CLASSES/TIME.CLASS.PHP
     *
     */


/**
 * time_ago
 */
function time_ago($TimeStamp)
{
    if (!$TimeStamp) {
        return false;
    }
    if (!is_number($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
        $TimeStamp = strtotime($TimeStamp);
    }
    return time() - $TimeStamp;
}

/**
 * Returns a <span> by default but can optionally return the raw time
 * difference in text (e.g., "16 hours and 28 minutes", "1 day, 18 hours").
 */
function time_diff($TimeStamp, $Levels = 2, $Span = true, $Lowercase = false)
{
    if (!$TimeStamp) {
        return 'Never';
    }
    if (!is_number($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
        $TimeStamp = strtotime($TimeStamp);
    }
    $Time = time() - $TimeStamp;

    // If the time is negative, then it expires in the future.
    if ($Time < 0) {
        $Time = -$Time;
        $HideAgo = true;
    }

    $Years = floor($Time / 31556926); // seconds in one year
    $Remain = $Time - $Years * 31556926;

    $Months = floor($Remain / 2629744); // seconds in one month
    $Remain = $Remain - $Months * 2629744;

    $Weeks = floor($Remain / 604800); // seconds in one week
    $Remain = $Remain - $Weeks * 604800;

    $Days = floor($Remain / 86400); // seconds in one day
    $Remain = $Remain - $Days * 86400;

    $Hours=floor($Remain / 3600); // seconds in one hour
    $Remain = $Remain - $Hours * 3600;

    $Minutes = floor($Remain / 60); // seconds in one minute
    $Remain = $Remain - $Minutes * 60;

    $Seconds = $Remain;

    $Return = '';

    if ($Years > 0 && $Levels > 0) {
        $Return .= "$Years year".(($Years > 1) ? 's' : '');
        $Levels--;
    }

    if ($Months > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ', ' : '';
        $Return .= "$Months month".(($Months > 1) ? 's' : '');
        $Levels--;
    }

    if ($Weeks > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ', ' : '';
        $Return .= "$Weeks week".(($Weeks > 1) ? 's' : '');
        $Levels--;
    }

    if ($Days > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ', ' : '';
        $Return .= "$Days day".(($Days > 1) ? 's' : '');
        $Levels--;
    }

    if ($Hours > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ', ' : '';
        $Return .= "$Hours hour".(($Hours > 1) ? 's' : '');
        $Levels--;
    }

    if ($Minutes > 0 && $Levels > 0) {
        $Return .= ($Return != '') ? ' and ' : '';
        $Return .= "$Minutes min".(($Minutes > 1) ? 's' : '');
    }

    if ($Return == '') {
        $Return = 'Just now';
    } elseif (!isset($HideAgo)) {
        $Return .= ' ago';
    }

    if ($Lowercase) {
        $Return = strtolower($Return);
    }

    if ($Span) {
        return '<span class="time tooltip" title="'.date('M d Y, H:i', $TimeStamp).'">'.$Return.'</span>';
    } else {
        return $Return;
    }
}


/*************************
 * SQL utility functions *
 *************************/


/**
 * time_plus
 */
function time_plus($Offset)
{
    return date('Y-m-d H:i:s', time() + $Offset);
}

/**
 * time_minus
 */
function time_minus($Offset, $Fuzzy = false)
{
    if ($Fuzzy) {
        return date('Y-m-d 00:00:00', time() - $Offset);
    } else {
        return date('Y-m-d H:i:s', time() - $Offset);
    }
}

// This is never used anywhere with $timestamp set
// todo: Why don't we just use NOW() in the sql queries?
function sqltime($timestamp = null)
{
    return date('Y-m-d H:i:s', ($timestamp ?? time()));
}


    /**
     *
     * FROM CLASSES/PERMISSIONS_FORM.PHP
     *
     */


/**
 * Permissions form
 * user.php and tools.php
 *
 * This function is used to create both the class permissions form,
 * and the user custom permissions form.
 */

$PermissionsArray = array(
    'site_leech' => 'Can leech (Does this work?).',
    'site_upload' => 'Upload torrent access.',
    'site_vote' => 'Request vote access.',
    'site_submit_requests' => 'Request create access.',
    'site_advanced_search' => 'Advanced search access.',
    'site_top10' => 'Top 10 access.',
    'site_advanced_top10' => 'Advanced Top 10 access.',
    'site_torrents_notify' => 'Notifications access.',
    'site_collages_create' => 'Collage create access.',
    'site_collages_manage' => 'Collage manage access.',
    'site_collages_delete' => 'Collage delete access.',
    'site_collages_subscribe' => 'Collage subscription access.',
    'site_collages_personal' => 'Can have a personal collage.',
    'site_collages_renamepersonal' => 'Can rename own personal collages.',
    'site_make_bookmarks' => 'Bookmarks access.',
    'site_edit_wiki' => 'Wiki edit access.',
    'site_can_invite_always' => 'Can invite past user limit.',
    'site_send_unlimited_invites' => 'Unlimited invites.',
    'site_moderate_requests' => 'Request moderation access.',
    'site_delete_artist' => 'Can delete artists (must be able to delete torrents+requests).',
    'site_moderate_forums' => 'Forum moderation access.',
    'site_admin_forums' => 'Forum administrator access.',
    'site_forums_double_post' => 'Can double post in the forums.',
    'site_view_flow' => 'Can view stats and data pools.',
    'site_view_full_log' => 'Can view old log entries.',
    'site_view_torrent_snatchlist' => 'Can view torrent snatch lists.',
    'site_recommend_own' => 'Can recommend own torrents.',
    'site_manage_recommendations' => 'Recommendations management access.',
    'site_delete_tag' => 'Can delete tags.',
    'zip_downloader' => 'Download multiple torrents at once.',
    'site_debug' => 'Developer access.',
    'site_proxy_images' => 'Image proxy & anti-canary.',
    'site_search_many' => 'Can go past low limit of search results.',
    'site_ratio_watch_immunity' => 'Immune from being put on ratio watch.',
    'users_edit_usernames' => 'Can edit usernames.',
    'users_edit_ratio' => 'Can edit anyone\'s upload/download amounts.',
    'users_edit_own_ratio' => 'Can edit own upload/download amounts.',
    'users_edit_titles' => 'Can edit titles.',
    'users_edit_avatars' => 'Can edit avatars.',
    'users_edit_invites' => 'Can edit invite numbers and cancel sent invites.',
    'users_edit_watch_hours' => 'Can edit contrib watch hours.',
    'users_edit_reset_keys' => 'Can reset passkey/authkey.',
    'users_edit_profiles' => 'Can edit anyone\'s profile.',
    'users_view_friends' => 'Can view anyone\'s friends.',
    'users_reset_own_keys' => 'Can reset own passkey/authkey.',
    'users_edit_password' => 'Can change passwords.',
    'users_promote_below' => 'Can promote users to below current level.',
    'users_promote_to' => 'Can promote users up to current level.',
    'users_give_donor' => 'Can give donor access.',
    'users_warn' => 'Can warn users.',
    'users_disable_users' => 'Can disable users.',
    'users_disable_posts' => 'Can disable users\' posting privileges.',
    'users_disable_any' => 'Can disable any users\' rights.',
    'users_delete_users' => 'Can delete users.',
    'users_view_invites' => 'Can view who user has invited.',
    'users_view_seedleech' => 'Can view what a user is seeding or leeching.',
    'users_view_uploaded' => 'Can view a user\'s uploads, regardless of privacy level.',
    'users_view_keys' => 'Can view passkeys.',
    'users_view_ips' => 'Can view IP addresses.',
    'users_view_email' => 'Can view email addresses.',
    'users_invite_notes' => 'Can add a staff note when inviting someone.',
    'users_override_paranoia' => 'Can override paranoia.',
    'users_logout' => 'Can log users out (old?).',
    'users_make_invisible' => 'Can make users invisible.',
    'users_mod' => 'Basic moderator tools.',
    'torrents_edit' => 'Can edit any torrent.',
    'torrents_delete' => 'Can delete torrents.',
    'torrents_delete_fast' => 'Can delete more than 3 torrents at a time.',
    'torrents_freeleech' => 'Can make torrents freeleech.',
    'torrents_search_fast' => 'Rapid search (for scripts).',
    'torrents_fix_ghosts' => 'Can fix "ghost" groups on artist pages.',
    'screenshots_add' => 'Can add screenshots to any torrent and delete their own screenshots.',
    'screenshots_delete' => 'Can delete any screenshot from any torrent.',
    'admin_manage_news' => 'Can manage site news.',
    'admin_manage_blog' => 'Can manage the site blog.',
    'admin_manage_polls' => 'Can manage polls.',
    'admin_manage_forums' => 'Can manage forums (add/edit/delete).',
    'admin_manage_fls' => 'Can manage FLS.',
    'admin_reports' => 'Can access reports system.',
    'admin_advanced_user_search' => 'Can access advanced user search.',
    'admin_create_users' => 'Can create users through an administrative form.',
    'admin_donor_log' => 'Can view the donor log.',
    'admin_manage_ipbans' => 'Can manage IP bans.',
    'admin_clear_cache' => 'Can clear cached.',
    'admin_whitelist' => 'Can manage the list of allowed clients.',
    'admin_manage_permissions' => 'Can edit permission classes/user permissions.',
    'admin_schedule' => 'Can run the site schedule.',
    'admin_login_watch' => 'Can manage login watch.',
    'admin_manage_wiki' => 'Can manage wiki access.',
    'site_collages_recover' => 'Can recover \'deleted\' collages.',
    'torrents_add_artist' => 'Can add artists to any group.',
    'edit_unknowns' => 'Can edit unknown release information.',
    'forums_polls_create' => 'Can create polls in the forums.',
    'forums_polls_moderate' => 'Can feature and close polls.',
    'project_team' => 'Is part of the project team.',
    'torrents_edit_vanityhouse' => 'Can mark groups as part of Vanity House.',
    'artist_edit_vanityhouse' => 'Can mark artists as part of Vanity House.',
    'site_tag_aliases_read' => 'Can view the list of tag aliases.'
  );
  
  function permissions_form()
  {
      echo <<<HTML
      <div class="permission_container">
        <table>
          <tr class="colhead">
            <th>Site</th>
          </tr>
          
          <tr>
            <td>
  HTML;
  
      display_perm('site_leech', 'Can leech.');
      display_perm('site_upload', 'Can upload.');
      display_perm('site_vote', 'Can vote on requests.');
      display_perm('site_submit_requests', 'Can submit requests.');
      display_perm('site_advanced_search', 'Can use advanced search.');
      display_perm('site_top10', 'Can access top 10.');
      display_perm('site_torrents_notify', 'Can access torrents notifications system.');
      display_perm('site_collages_create', 'Can create collages.');
      display_perm('site_collages_manage', 'Can manage collages (add torrents, sorting).');
      display_perm('site_collages_delete', 'Can delete collages.');
      display_perm('site_collages_subscribe', 'Can access collage subscriptions.');
      display_perm('site_collages_personal', 'Can have a personal collage.');
      display_perm('site_collages_renamepersonal', 'Can rename own personal collages.');
      display_perm('site_advanced_top10', 'Can access advanced top 10.');
      display_perm('site_make_bookmarks', 'Can make bookmarks.');
      display_perm('site_edit_wiki', 'Can edit wiki pages.');
      display_perm('site_can_invite_always', 'Can invite users even when invites are closed.');
      display_perm('site_send_unlimited_invites', 'Can send unlimited invites.');
      display_perm('site_moderate_requests', 'Can moderate any request.');
      display_perm('site_delete_artist', 'Can delete artists (must be able to delete torrents+requests).');
      display_perm('forums_polls_create', 'Can create polls in the forums.');
      display_perm('forums_polls_moderate', 'Can feature and close polls.');
      display_perm('site_moderate_forums', 'Can moderate the forums.');
      display_perm('site_admin_forums', 'Can administrate the forums.');
      display_perm('site_view_flow', 'Can view site stats and data pools.');
      display_perm('site_view_full_log', 'Can view the full site log.');
      display_perm('site_view_torrent_snatchlist', 'Can view torrent snatch lists.');
      display_perm('site_recommend_own', 'Can add own torrents to recommendations list.');
      display_perm('site_manage_recommendations', 'Can edit recommendations list.');
      display_perm('site_delete_tag', 'Can delete tags.');
      display_perm('zip_downloader', 'Download multiple torrents at once.');
      display_perm('site_debug', 'View site debug tables.');
      display_perm('site_proxy_images', 'Proxy images through the server.');
      display_perm('site_search_many', 'Can go past low limit of search results.');
      display_perm('site_collages_recover', 'Can recover \'deleted\' collages.');
      display_perm('site_forums_double_post', 'Can double post in the forums.');
      display_perm('project_team', 'Part of the project team.');
      display_perm('site_tag_aliases_read', 'Can view the list of tag aliases.');
      display_perm('site_ratio_watch_immunity', 'Immune from being put on ratio watch.');
  
      echo <<<HTML
            </td>
          </tr>
        </table>
      </div>
      
      <div class="permission_container">
        <table>
          <tr class="colhead">
            <th>Users</th>
          </tr>
          
          <tr>
            <td>
  HTML;
  
      display_perm('users_edit_usernames', 'Can edit usernames.');
      display_perm('users_edit_ratio', 'Can edit anyone\'s upload/download amounts.');
      display_perm('users_edit_own_ratio', 'Can edit own upload/download amounts.');
      display_perm('users_edit_titles', 'Can edit titles.');
      display_perm('users_edit_avatars', 'Can edit avatars.');
      display_perm('users_edit_invites', 'Can edit invite numbers and cancel sent invites.');
      display_perm('users_edit_watch_hours', 'Can edit contrib watch hours.');
      display_perm('users_edit_reset_keys', 'Can reset any passkey/authkey.');
      display_perm('users_edit_profiles', 'Can edit anyone\'s profile.');
      display_perm('users_edit_badges', 'Can edit anyone\'s badges.');
      display_perm('users_view_friends', 'Can view anyone\'s friends.');
      display_perm('users_reset_own_keys', 'Can reset own passkey/authkey.');
      display_perm('users_edit_password', 'Can change password.');
      display_perm('users_promote_below', 'Can promote users to below current level.');
      display_perm('users_promote_to', 'Can promote users up to current level.');
      display_perm('users_give_donor', 'Can give donor access.');
      display_perm('users_warn', 'Can warn users.');
      display_perm('users_disable_users', 'Can disable users.');
      display_perm('users_disable_posts', 'Can disable users\' posting privileges.');
      display_perm('users_disable_any', 'Can disable any users\' rights.');
      display_perm('users_delete_users', 'Can delete anyone\'s account');
      display_perm('users_view_invites', 'Can view who user has invited');
      display_perm('users_view_seedleech', 'Can view what a user is seeding or leeching');
      display_perm('users_view_uploaded', 'Can view a user\'s uploads, regardless of privacy level');
      display_perm('users_view_keys', 'Can view passkeys');
      display_perm('users_view_ips', 'Can view IP addresses');
      display_perm('users_view_email', 'Can view email addresses');
      display_perm('users_invite_notes', 'Can add a staff note when inviting someone.');
      display_perm('users_override_paranoia', 'Can override paranoia');
      display_perm('users_make_invisible', 'Can make users invisible');
      display_perm('users_logout', 'Can log users out');
      display_perm('users_mod', 'Can access basic moderator tools (Admin comment)');
  
      echo <<<HTML
              <strong class="important_text">
                Everything is only applicable to users with the same or lower class level
              </strong>
            </td>
          </tr>
        </table>
      </div>
      
      <div class="permission_container">
        <table>
          <tr class="colhead">
            <th>Torrents</th>
          </tr>
          
          <tr>
            <td>
  HTML;
  
      display_perm('torrents_edit', 'Can edit any torrent');
      display_perm('torrents_delete', 'Can delete torrents');
      display_perm('torrents_delete_fast', 'Can delete more than 3 torrents at a time.');
      display_perm('torrents_freeleech', 'Can make torrents freeleech');
      display_perm('torrents_search_fast', 'Unlimit search frequency (for scripts).');
      display_perm('torrents_add_artist', 'Can add artists to any group.');
      display_perm('edit_unknowns', 'Can edit unknown release information.');
      display_perm('torrents_edit_vanityhouse', 'Can mark groups as part of Vanity House.');
      display_perm('artist_edit_vanityhouse', 'Can mark artists as part of Vanity House.');
      display_perm('torrents_fix_ghosts', 'Can fix ghost groups on artist pages.');
      display_perm('screenshots_add', 'Can add screenshots to any torrent and delete their own screenshots.');
      display_perm('screenshots_delete', 'Can delete any screenshot from any torrent.');
  
      echo <<<HTML
            </td>
          </tr>
        </table>
      </div>
      
      <div class="permission_container">
        <table>
          <tr class="colhead">
            <th>Administrative</th>
          </tr>
          
          <tr>
            <td>
  HTML;
  
      display_perm('admin_manage_news', 'Can manage site news');
      display_perm('admin_manage_blog', 'Can manage the site blog');
      display_perm('admin_manage_polls', 'Can manage polls');
      display_perm('admin_manage_forums', 'Can manage forums (add/edit/delete)');
      display_perm('admin_manage_fls', 'Can manage FLS');
      display_perm('admin_reports', 'Can access reports system');
      display_perm('admin_advanced_user_search', 'Can access advanced user search');
      display_perm('admin_create_users', 'Can create users through an administrative form');
      display_perm('admin_donor_log', 'Can view the donor log');
      display_perm('admin_manage_ipbans', 'Can manage IP bans');
      display_perm('admin_clear_cache', 'Can clear cached pages');
      display_perm('admin_whitelist', 'Can manage the list of allowed clients.');
      display_perm('admin_manage_permissions', 'Can edit permission classes/user permissions.');
      display_perm('admin_schedule', 'Can run the site schedule.');
      display_perm('admin_login_watch', 'Can manage login watch.');
      display_perm('admin_manage_wiki', 'Can manage wiki access.');
  
      echo <<<HTML
            </td>
          </tr>
        </table>
      </div>
      
      <div class="submit_container">
        <input type="submit" name="submit" class ="button-primary" value="Save Permission Class" />
      </div>
  HTML;
  }

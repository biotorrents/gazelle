<?php

declare(strict_types=1);


/*********************
 * bootstrap/app.php *
 *********************/

/**
 * enforce_login
 */
function enforce_login()
{
    return true;

    /*
    $app = \Gazelle\App::go();

    if (empty($app->user->core)) {
        Http::createCookie(['redirect' => $_SERVER['REQUEST_URI']]);
        #logout();
    }
    */
}


/**
 * authorize
 *
 * Make sure $_GET['auth'] is the same as the user's authorization key.
 * Should be used for any user action that relies solely on GET.
 *
 * @param Are we using ajax?
 * @return authorisation status. Prints an error message to DEBUG_CHAN on IRC on failure.
 */
function authorize($Ajax = false)
{
    return true;

    /*
    $app = \Gazelle\App::go();

    # Ugly workaround for API tokens
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) && $Document === 'api') {
        return true;
    } else {
        if (empty($_REQUEST['auth']) || $_REQUEST['auth'] !== $app->user->extra['AuthKey']) {
            send_irc(DEBUG_CHAN, $app->user->core['username']." just failed authorize on ".$_SERVER['REQUEST_URI'].(!empty($_SERVER['HTTP_REFERER']) ? " coming from ".$_SERVER['HTTP_REFERER'] : ""));
            error('Invalid authorization key. Go back, refresh, and try again.', $NoHTML = true);
            return false;
        }
    }
    */
}


/************
 * util.php *
 ************/

/**
 * send_irc
 *
 * Send a message to an IRC bot listening on SOCKET_LISTEN_PORT
 *
 * @param string $Raw An IRC protocol snippet to send.
 */
function send_irc($Channels = null, $Message = '')
{
    $ENV = \Gazelle\ENV::go();

    // Check if IRC is enabled
    if (!$ENV->announceIrc || !$Channels) {
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
        \Gazelle\Text::esc($Message),
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
 * error
 */
function error(int|string $error = 400, $noHtmlUnused = false, $logUnused = false): void
{
    $app = \Gazelle\App::go();

    $app->error($error);
}


/**
 * check_perms
 */
function check_perms(string $permission, $unused = 0)
{
    $app = \Gazelle\App::go();

    return $app->user->can($permission);
}


/**
 * site_url
 */
function site_url()
{
    $app = \Gazelle\App::go();

    return "https://{$app->env->siteDomain}";
}


/******************************
 * classes/paranoia.class.php *
 ******************************/

/**
 * check_paranoia
 */

function check_paranoia($Property, $Paranoia = false, $UserClass = false, $UserID = false)
{
    return true;
}


/**************************
 * classes/time.class.php *
 **************************/

/**
 * time_ago
 */
function time_ago($TimeStamp)
{
    if (!$TimeStamp) {
        return false;
    }
    if (!is_numeric($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
        $TimeStamp = strtotime($TimeStamp);
    }
    return time() - $TimeStamp;
}


/**
 * time_diff
 */
function time_diff(int|string $time, $unusedLevels = 2, $unusedSpan = true, $unusedLowercase = false)
{
    return Carbon\Carbon::parse($time)->diffForHumans();
}


/*************************
 * sql utility functions *
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


/**
 * sqltime
 */
function sqltime($timestamp = null)
{
    return \Gazelle\App::sqlTime($timestamp);
}


/********************************
 * classes/permissions_form.php *
 ********************************/

/**
 * permissions_form
 */
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
      
        <input type="submit" name="submit" class ="button-primary" value="Save Permission Class">
  HTML;
}

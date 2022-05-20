<?php
declare(strict_types = 1);

/**
 * Session
 *
 * All session handling stuff.
 * Logins, logouts, cookies, etc.
 */

class Session
{
    /**
     * enforceLogin
     */
    public function enforce_login()
    {
        $app = App::go();

        $cookie = Http::query("cookie");

        $SessionID = $_COOKIE["session"];
        $user["ID"] = (int) $_COOKIE["userid"];
    
    
        if (!$cookie["session"] || !$app->$user) {
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
    public function authorize($Ajax = false)
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
} # class

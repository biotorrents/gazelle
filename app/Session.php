<?php

declare(strict_types=1);

/**
 * Session
 *
 * PROLLY NOT NEEDED!
 *
 * All session handling stuff.
 * Logins, logouts, cookies, etc.
 */

class Session
{
    # basic storage
    public $id = null;
    public $userId = null;

    # login atempts
    public $attempts = 0;


    /**
     * __construct
     */
    public function __construct(string $username = "")
    {
        # relies on cookies
        $cookie = Http::query("cookie");

        # attempt to read cookies
        $this->id = $cookie["session"] ?? null;
        $this->userId = $cookie["userid"] ?? null;

        # generate new session key
        if ($this->id === null) {
            # users_sessions.SessionID char(64)
            $this->id = Text::random(64);
        }

        # try to get a userId
        if ($this->userId === null && empty($username)) {
            throw new Exception("Please provide a username, e.g., new Session(\"foo\")");
        }

        session_id($this->id);
        session_start();
    }


    /**
     * enforceLogin
     */
    public function enforceLogin()
    {
        # sanitize request
        $server = Http::query("server");

        if (!$this->id || !$this->userId) {
            Http::setCookie(["redirect" => $server["REQUEST_URI"]]);
            $this->logoutAll();
        }
    }


    /**
     * authorize
     *
     * Make sure $_GET["auth"] is the same as the user's authorization key.
     * Should be used for any user action that relies solely on GET.
     *
     * @param Are we using the API?
     * @return bool
     */
    public function authorize($api = false): bool
    {
        $app = \Gazelle\App::go();

        $request = Http::query("request");
        $server = Http::query("server");

        # ugly workaround for API tokens
        if (!empty($server["HTTP_AUTHORIZATION"]) && $api === true) {
            return true;
        }

        # fail them
        if (empty($request["auth"]) || $request["auth"] !== $app->user->extra["AuthKey"]) {
            Announce::slack("{$app->user->core["username"]} just failed authorize on {$server["REQUEST_URI"]}", ["debug"]);
            error("Invalid authorization key. Go back, refresh, and try again.");

            return false;
        }

        # okay
        return true;
    }


    /**
     * logout
     *
     * Log out the current session.
     */
    /*
    public function logout()
    {
        $app = \Gazelle\App::go();

        Http::deleteCookie("session");
        Http::deleteCookie("userid");
        Http::deleteCookie("keeplogged");

        if ($this->id) {
            $app->dbOld->prepared_query("
                delete from users_sessions where UserID = {$this->userId} and SessionID = '{$this->id}'
            ");

            $app->cacheNew->delete("users_sessions_{$this->userId}");
        }

        $app->cacheNew->delete("user_info_{$this->userId}");
        $app->cacheNew->delete("user_stats_{$this->userId}");
        $app->cacheNew->delete("user_info_heavy_{$this->userId}");

        # send to login
        #Http::redirect("login");
    }
    */


    /**
     * logoutAll
     *
     * Log out all user sessions.
     * Prefer this to self::logout.
     */
    /*
    public function logoutAll()
    {
        $app = \Gazelle\App::go();

        $app->dbOld->prepared_query("
            delete from users_sessions where UserID = '{$this->userId}'
        ");

        $app->cacheNew->delete("users_sessions_{$this->userId}");
        $this->logout();
    }
    */


    /**
     * logAttempt
     *
     * Function to log a user's login attempt.
     */
    public function logAttempt()
    {
        $app = \Gazelle\App::go();

        $server = Http::query("server");

        $attempts = $this->attempts++;
        $app->cacheNew->set("login_attempts_{$server["REMOTE_ADDR"]}", [$attempts, ($attempts > 5)], 60 * 60 * $attempts);

        $allAttempts = $app->cacheNew->get("login_attempts") ?? [];
        $allAttempts[$server["REMOTE_ADDR"]] = time() + (60 * 60 * $attempts);

        foreach ($allAttempts as $ip => $time) {
            if ($time < time()) {
                unset($allAttempts[$ip]);
            }
        }

        $app->cacheNew->set("login_attempts", $allAttempts, 0);
    }
} # class

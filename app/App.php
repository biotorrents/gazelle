<?php

declare(strict_types=1);


/**
 * App
 *
 * The main app is now a singleton.
 * Holds the various globals and some methods.
 * Supersedes G::class and ENV::go().
 * Will eventually kill Misc::class.
 */

class App
{
    # singleton
    private static $instance = null;

    # env is special
    public $env = null;

    # the rest of the globals
    public $cacheNew = null; # new
    public $cacheOld = null; # old

    public $dbNew = null; # new
    public $dbOld = null; # old

    public $debug = null;
    public $twig = null;

    public $userOld = null; # old
    public $userNew = null; # new


    /**
     * __functions
     */
    public function __construct()
    {
        return;
    }

    public function __clone()
    {
        return trigger_error(
            "clone not allowed",
            E_USER_ERROR
        );
    }

    public function __wakeup()
    {
        return trigger_error(
            "wakeup not allowed",
            E_USER_ERROR
        );
    }


    /**
     * go
     */
    public static function go(array $options = [])
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->factory($options);
        }

        return self::$instance;
    }


    /**
     * factory
     *
     * These need to be in a specific order to load right,
     * i.e., user depends on debug and twig depends on user.
     */
    private function factory(array $options = [])
    {
        # env: FIRST
        $this->env = ENV::go();

        # cache
        #$this->cacheNew = CacheRedis::go(); # new
        $this->cacheOld = new Cache(); # old

        # database
        $this->dbNew = Database::go(); # new
        $this->dbOld = new DB(); # old

        # debug
        $this->debug = Debug::go();

        # user
        $this->userNew = Users::go(); # new
        $this->userOld =& $user; # old

        # twig: LAST
        $this->twig = Twig::go();
    }


    /** NON-SINGLETON METHODS */


    /**
     * gotcha
     *
     * Basic sanity checks, just in case.
     * You know, for <? and other such nonsense.
     */
    public static function gotcha()
    {
        return true;
    }


    /**
     * email
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     */
    public static function email(string $to, string $subject, string $body)
    {
        $app = self::go();

        # check if email is enabled
        if (!$app->env->enableSiteEmail) {
            return false;
        }

        # wrap to 70 characters for RFC compliance
        # https://www.php.net/manual/en/function.mail.php
        #$body = wordwrap($body, 70, "\r\n");

        $secret = Text::random();
        $headers = [
            "Content-Language" => "en-US",
            "Content-Transfer-Encoding" => "7bit",
            "Content-Type" => "text/plain; charset=UTF-8; format=flowed",
            "From" => "{$app->env->siteName} <gazelle@{$app->env->siteDomain}>",
            "MIME-Version" => "1.0",
            "Message-ID" => "<{$secret}@{$app->env->siteDomain}>",
        ];

        # send the email
        mail($to, $subject, $body, $headers);
    }


    /**
     * unlimit
     *
     * Beef the specs for a time.
     */
    public static function unlimit()
    {
        if (ob_get_status()) {
            ob_end_clean(); # clear output buffer
        }

        set_time_limit(3600); # one hour
        ini_set("memory_limit", "2G"); # all the shit hetzner memory
    }


    /**
     * recursiveGlob
     *
     * @see https://stackoverflow.com/a/12172557
     */
    public static function recursiveGlob($folder, $extension)
    {
        $globFiles = glob("{$folder}/*.{$extension}");
        $globFolders  = glob("{$folder}/*", GLOB_ONLYDIR);

        foreach ($globFolders as $folder) {
            self::recursiveGlob($folder, $extension);
        }

        foreach ($globFiles as $file) {
            require_once $file;
        }
    }


    /**
     * manifest
     *
     * Prints an app manifest.
     */
    public static function manifest()
    {
        $app = self::go();

        # https://developer.mozilla.org/en-US/docs/Web/Manifest
        $manifest = [
            "\$schema" => "https://json.schemastore.org/web-manifest-combined.json",
            "name" => $app->env->siteName,
            "short_name" => $app->env->siteName,
            "start_url" => "/",
            "display" => "standalone",
            "background_color" => "#ffffff",
            "theme_color" => "#0288d1",
            "description" => $app->env->siteDescription,
            "icons" => [
                [
                    "src" => "/images/logos/liquidrop-bookish-1k.png",
                    "sizes" => "1024x1024",
                    "type" => "image/png",
                ],
                [
                    "src" => "/images/logos/liquidrop-postmod-1k.png",
                    "sizes" => "1024x1024",
                    "type" => "image/png",
                ],
            ],
            /*
            "related_applications" => [
                [
                    "platform" => "play",
                    "url" => "https://play.google.com/store/apps/details?id=cheeaun.hackerweb",
                ],
            ],
            */
        ];

        # return array
        return $manifest;

        # return json
        #return json_encode($manifest, JSON_UNESCAPED_SLASHES);
    }


    /**
     * sqlTime
     */
    public static function sqlTime($timestamp = null): string
    {
        return date(
            "Y-m-d H:i:s",
            $timestamp ?? time()
        );
    }


    /**
     * ajaxPagination
     *
     * Used for pagination of peer/snatch/download lists on torrent details.
     * THIS SHOULD EITHER GO AWAY OR GO SOMEWHERE ELSE.
     */
    public static function ajaxPagination($action, $torrentId, $resultCount, $currentPage)
    {
        $pageCount = ceil($resultCount / 100);
        $pageLinks = [];

        for ($i = 1; $i <= $pageCount; $i++) {
            if ($i === $currentPage) {
                $pageLinks[] = $i;
            } else {
                $pageLinks[] = "<a href='#' onclick='{$action}({$torrentId}, {$i})'>{$i}</a>";
            }
        }

        return implode(" | ", $pageLinks);
    }
} # class

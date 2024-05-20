<?php

declare(strict_types=1);


/**
 * Announce
 *
 * Simple unified announce multi-client for site events.
 * The point is to have, e.g., Announce::slack("foo") in one place.
 * Better yet, Announce:all("foo") that hits IRC, RSS, Slack, and Twitter.
 */

class Announce
{
    # IRC bot config options
    private static $ircChannels = ["announce", "debug"];
    private static $ircAddress = "10.0.0.6";
    private static $ircPort = 51010;

    # RSS bot config options
    private static $foo = "foo";

    # slack bot config options
    private static $slackChannels = ["announce", "debug"];

    # twitter bot config options
    private static $bar = "bar";


    /**
     * all
     *
     * Blast all the channels with a message.
     * On second thought, this needs to take an array:
     *
     *   Announce::all([
     *     "irc" => "irc command",
     *     "twitter" => "tweet < 160 chars",
     *     # etc.
     *   ]);
     *
     * @param string $message Please no gay.pl stuff. :(
     */
    public static function all(string $message)
    {
        # escape it (shouldn't be UGC anyway)
        $message = \Gazelle\Text::esc($message);

        /*
        # send it places
        self::irc($message);
        self::rss($message);
        self::slack($message);
        self::twitter($message);
        */
    }


    /**
     * irc
     *
     * Send a message to an IRC bot listening on $ENV->SOCKET_LISTEN_PORT.
     *
     * @param string $message An IRC protocol snippet to send.
     * @param array $channels What channels you wanna blast.
     */
    public static function irc(string $message, array $channels = [])
    {
        $app = \Gazelle\App::go();

        # check if IRC is enabled
        if (!$app->env->announceIrc) {
            return false;
        }

        # set default channels
        if (empty($channels)) {
            $channels = self::$ircChannels;
        }

        # strip leading #channel hashes
        $strippedHashes = [];
        foreach ($channels as $channel) {
            array_push($strippedHashes, preg_replace("/^#/", "", $channel));
        }

        # specific to AB's kana bot
        # https://github.com/anniemaybytes/kana
        $command = implode("-", $strippedHashes)
            . "|%|"
            . html_entity_decode($message, ENT_QUOTES);

        # original input sanitization
        $command = str_replace(["\n", "\r"], "", $command);

        try {
            # send the raw echo
            $socket = fsockopen(self::$ircAddress, self::$ircPort);
            fwrite($socket, $command);
            fclose($socket);
        } catch (Throwable $e) {
            \Gazelle\Text::figlet("irc failure", "red");
            !d($e->getMessage());
        }
    }


    /**
     * rss
     *
     * Make an RSS feed entry.
     */
    public static function rss(string $message)
    {
        $app = \Gazelle\App::go();

        # check if RSS is enabled
        if (!$app->env->announceRss) {
            return false;
        }

        try {
            # todo
        } catch (Throwable $e) {
            \Gazelle\Text::figlet("rss failure", "red");
            !d($e->getMessage());
        }
    }


    /**
     * slack
     *
     * @see https://github.com/jolicode/slack-php-api/blob/main/docs/examples/posting-message.php
     */
    public static function slack(string $message, array $channels = [])
    {
        $app = \Gazelle\App::go();

        # check if slack is enabled
        if (!$app->env->announceSlack) {
            return false;
        }

        # set default channels
        if (empty($channels)) {
            $channels = self::$slackChannels;
        }

        # webhooks must remain private
        $webhooks = $app->env->private("slackWebhooks");
        foreach ($channels as $channel) {
            try {
                # set up
                $curl = curl_init($webhooks->$channel);
                $data = json_encode(["text" => $message], JSON_UNESCAPED_SLASHES);

                # options
                curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/vnd.api+json"]);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                # do it
                curl_exec($curl);
                curl_close($curl);
            } catch (TypeError $e) {
                \Gazelle\Text::figlet("slack failure", "red");
                !d($e->getMessage());
            }
        }
    }


    /**
     * twitter
     *
     * @see https://twitteroauth.com
     */
    public static function twitter(string $message)
    {
        $app = \Gazelle\App::go();

        # check if twitter is enabled
        if (!$app->env->enableTwitter) {
            return false;
        }

        try {
            $twitterCredentials = $app->env->private("twitterApi");

            $connection = new Abraham\TwitterOAuth\TwitterOAuth(
                $twitterCredentials->consumerKey,
                $twitterCredentials->consumerSecret,
                $twitterCredentials->accessToken,
                $twitterCredentials->accessTokenSecret
            );

            # set api version
            # $connection->setApiVersion("2");
            $content = $connection->get("account/verify_credentials");
            !d($content);
            $statues = $connection->post("statuses/update", ["status" => "hello world"]);
            !d($statues);
            return;

            # https://twitteroauth.com/redirect.php
            define("OAUTH_CALLBACK", "fuck");
            $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => OAUTH_CALLBACK));

            $_SESSION['oauth_token'] = $request_token['oauth_token'];
            $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

            $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

            return $statues = $connection->post("statuses/update", ["status" => "hello world"]);

            return $content = $connection->get("account/verify_credentials");
        } catch (Throwable $e) {
            \Gazelle\Text::figlet("twitter failure", "red");
            !d($e->getMessage());
        }
    }


    /**
     * mastodon
     */
    public static function mastodon(string $message)
    {
        $app = \Gazelle\App::go();

        # check if mastodon is enabled
        if (!$app->env->enableMastodon) {
            return false;
        }

        try {
            # mastodon api announce bot
            $mastodon = new Mastodon(
                "https://botsin.space",
                "your_access_token"
            );

            $mastodon->postStatus($message);
        } catch (Throwable $e) {
            \Gazelle\Text::figlet("mastodon failure", "red");
            !d($e->getMessage());
        }
    }
} # class

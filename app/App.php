<?php

declare(strict_types=1);


/**
 * Gazelle\App
 *
 * The main app is now a singleton.
 * Holds the various globals and some methods.
 * Supersedes G::class and ENV::go().
 * Will eventually kill Misc::class.
 */

namespace Gazelle;

class App
{
    # singleton
    private static $instance = null;

    # env is special
    public $env = null;

    # the rest of the globals
    public $cache = null;

    public $dbNew = null; # new
    public $dbOld = null; # old

    public $debug = null;
    public $twig = null;

    public $user = null;


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
        $this->env = \ENV::go();

        # cache
        $this->cache = \Gazelle\Cache::go();

        # database
        $this->dbNew = \Gazelle\Database::go(); # new
        $this->dbOld = new \DatabaseOld(); # old

        # debug
        $this->debug = \Debug::go();

        # user
        $this->user = \User::go();

        # twig: LAST
        $this->twig = \Twig::go();
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
     * @param bool $isHtml
     */
    public static function email(string $to, string $subject, string $body, bool $isHtml = false)
    {
        $app = self::go();

        # check if email is enabled
        if (!$app->env->enableSiteEmail) {
            return false;
        }

        # passing "true" enables exceptions
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            # debug on development
            $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
            if ($app->env->dev) {
                $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            }

            # server settings
            $mail->isSMTP();
            $mail->SMTPAuth = true;

            $mail->Host = $app->env->getPriv("emailHost");
            $mail->Port = $app->env->getPriv("emailPort");

            $mail->Username = $app->env->getPriv("emailUsername");
            $mail->Password = $app->env->getPriv("emailPassphrase");

            # determine starttls or smtps
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            if ($mail->Port === 465) {
                # please fix your smtpd configuration
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }

            # from address
            $mail->setFrom($app->env->getPriv("emailUsername"), $app->env->siteName);

            # recipient(s)
            $mail->addAddress($to);

            # potentially useful
            #$mail->addReplyTo('info@example.com', 'Information');
            #$mail->addCC('cc@example.com');
            #$mail->addBCC('bcc@example.com');

            # attachments
            #$mail->addAttachment('/var/tmp/file.tar.gz');
            #$mail->addAttachment('/tmp/image.jpg', 'new.jpg');

            # content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;

            # create a plaintext version if needed
            if ($isHtml) {
                $mail->AltBody = "todo: render the html as plaintext";
            }

            # send it
            $mail->send();
        } catch (\Throwable $e) {
            \Announce::slack($mail->ErrorInfo, ["debug"]);
        }
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

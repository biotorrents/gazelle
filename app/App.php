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

    public $dbNew = null;
    public $dbOld = null;

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
        if (!self::$instance) {
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
        $this->dbNew = \Gazelle\Database::go();
        $this->dbOld = new \DatabaseOld();

        # debug
        $this->debug = \Debug::go();

        # user
        $this->user = \User::go();

        # twig: LAST
        $this->twig = \Twig::go();
    }


    /** non-singleton methods */


    /**
     * gotcha
     *
     * Basic sanity checks, just in case.
     * You know, for <? and other such nonsense.
     */
    public function gotcha()
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
     *
     * @see https://github.com/PHPMailer/PHPMailer
     */
    public function email(string $to, string $subject, string $body, bool $isHtml = false)
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
                #$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            }

            # server settings
            $mail->isSMTP();
            $mail->SMTPAuth = true;

            $mail->Host = $app->env->private("emailHost");
            $mail->Port = $app->env->private("emailPort");

            $mail->Username = $app->env->private("emailUsername");
            $mail->Password = $app->env->private("emailPassphrase");

            # determine starttls or smtps
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            if ($mail->Port === 465) {
                # please fix your smtpd configuration
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }

            # from address
            $mail->setFrom($app->env->private("emailUsername"), $app->env->siteName);

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
                $mail->AltBody = strip_tags($body);
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
    public function unlimit()
    {
        # clear output buffer
        if (ob_get_status()) {
            ob_end_clean();
        }

        set_time_limit(0);
        ini_set("memory_limit", -1);

        gc_enable();
    }


    /**
     * recursiveGlob
     *
     * Recursively require all files in a folder.
     *
     * @param string $folder
     * @param string $extension
     * @return void
     *
     * @see https://stackoverflow.com/a/12172557
     */
    public function recursiveGlob(string $folder, string $extension = "php"): void
    {
        $globFiles = glob("{$folder}/*.{$extension}");
        $globFolders  = glob("{$folder}/*", GLOB_ONLYDIR);

        foreach ($globFolders as $folder) {
            $this->recursiveGlob($folder, $extension);
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
                    "src" => "/images/logos/colorfulWaves-whiteShadow-2k.webp",
                    "sizes" => "2048x2048",
                    "type" => "image/webp",
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


    /**
     * error
     *
     * Displays an HTTP status code with description and triggers an error.
     * If you use your own string for $error, it becomes the error description.
     *
     * @param int|string $error error type or message
     */
    public function error(int|string $error = 400): void
    {
        $app = \Gazelle\App::go();

        # https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
        $map = [
            400 => [
                "400 Bad Request",
                "The server cannot or will not process the request due to an apparent client error (e.g., malformed request syntax, size too large, invalid request message framing, or deceptive request routing).",
            ],

            403 => [
                "403 Forbidden",
                "The request contained valid data and was understood by the server, but the server is refusing action. This may be due to the user not having the necessary permissions for a resource or needing an account of some sort, or attempting a prohibited action (e.g. creating a duplicate record where only one is allowed). This code is also typically used if the request provided authentication by answering the WWW-Authenticate header field challenge, but the server did not accept that authentication. The request should not be repeated.",
            ],

            404 => [
                "404 Not Found",
                "The requested resource could not be found but may be available in the future. Subsequent requests by the client are permissible.",
            ],
        ];

        # page content
        if (array_key_exists($error, $map)) {
            $subject = $map[$error][0];
            $body = $map[$error][1];
        } else {
            $subject = "Other error";
            $body = "A function supplied this error message: {$error}";
        }

        # twig
        $app->twig->display("error.twig", [
            "title" => $subject,
            "subject" => $subject,
            "body" => $body
        ]);

        # end all execution
        exit;
    }
} # class

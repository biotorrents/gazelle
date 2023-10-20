<?php

declare(strict_types=1);


/**
 * Gazelle\App
 *
 * The main app is now a singleton.
 * Holds the various globals and some methods.
 * Supersedes G::class and Gazelle\ENV::go().
 * Will eventually kill Misc::class.
 */

namespace Gazelle;

class App
{
    # singleton
    private static $instance;

    # env is special
    public ENV $env;

    # the rest of the globals
    public Cache $cache;

    public Database $dbNew;
    public \DatabaseOld $dbOld;

    public \DebugBar\StandardDebugBar $debug;
    public \Twig\Environment $twig;

    public \User $user;


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
        $this->env = ENV::go();

        # cache
        $this->cache = Cache::go();

        # database
        $this->dbNew = Database::go();
        $this->dbOld = new \DatabaseOld();

        # debug
        $this->debug = \Debug::go();

        # user
        $this->user = \User::go();

        # twig: LAST
        $this->twig = Twig::go();
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
        # check if email is enabled
        if (!$this->env->enableSiteEmail) {
            return false;
        }

        # passing "true" enables exceptions
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            # debug on development
            $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
            if ($this->env->dev) {
                #$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            }

            # server settings
            $mail->isSMTP();
            $mail->SMTPAuth = true;

            $mail->Host = $this->env->private("emailHost");
            $mail->Port = $this->env->private("emailPort");

            $mail->Username = $this->env->private("emailUsername");
            $mail->Password = $this->env->private("emailPassphrase");

            # determine starttls or smtps
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            if ($mail->Port === 465) {
                # please fix your smtpd configuration
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }

            # from address
            $mail->setFrom($this->env->private("emailUsername"), $this->env->siteName);

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
    public function manifest()
    {
        # https://developer.mozilla.org/en-US/docs/Web/Manifest
        $manifest = [
            # schema
            "\$schema" => "https://json.schemastore.org/web-manifest-combined.json",

            # identity
            "name" => $this->env->siteName,
            "short_name" => $this->env->siteName,
            "description" => $this->env->siteDescription,
            "id" => $this->env->siteName,

            # presentation
            "start_url" => ".",
            "theme_color" => "#0288d1",
            "background_color" => "#ffffff",
            "orientation" => "landscape-primary",
            "display" => "standalone",

            # icons
            "icons" => [
                [
                    "src" => "/images/logos/colorfulWaves-whiteShadow-2k.webp",
                    "sizes" => "2048x2048",
                    "type" => "image/webp",
                ],
                [
                    "src" => "/images/logos/simpleFavicon-2k.webp",
                    "sizes" => "2048x2048",
                    "type" => "image/webp",
                ],
            ],

            # window controls overlay
            "display_override" => ["window-controls-overlay"],
        ];

        # return json
        return json_encode($manifest, JSON_UNESCAPED_SLASHES);
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
        $this->twig->display("error.twig", [
            "title" => $subject,
            "subject" => $subject,
            "body" => $body
        ]);

        # end all execution
        exit;
    }
} # class

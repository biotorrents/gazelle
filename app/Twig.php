<?php

declare(strict_types=1);


/**
 * Twig
 *
 * Converted to a singleton class.
 * One instance should only ever exist,
 * because of its separate disk cache.
 *
 * Based on OPS's useful rule set:
 * https://github.com/OPSnet/Gazelle/blob/master/app/Util/Twig.php
 */

class Twig # extends Twig\Environment
{
    # singleton
    private static $instance = null;

    # twig instance
    private $twig = null;


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
        return (!self::$instance)
            ? self::$instance = self::factory($options)
            : self::$instance;

        /*
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->factory($options);
        }

        return self::$instance;
        */
    }


    /**
     * factory
     */
    private static function factory(array $options = []): Twig\Environment
    {
        $app = \Gazelle\App::go();

        # https://twig.symfony.com/doc/3.x/api.html
        $twig = new Twig\Environment(
            new Twig\Loader\FilesystemLoader("{$app->env->serverRoot}/templates"),
            [
                "auto_reload" => true,
                "autoescape" => "name",
                # don't cache in the dev environment
                "cache" => (!$app->env->dev) ? "{$app->env->webRoot}/cache" : false,
                "debug" => $app->env->dev,
                "strict_variables" => true,
            ]
        );

        # debug stuff
        if ($app->env->dev) {
            $twig->addExtension(new Twig\Extension\DebugExtension());

            # last commit banner
            $twig->addGlobal(
                "git",
                json_decode(
                    file_get_contents("{$app->env->webRoot}/gitInfo.json"),
                    true
                )
            );
        }

        # globals: app and env
        $twig->addGlobal("app", $app);
        $twig->addGlobal("env", $app->env::$public);

        # user and authenticated
        $twig->addGlobal("user", $app->user);
        $twig->addGlobal("authenticated", $app->user->isLoggedIn());

        # site options
        $twig->addGlobal("siteOptions", $app->user->siteOptions ?? []);

        # todo: put these elsewhere later
        $twig->addGlobal("inbox", Inbox::get_inbox_link());
        $twig->addGlobal("notify", check_perms("site_torrents_notify"));

        /** */

        # body styles
        $bodyStyles = [];
        if (!empty($app->user->extra)) {
            $bodyStyles = [
                ($app->env->dev) ? "development" : null,
                ($app->user->siteOptions["font"]) ?? null,
                ($app->user->siteOptions["calmMode"]) ? "calmMode" : null,
                ($app->user->siteOptions["darkMode"]) ? "darkMode" : null,
            ];
        } else {
            $bodyStyles = ["notoSans"];
        }

        $bodyStyles = implode(" ", array_filter($bodyStyles));
        $twig->addGlobal("bodyStyles", $bodyStyles);

        # session internal api key
        $frontendKey = implode(".", [
            Http::readCookie("sessionId"),
            $app->env->getPriv("siteApiSecret"),
        ]);

        $frontendHash = password_hash($frontendKey, PASSWORD_DEFAULT);
        $twig->addGlobal("frontendHash", $frontendHash);

        # query
        $query = Http::request();
        $twig->addGlobal("query", $query);
        #!d($twig->getGlobals());exit;

        # https://github.com/paragonie/anti-csrf
        $twig->addFunction(new Twig\TwigFunction(
            "form_token",
            function ($lock_to = null) {
                static $csrf;

                if ($csrf === null) {
                    try {
                        $csrf = new ParagonIE\AntiCSRF\AntiCSRF();
                    } catch (Throwable $e) {
                        return;
                    }
                }

                return $csrf->insertToken($lock_to, false);
            },
            [ "is_safe" => ["html"] ]
        ));

        /*
        # DebugBar
        $profile = new Twig\Profiler\Profile();
        $debug = Debug::go();
        $twig->addExtension(
            new DebugBar\Bridge\Twig\TimeableTwigExtensionProfiler(
                $profile,
                $debug["time"]
            )
        );
        */

        # DebugBar: header
        $twig->addFunction(new Twig\TwigFunction("debugHeader", function () {
            $app = \Gazelle\App::go();
            $render = $app->debug->getJavascriptRenderer();

            return new Twig\Markup(
                $render->renderHead(),
                "UTF-8"
            );
        }));

        # DebugBar: footer
        $twig->addFunction(new Twig\TwigFunction("debugFooter", function () {
            $app = \Gazelle\App::go();
            $render = $app->debug->getJavascriptRenderer();

            return new Twig\Markup(
                $render->render(),
                "UTF-8"
            );
        }));

        # can
        $twig->addFunction(new Twig\TwigFunction("can", function ($permission) {
            $app = \Gazelle\App::go();

            return $app->user->can($permission);
        }));

        # cant
        $twig->addFunction(new Twig\TwigFunction("cant", function ($permission) {
            $app = \Gazelle\App::go();

            return $app->user->cant($permission);
        }));

        # Gazelle\Images::process
        $twig->addFunction(new Twig\TwigFunction("processImage", function ($uri, $thumbnail) {
            return new Twig\Markup(
                \Gazelle\Images::process($uri, $thumbnail),
                "UTF-8"
            );
        }));

        # Torrents::can_use_token
        $twig->addFunction(new Twig\TwigFunction("canUseToken", function ($torrentId) {
            return new Twig\Markup(
                Torrents::can_use_token($torrentId),
                "UTF-8"
            );
        }));

        # Format::pretty_category
        $twig->addFilter(new Twig\TwigFilter("categoryIcon", function ($categoryId) {
            $markup = "<div title='" . Format::pretty_category($categoryId) . "' class='" . Format::css_category($categoryId) . "' />";
            return new Twig\Markup(
                $markup,
                "UTF-8"
            );
        }));

        # Gazelle\Text::parse
        $twig->addFilter(new Twig\TwigFilter("parse", function ($string) {
            return new Twig\Markup(
                \Gazelle\Text::parse($string),
                "UTF-8"
            );
        }));

        # https://philfrilling.com/blog/2017-01/php-convert-seconds-hhmmss-format
        $twig->addFilter(new Twig\TwigFilter("hhmmss", function ($seconds) {
            return sprintf(
                "%02dm %02ds", # mm:ss
                #"%02d:%02d:%02d", # hh:mm:ss
                #($seconds / 3600), # hh
                (intval($seconds / 60) % 60), # mm
                ($seconds % 60) # ss
            );
        }));

        # Format::relativeTime
        $twig->addFilter(new Twig\TwigFilter("relativeTime", function ($time) {
            return Format::relativeTime($time);
        }));

        # curlyBraces (for biblatex)
        $twig->addFilter(new Twig\TwigFilter("curlyBraces", function ($string) {
            return new Twig\Markup(
                "{{$string}}",
                "UTF-8"
            );
        }));

        # Badges::displayBadge
        $twig->addFunction(new Twig\TwigFunction("displayBadge", function ($badgeId) {
            return new Twig\Markup(
                Badges::displayBadge($badgeId),
                "UTF-8"
            );
        }));

        # Artists::display_artists
        $twig->addFunction(new Twig\TwigFunction("displayCreators", function ($creators) {
            return new Twig\Markup(
                Artists::display_artists($creators),
                "UTF-8"
            );
        }));

        # Artists::display_artist
        $twig->addFunction(new Twig\TwigFunction("displayCreator", function ($creator) {
            return new Twig\Markup(
                Artists::display_artist($creator),
                "UTF-8"
            );
        }));

        # displayTags
        $twig->addFunction(new Twig\TwigFunction("displayTags", function ($tagList) {
            $tags = new Tags($tagList);

            return new Twig\Markup(
                $tags->format(""),
                "UTF-8"
            );
        }));

        # displayTagsFromArray
        # this is extremely stupid
        $twig->addFunction(new Twig\TwigFunction("displayTagsFromArray", function ($tagList) {
            $tagList = implode(" ", $tagList);
            $tags = new Tags($tagList);

            return new Twig\Markup(
                $tags->format(""),
                "UTF-8"
            );
        }));

        # Format::breadcrumbs
        $twig->addFunction(new Twig\TwigFunction("breadcrumbs", function () {
            return Format::breadcrumbs();
        }));

        # Format::get_size
        $twig->addFilter(new Twig\TwigFilter("get_size", function ($size, $levels = 2) {
            return Format::get_size($size, $levels);
        }));

        # \Gazelle\Text::float
        $twig->addFilter(new Twig\TwigFilter("float", function ($number, $decimals = 2) {
            return \Gazelle\Text::float($number, $decimals);
        }));

        # Illuminate\Support\Str::camel
        $twig->addFilter(new Twig\TwigFilter("camel", function ($string) {
            $string = Illuminate\Support\Str::camel($string);
            $string = preg_replace("/[\W]/", '', $string);

            return $string;
        }));

        # User::displayAvatar
        $twig->addFunction(new Twig\TwigFunction("displayAvatar", function ($uri, $username) {
            return new Twig\Markup(
                User::displayAvatar($uri, $username),
                "UTF-8"
            );
        }));

        # random creator
        $twig->addFunction(new Twig\TwigFunction("randomCreator", function () {
            $randomCreators = [
                "Alexander Fleming",
                "Alfonso Valencia",
                "Alfred Russel Wallace",
                "Alister Hardy",
                "Andreas Vesalius",
                "Antoine Lavoisier",
                "Antonie van Leeuwenhoek",
                "Aristotle",
                "Barbara McClintock",
                "Carl Linnaeus",
                "Carl Woese",
                "Charles Darwin",
                "Charles Nicolle",
                "Craig Venter",
                "David Baltimore",
                "E. O. Wilson",
                "Emmanuelle Charpentier",
                "Eric S. Lander",
                "Francesco Redi",
                "Francis Crick",
                "Galen",
                "George Wald",
                "George Washington Carver",
                "Gregor Mendel",
                "Hamilton O. Smith",
                "He Jiankui",
                "James Watson",
                "Jennifer Doudna",
                "Jerry A. Coyne",
                "Karl Landsteiner",
                "Linda B. Buck",
                "Louis Pasteur",
                "Marcus W. Feldman",
                "Masatoshi Nei",
                "Maurice Hilleman",
                "Mónica Bettencourt-Dias",
                "Nettie Stevens",
                "Oswald Avery",
                "Rachel Carson",
                "Richard Dawkins",
                "Richard Lewontin",
                "Robert Hooke",
                "Ronald Fisher",
                "Rosalind Franklin",
                "Selman Waksman",
                "Sergei Winogradsky",
                "Stephen Jay Gould",
                "Stuart Kauffman",
                "Susumu Tonegawa",
                "Theodor Schwann",
                "William Harvey",
            ];

            $randomKey = array_rand($randomCreators);

            return new Twig\Markup(
                $randomCreators[$randomKey],
                "UTF-8"
            );
        }));

        # random tag
        $twig->addFunction(new Twig\TwigFunction("randomTag", function () {
            $app = \Gazelle\App::go();

            $query = "select name from tags where tagType = ? order by rand() limit 1";
            $randomTag = $app->dbNew->single($query, ["genre"]);

            return new Twig\Markup(
                $randomTag,
                "UTF-8"
            );
        }));


        /**
         * OPS
         */

        $twig->addFilter(new Twig\TwigFilter(
            "article",
            function ($word) {
                return preg_match("/^[aeiou]/i", $word) ? "an" : "a";
            }
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "b64",
            function (string $binary) {
                return base64_encode($binary);
            }
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "image",
            function ($i) {
                return new Twig\Markup(\Gazelle\Images::process($i, true), "UTF-8");
            }
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "octet_size",
            function ($size, array $option = []) {
                return Format::get_size($size, empty($option)
                    ? 2
                    : $option[0]);
            },
            ["is_variadic" => true]
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "time_diff",
            function ($time) {
                return new Twig\Markup(time_diff($time), "UTF-8");
            }
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "ucfirst",
            function ($text) {
                return ucfirst($text);
            }
        ));

        # Format::get_ratio_html
        $twig->addFunction(new Twig\TwigFunction("ratio", function ($up, $down) {
            return new Twig\Markup(
                Format::get_ratio_html($up, $down),
                "UTF-8"
            );
        }));

        return $twig;
    }
} # class

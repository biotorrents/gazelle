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
        return (self::$instance === null)
        ? self::$instance = self::factory($options)
        : self::$instance;

        /*
        if (self::$instance === null) {
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
        $app = App::go();

        # https://twig.symfony.com/doc/3.x/api.html
        $twig = new Twig\Environment(
            new Twig\Loader\FilesystemLoader("{$app->env->SERVER_ROOT}/templates"),
            [
                "auto_reload" => true,
                "autoescape" => "name",
                "cache" => "{$app->env->WEB_ROOT}/cache/twig",
                "debug" => $app->env->DEV,
                "strict_variables" => true,
            ]
        );

        # globals
        $twig->addGlobal("env", $app->env);
        $twig->addGlobal("user", $app->user);
        #!d($twig->getGlobals());exit;

        # https://github.com/paragonie/anti-csrf
        $twig->addFunction(
            new Twig\TwigFunction(
                'form_token',
                function ($lock_to = null) {
                    static $csrf;
                    if ($csrf === null) {
                        $csrf = new ParagonIE\AntiCSRF\AntiCSRF;
                    }
                    return $csrf->insertToken($lock_to, false);
                },
                ['is_safe' => ['html']]
            )
        );


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

        # https://philfrilling.com/blog/2017-01/php-convert-seconds-hhmmss-format
        $twig->addFilter(new Twig\TwigFilter(
            "hhmmss",
            function ($seconds) {
                return sprintf(
                    "%02dm %02ds", # mm:ss
                    #"%02d:%02d:%02d", # hh:mm:ss
                    #($seconds / 3600), # hh
                    (intval($seconds / 60) % 60), # mm
                    ($seconds % 60) # ss
                );
            }
        ));

        # Format::get_size
        $twig->addFilter(new Twig\TwigFilter(
            "get_size",
            function ($size, $levels = 2) {
                return Format::get_size($size, $levels);
            }
        ));

        # Format::get_ratio_html
        $twig->addFunction(new Twig\TwigFunction(
            "get_ratio_html",
            function ($dividend, $divisor, $color = true) {
                return Format::get_ratio_html($dividend, $divisor, $color);
            }
        ));

        # Text::float
        $twig->addFilter(new Twig\TwigFilter(
            "float",
            function ($number, $decimals = 2) {
                return Text::float($number, $decimals);
            }
        ));
        


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
            "bb_format",
            function ($text) {
                return new Twig\Markup(Text::parse($text), "UTF-8");
            }
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "checked",
            function ($isChecked) {
                return $isChecked ? " checked=\"checked\"" : "";
            }
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "image",
            function ($i) {
                return new Twig\Markup(ImageTools::process($i, true), "UTF-8");
            }
        ));

        /*
        $twig->addFilter(new Twig\TwigFilter(
            "ipaddr",
            function ($ipaddr) {
                return new Twig\Markup(Tools::display_ip($ipaddr), "UTF-8");
            }
        ));
        */

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
            "plural",
            function ($number) {
                return plural($number);
            }
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "selected",
            function ($isSelected) {
                return $isSelected
                    ? " selected=\"selected\""
                    : "";
            }
        ));

        /*
        $twig->addFilter(new Twig\TwigFilter(
            "shorten",
            function (string $text, int $length) {
                return shortenString($text, $length);
            }
        ));
        */

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

        $twig->addFilter(new Twig\TwigFilter(
            "ucfirstall",
            function ($text) {
                return implode(" ", array_map(function ($w) {
                    return ucfirst($w);
                }, explode(" ", $text)));
            }
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "user_url",
            function ($userId) {
                return new Twig\Markup(Users::format_username($userId, false, false, false), "UTF-8");
            }
        ));

        $twig->addFilter(new Twig\TwigFilter(
            "user_full",
            function ($userId) {
                return new Twig\Markup(Users::format_username($userId, true, true, true, true), "UTF-8");
            }
        ));

        $twig->addFunction(new Twig\TwigFunction("donor_icon", function ($icon, $userId) {
            return new Twig\Markup(
                ImageTools::process($icon, false, "donoricon", $userId),
                "UTF-8"
            );
        }));

        $twig->addFunction(new Twig\TwigFunction("ratio", function ($up, $down) {
            return new Twig\Markup(
                Format::get_ratio_html($up, $down),
                "UTF-8"
            );
        }));

        /*
        $twig->addFunction(new Twig\TwigFunction("shorten", function ($text, $length) {
            return new Twig\Markup(
                shortenString($text, $length),
                "UTF-8"
            );
        }));
        */

        $twig->addTest(
            new Twig\TwigTest("numeric", function ($value) {
                return is_numeric($value);
            })
        );

        return $twig;
    }


    /**
     * render
     *
     * Returns a Twig render
     * (e.g., for a variable).
     */
    public function render(string $template, array $vars = [])
    {
        $twig = self::$instance ?? self::go();
        return $twig->render($template, $vars);
    }


    /**
     * print
     *
     * Prints a Twig render
     * (e.g., for a page).
     */
    public function print(string $template, array $vars = [])
    {
        $twig = self::$instance ?? self::go();
        echo $twig->render($template, $vars);
    }
} # class

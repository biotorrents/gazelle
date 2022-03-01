<?php
declare(strict_types=1);

/**
 * Twig class
 *
 * Converted to a singleton class.
 * One instance should only ever exist,
 * because of its separate disk cache.
 *
 * Based on OPS's useful rule set:
 * https://github.com/OPSnet/Gazelle/blob/master/app/Util/Twig.php
 */

class Twig
{
    # Singleton
    private static $twig = null;

    /**
     * __functions
     */
    private function __construct()
    {
        return;
    }

    private function __clone()
    {
        return trigger_error(
            'clone not allowed',
            E_USER_ERROR
        );
    }

    public function __wakeup()
    {
        return trigger_error(
            'wakeup not allowed',
            E_USER_ERROR
        );
    }


    /**
     * go
     */
    public static function go()
    {
        return (self::$twig === null)
            ? self::$twig = Twig::factory()
            : self::$twig;
    }


    /**
     * factory
     */
    private static function factory(): \Twig\Environment
    {
        $ENV = ENV::go();

        # https://twig.symfony.com/doc/3.x/api.html
        $twig = new \Twig\Environment(
            new \Twig\Loader\FilesystemLoader("{$ENV->SERVER_ROOT}/templates"),
            [
                'auto_reload' => true,
                'autoescape' => 'name',
                'cache' => "{$ENV->WEB_ROOT}/cache/twig",
                'debug' => $ENV->DEV,
                'strict_variables' => true,
            ]
        );

        /*
        # DebugBar
        $Profile = new \Twig\Profiler\Profile();
        $debug = Debug::go();
        $twig->addExtension(
            new \DebugBar\Bridge\Twig\TimeableTwigExtensionProfiler(
                $Profile,
                $debug['time']
            )
        );
        */

        # https://philfrilling.com/blog/2017-01/php-convert-seconds-hhmmss-format
        $twig->addFilter(new \Twig\TwigFilter(
            'hhmmss',
            function ($seconds) {
                return sprintf(
                    '%02dm %02ds', # mm:ss
                    #'%02d:%02d:%02d', # hh:mm:ss
                    #($seconds / 3600), # hh
                    (intval($seconds / 60) % 60), # mm
                    ($seconds % 60) # ss
                );
            }
        ));

        # Format::get_size
        $twig->addFilter(new \Twig\TwigFilter(
            'get_size',
            function ($size, $levels = 2) {
                return Format::get_size($size, $levels);
            }
        ));

        # Format::get_ratio_html
        $twig->addFunction(new \Twig\TwigFunction(
            'get_ratio_html',
            function ($dividend, $divisor, $color = true) {
                return Format::get_ratio_html($dividend, $divisor, $color);
            }
        ));


        /**
         * OPS
         */
        $twig->addFilter(new \Twig\TwigFilter(
            'article',
            function ($word) {
                return preg_match('/^[aeiou]/i', $word) ? 'an' : 'a';
            }
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'b64',
            function (string $binary) {
                return base64_encode($binary);
            }
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'bb_format',
            function ($text) {
                return new \Twig\Markup(\Text::parse($text), 'UTF-8');
            }
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'checked',
            function ($isChecked) {
                return $isChecked ? ' checked="checked"' : '';
            }
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'image',
            function ($i) {
                return new \Twig\Markup(ImageTools::process($i, true), 'UTF-8');
            }
        ));

        /*
        $twig->addFilter(new \Twig\TwigFilter(
            'ipaddr',
            function ($ipaddr) {
                return new \Twig\Markup(\Tools::display_ip($ipaddr), 'UTF-8');
            }
        ));
        */

        $twig->addFilter(new \Twig\TwigFilter(
            'octet_size',
            function ($size, array $option = []) {
                return Format::get_size($size, empty($option)
                    ? 2
                    : $option[0]);
            },
            ['is_variadic' => true]
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'plural',
            function ($number) {
                return plural($number);
            }
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'selected',
            function ($isSelected) {
                return $isSelected
                    ? ' selected="selected"'
                    : '';
            }
        ));

        /*
        $twig->addFilter(new \Twig\TwigFilter(
            'shorten',
            function (string $text, int $length) {
                return shortenString($text, $length);
            }
        ));
        */

        $twig->addFilter(new \Twig\TwigFilter(
            'time_diff',
            function ($time) {
                return new \Twig\Markup(time_diff($time), 'UTF-8');
            }
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'ucfirst',
            function ($text) {
                return ucfirst($text);
            }
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'ucfirstall',
            function ($text) {
                return implode(' ', array_map(function ($w) {
                    return ucfirst($w);
                }, explode(' ', $text)));
            }
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'user_url',
            function ($userId) {
                return new \Twig\Markup(Users::format_username($userId, false, false, false), 'UTF-8');
            }
        ));

        $twig->addFilter(new \Twig\TwigFilter(
            'user_full',
            function ($userId) {
                return new \Twig\Markup(Users::format_username($userId, true, true, true, true), 'UTF-8');
            }
        ));

        $twig->addFunction(new \Twig\TwigFunction('donor_icon', function ($icon, $userId) {
            return new \Twig\Markup(
                ImageTools::process($icon, false, 'donoricon', $userId),
                'UTF-8'
            );
        }));

        $twig->addFunction(new \Twig\TwigFunction('ratio', function ($up, $down) {
            return new \Twig\Markup(
                Format::get_ratio_html($up, $down),
                'UTF-8'
            );
        }));

        /*
        $twig->addFunction(new \Twig\TwigFunction('shorten', function ($text, $length) {
            return new \Twig\Markup(
                shortenString($text, $length),
                'UTF-8'
            );
        }));
        */

        $twig->addTest(
            new \Twig\TwigTest('numeric', function ($value) {
                return is_numeric($value);
            })
        );

        return $twig;
    }
}

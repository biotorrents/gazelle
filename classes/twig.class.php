<?php
declare(strict_types=1);

/**
 * Twig class
 *
 * Converted to a singleton class like $ENV.
 * One instance should only ever exist,
 * because of its separate disk cache system.
 *
 * Based on OPS's useful rule set:
 * https://github.com/OPSnet/Gazelle/blob/master/app/Util/Twig.php
 */

class Twig
{
    /**
     * __functions
     */

    private static $Twig = null;

    private function __construct()
    {
        return;
    }

    public function __clone()
    {
        return trigger_error(
            'clone() not allowed',
            E_USER_ERROR
        );
    }

    public function __wakeup()
    {
        return trigger_error(
            'wakeup() not allowed',
            E_USER_ERROR
        );
    }


    /**
     * go
     */
    public static function go()
    {
        return (self::$Twig === null)
            ? self::$Twig = Twig::factory()
            : self::$Twig;
    }


    /**
     * factory
     */

    private static function factory(): \Twig\Environment
    {
        $ENV = ENV::go();

        # https://twig.symfony.com/doc/3.x/api.html
        $Twig = new \Twig\Environment(
            new \Twig\Loader\FilesystemLoader("$ENV->SERVER_ROOT/templates"),
            [
                'auto_reload' => true,
                'autoescape' => 'name',
                'cache' => "$ENV->WEB_ROOT/cache/twig",
                'debug' => $ENV->DEV,
                'strict_variables' => true,
        ]
        );

        $Twig->addFilter(new \Twig\TwigFilter(
            'article',
            function ($word) {
                return preg_match('/^[aeiou]/i', $word) ? 'an' : 'a';
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'b64',
            function (string $binary) {
                return base64_encode($binary);
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'bb_format',
            function ($text) {
                return new \Twig\Markup(\Text::full_format($text), 'UTF-8');
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'checked',
            function ($isChecked) {
                return $isChecked ? ' checked="checked"' : '';
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'image',
            function ($i) {
                return new \Twig\Markup(\ImageTools::process($i, true), 'UTF-8');
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'ipaddr',
            function ($ipaddr) {
                return new \Twig\Markup(\Tools::display_ip($ipaddr), 'UTF-8');
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'octet_size',
            function ($size, array $option = []) {
                return \Format::get_size($size, empty($option) ? 2 : $option[0]);
            },
            ['is_variadic' => true]
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'plural',
            function ($number) {
                return plural($number);
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'selected',
            function ($isSelected) {
                return $isSelected ? ' selected="selected"' : '';
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'shorten',
            function (string $text, int $length) {
                return shortenString($text, $length);
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'time_diff',
            function ($time) {
                return new \Twig\Markup(time_diff($time), 'UTF-8');
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'ucfirst',
            function ($text) {
                return ucfirst($text);
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'ucfirstall',
            function ($text) {
                return implode(' ', array_map(function ($w) {
                    return ucfirst($w);
                }, explode(' ', $text)));
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'user_url',
            function ($userId) {
                return new \Twig\Markup(\Users::format_username($userId, false, false, false), 'UTF-8');
            }
        ));

        $Twig->addFilter(new \Twig\TwigFilter(
            'user_full',
            function ($userId) {
                return new \Twig\Markup(\Users::format_username($userId, true, true, true, true), 'UTF-8');
            }
        ));

        $Twig->addFunction(new \Twig\TwigFunction('donor_icon', function ($icon, $userId) {
            return new \Twig\Markup(
                \ImageTools::process($icon, false, 'donoricon', $userId),
                'UTF-8'
            );
        }));

        $Twig->addFunction(new \Twig\TwigFunction('privilege', function ($default, $config, $key) {
            return new \Twig\Markup(
                (
                    $default
                    ? sprintf(
                        '<input id="%s" type="checkbox" disabled="disabled"%s />&nbsp;',
                        "default_$key",
                        (isset($default[$key]) && $default[$key] ? ' checked="checked"' : '')
                    )
                    : ''
                )
                . sprintf(
                    '<input type="checkbox" name="%s" id="%s" value="1"%s />&nbsp;<label title="%s" for="%s">%s</label><br />',
                    "perm_$key",
                    $key,
                    (empty($config[$key]) ? '' : ' checked="checked"'),
                    $key,
                    $key,
                    \Permissions::list()[$key] ?? "!unknown($key)!"
                ),
                'UTF-8'
            );
        }));

        $Twig->addFunction(new \Twig\TwigFunction('ratio', function ($up, $down) {
            return new \Twig\Markup(
                \Format::get_ratio_html($up, $down),
                'UTF-8'
            );
        }));

        $Twig->addFunction(new \Twig\TwigFunction('resolveCountryIpv4', function ($addr) {
            return new \Twig\Markup(
                (function ($ip) {
                    static $cache = [];
                    if (!isset($cache[$ip])) {
                        $Class = strtr($ip, '.', '-');
                        $cache[$ip] = '<span class="cc_'.$Class.'">Resolving CC...'
                            . '<script type="text/javascript">'
                                . '$(document).ready(function() {'
                                    . '$.get(\'tools.php?action=get_cc&ip='.$ip.'\', function(cc) {'
                                        . '$(\'.cc_'.$Class.'\').html(cc);'
                                    . '});'
                                . '});'
                            . '</script></span>';
                    }
                    return $cache[$ip];
                })($addr),
                'UTF-8'
            );
        }));

        $Twig->addFunction(new \Twig\TwigFunction('resolveIpv4', function ($addr) {
            return new \Twig\Markup(
                (function ($ip) {
                    if (!$ip) {
                        $ip = '127.0.0.1';
                    }
                    static $cache = [];
                    if (!isset($cache[$ip])) {
                        $class = strtr($ip, '.', '-');
                        $cache[$ip] = '<span class="host_' . $class
                            . '">Resolving host' . "\xE2\x80\xA6" . '<script type="text/javascript">$(document).ready(function() {'
                            .  "\$.get('tools.php?action=get_host&ip=$ip', function(host) {\$('.host_$class').html(host)})})</script></span>";
                    }
                    return $cache[$ip];
                })($addr),
                'UTF-8'
            );
        }));

        $Twig->addFunction(new \Twig\TwigFunction('shorten', function ($text, $length) {
            return new \Twig\Markup(
                shortenString($text, $length),
                'UTF-8'
            );
        }));

        $Twig->addTest(
            new \Twig\TwigTest('numeric', function ($value) {
                return is_numeric($value);
            })
        );

        return $Twig;
    }
}
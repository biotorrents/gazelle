<?php
#declare(strict_types=1);

class Format
{
    /**
     * Torrent Labels
     * Map a common display string to a CSS class
     * Indexes are lower case
     * Note the "tl_" prefix for "torrent label"
     *
     * There are five basic types:
     *  - tl_free (leech status)
     *  - tl_snatched
     *  - tl_reported
     *  - tl_approved
     *  - tl_notice (default)
     *
     * @var array Strings
     */
    private static $TorrentLabels = array(
        'default'  => 'tl_notice',
        'snatched' => 'tl_snatched',
        'seeding'  => 'tl_seeding',
        'leeching' => 'tl_leeching',

        'freeleech'     => 'tl_free',
        'neutral leech' => 'tl_free tl_neutral',
        'personal freeleech' => 'tl_free tl_personal',

        'reported'    => 'tl_reported',
        'bad tags'    => 'tl_reported tl_bad_tags',
        'bad folders' => 'tl_reported tl_bad_folders',
        'bad file names' => 'tl_reported tl_bad_file_names',

        'uncensored' => 'tl_notice'
    );


    /**
     * Shorten a string
     *
     * @param $Str string to cut
     * @param $Length cut at length
     * @param $Hard force cut at length instead of at closest word
     * @param $ShowDots Show dots at the end
     * @return string formatted string
     */
    public static function cut_string($Str, $Length, $Hard = false, $ShowDots = true)
    {
        if (mb_strlen($Str, 'UTF-8') > $Length) {
            if ($Hard === 0) {
                // Not hard, cut at closest word
                $CutDesc = mb_substr($Str, 0, $Length, 'UTF-8');
                $DescArr = explode(' ', $CutDesc);

                if (count($DescArr) > 1) {
                    array_pop($DescArr);
                    $CutDesc = implode(' ', $DescArr);
                }
                
                if ($ShowDots) {
                    $CutDesc .= '…';
                }
            } else {
                $CutDesc = mb_substr($Str, 0, $Length, 'UTF-8');
                if ($ShowDots) {
                    $CutDesc .= '…';
                }
            }

            return $CutDesc;
        } else {
            return $Str;
        }
    }


    /**
     * Gets the CSS class corresponding to a ratio
     *
     * @param $Ratio ratio to get the css class for
     * @return string the CSS class corresponding to the ratio range
     */
    private static function get_ratio_color($ratio)
    {
        if ($ratio < 0.1) {
            return 'r00';
        }

        if ($ratio < 0.2) {
            return 'r01';
        }

        if ($ratio < 0.3) {
            return 'r02';
        }

        if ($ratio < 0.4) {
            return 'r03';
        }

        if ($ratio < 0.5) {
            return 'r04';
        }

        if ($ratio < 0.6) {
            return 'r05';
        }

        if ($ratio < 0.7) {
            return 'r06';
        }

        if ($ratio < 0.8) {
            return 'r07';
        }

        if ($ratio < 0.9) {
            return 'r08';
        }

        if ($ratio < 1.0) {
            return 'r09';
        }

        if ($ratio < 2.0) {
            return 'r10';
        }

        if ($ratio < 5.0) {
            return 'r20';
        }

        return 'r50';
    }


    /**
     * Calculates and formats a ratio.
     *
     * @param int $dividend numerator
     * @param int $divisor demoninator
     * @param boolean $color if true, ratio will be colored
     * @return string formatted ratio HTML
     */
    public static function get_ratio_html($dividend, $divisor, $color = true)
    {
        $ratio = self::get_ratio($dividend, $divisor);

        if ($ratio === false) {
            return '&ndash;';
        }

        if ($ratio === '∞') {
            return '<span class="tooltip r99" title="Infinite">∞</span>';
        }

        if ($color) {
            $ratio = sprintf(
                '<span class="tooltip %s" title="%s">%s</span>',
                self::get_ratio_color($ratio),
                self::get_ratio($dividend, $divisor, 5),
                $ratio
            );
        }

        return $ratio;
    }


    /**
     * Returns ratio
     * @param int $Dividend
     * @param int $Divisor
     * @param int $Decimal floor to n decimals (e.g. subtract .005 to floor to 2 decimals)
     * @return boolean|string
     */
    public static function get_ratio($Dividend, $Divisor, $Decimal = 2)
    {
        if ((int) $Divisor === 0 && (int) $Dividend === 0) {
            return false;
        }

        if ((int) $Divisor === 0) {
            return '∞';
        }

        return Text::float(max($Dividend / $Divisor - (0.5 / pow(10, $Decimal)), 0), $Decimal);
    }


    /**
     * Gets the query string of the current page, minus the parameters in $Exclude
     *
     * @param array $Exclude Query string parameters to leave out, or blank to include all parameters.
     * @param bool $Escape Whether to return a string prepared for HTML output
     * @param bool $Sort Whether to sort the parameters by key
     * @return An optionally HTML sanatized query string
     */
    public static function get_url($Exclude = false, $Escape = true, $Sort = false)
    {
        if ($Exclude !== false) {
            $Separator = $Escape ? '&amp;' : '&';
            $QueryItems = null;
            parse_str($_SERVER['QUERY_STRING'], $QueryItems);

            foreach ($Exclude as $Key) {
                unset($QueryItems[$Key]);
            }

            if ($Sort) {
                ksort($QueryItems);
            }

            return http_build_query($QueryItems, '', $Separator);
        } else {
            return $Escape ? esc($_SERVER['QUERY_STRING']) : $_SERVER['QUERY_STRING'];
        }
    }


    /**
     * Finds what page we're on and gives it to us, as well as the LIMIT clause for SQL
     * Takes in $_GET['page'] as an additional input
     *
     * @param $PerPage Results to show per page
     * @param $DefaultResult Optional, which result's page we want if no page is specified
     * If this parameter is not specified, we will default to page 1
     *
     * @return array(int, string) What page we are on, and what to use in the LIMIT section of a query
     * e.g. "SELECT […] LIMIT $Limit;"
     */
    public static function page_limit($PerPage, $DefaultResult = 1)
    {
        if (!isset($_GET['page'])) {
            $Page = ceil($DefaultResult / $PerPage);
            # todo: Strict equality breaks comment fetching
            if ($Page == 0) {
                $Page = 1;
            }
            $Limit = $PerPage;
        } else {
            if (!is_number($_GET['page'])) {
                error(0);
            }
            $Page = $_GET['page'];

            if ($Page <= 0) {
                $Page = 1;
            }
            $Limit = $PerPage * $Page - $PerPage . ", $PerPage";
        }
        return array($Page, $Limit);
    }


    /**
     * catalogue_limit()
     *
     * A9 magic. Some other poor soul can write the phpdoc.
     * For data stored in memcached catalogues (giant arrays), e.g., forum threads
     */
    public static function catalogue_limit($Page, $PerPage, $CatalogueSize = 500)
    {
        $CatalogueID = floor(($PerPage * $Page - $PerPage) / $CatalogueSize);
        $CatalogueLimit = ($CatalogueID * $CatalogueSize).", $CatalogueSize";
        return array($CatalogueID, $CatalogueLimit);
    }


    /**
     * catalogue_select()
     */
    public static function catalogue_select($Catalogue, $Page, $PerPage, $CatalogueSize = 500)
    {
        return array_slice($Catalogue, (($PerPage * $Page - $PerPage) % $CatalogueSize), $PerPage, true);
    }


    /*
     * Get pages
     * Returns a page list, given certain information about the pages.
     *
     * @param int $StartPage: The current record the page you're on starts with.
     *    e.g. if you're on page 2 of a forum thread with 25 posts per page, $StartPage is 25.
     *    If you're on page 1, $StartPage is 0.
     * @param int $TotalRecords: The total number of records in the result set.
     *    e.g. if you're on a forum thread with 152 posts, $TotalRecords is 152.
     * @param int $ItemsPerPage: Self-explanatory. The number of records shown on each page
     *    e.g. if there are 25 posts per forum page, $ItemsPerPage is 25.
     * @param int $ShowPages: The number of page links that are shown.
     *    e.g. If there are 20 pages that exist, but $ShowPages is only 11, only 11 links will be shown.
     * @param string $Anchor A URL fragment to attach to the links.
     *    e.g. '#comment12'
     * @return A sanitized HTML page listing.
     */
    public static function get_pages($StartPage, $TotalRecords, $ItemsPerPage, $ShowPages = 11, $Anchor = '')
    {
        global $Document, $Method, $Mobile;
        $Location = "$Document.php";
        $StartPage = ceil($StartPage);
        $TotalPages = 0;

        if ($TotalRecords > 0) {
            $StartPage = min($StartPage, ceil($TotalRecords / $ItemsPerPage));

            $ShowPages--;
            $TotalPages = ceil($TotalRecords / $ItemsPerPage);

            if ($TotalPages > $ShowPages) {
                $StartPosition = $StartPage - round($ShowPages / 2);

                if ($StartPosition <= 0) {
                    $StartPosition = 1;
                } else {
                    if ($StartPosition >= ($TotalPages - $ShowPages)) {
                        $StartPosition = $TotalPages - $ShowPages;
                    }
                }

                $StopPage = $ShowPages + $StartPosition;
            } else {
                $StopPage = $TotalPages;
                $StartPosition = 1;
            }

            $StartPosition = max($StartPosition, 1);

            $QueryString = self::get_url(array('page', 'post'));
            if ($QueryString !== '') {
                $QueryString = "&amp;$QueryString";
            }

            $Pages = '';

            if ($StartPage > 1) {
                $Pages .= "<a href='$Location?page=1$QueryString$Anchor'><strong>&laquo; First</strong></a> ";
                $Pages .= "<a href='$Location?page=".($StartPage - 1).$QueryString.$Anchor."' class='pager_prev'><strong>&lsaquo; Prev</strong></a> | ";
            }
            // End change

            if (!$Mobile) {
                for ($i = $StartPosition; $i <= $StopPage; $i++) {
                    if ($i !== $StartPage) {
                        $Pages .= "<a href='$Location?page=$i$QueryString$Anchor'>";
                    }

                    $Pages .= '<strong>';
                    if ($i * $ItemsPerPage > $TotalRecords) {
                        $Pages .= ((($i - 1) * $ItemsPerPage) + 1)."-$TotalRecords";
                    } else {
                        $Pages .= ((($i - 1) * $ItemsPerPage) + 1).'-'.($i * $ItemsPerPage);
                    }

                    $Pages .= '</strong>';
                    if ($i !== $StartPage) {
                        $Pages .= '</a>';
                    }

                    if ($i < $StopPage) {
                        $Pages .= ' | ';
                    }
                }
            } else {
                $Pages .= $StartPage;
            }

            if ($StartPage && $StartPage < $TotalPages) {
                $Pages .= " | <a href='$Location?page=".($StartPage + 1).$QueryString.$Anchor."' class='pager_next'><strong>Next &rsaquo;</strong></a> ";
                $Pages .= "<a href='$Location?page=$TotalPages$QueryString$Anchor'><strong> Last&nbsp;&raquo;</strong></a>";
            }
        }

        if ($TotalPages > 1) {
            return $Pages;
        }
    }


    /**
     * Format a size in bytes as a human readable string in KiB/MiB/…
     * Note: KiB, MiB, etc. are the IEC units, which are in base 2.
     * KB, MB are the SI units, which are in base 10.
     *
     * @param int $size
     * @param int $levels Number of decimal places. Defaults to 2, unless the size >= 1TB, in which case it defaults to 4.
     * @return string formatted number.
     */
    public static function get_size($size, $levels = 2)
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        $size = floatval($size);

        for (
            $steps = 0;
            abs($size) >= 1024 && $steps < count($units);
            $size /= 1024, $steps++
            ) {
            # apparently useless, but defines $steps
        }

        if (func_num_args() === 1 && $steps >= 4) {
            $levels++;
        }
        return Text::float($size, $levels) . ' ' . $units[$steps];
    }


    /**
     * Given a formatted string of a size, get the number of bytes it represents.
     *
     * @param string $Size formatted size string, e.g. 123.45k
     * @return Number of bytes it represents, e.g. (123.45 * 1024)
     */
    public static function get_bytes($Size)
    {
        list($Value, $Unit) = sscanf($Size, "%f%s");
        $Unit = ltrim($Unit);

        if (empty($Unit)) {
            return $Value ? round($Value) : 0;
        }

        switch (strtolower($Unit[0])) {
          case 'k': return round($Value * 1024);
          case 'm': return round($Value * 1048576);
          case 'g': return round($Value * 1073741824);
          case 't': return round($Value * 1099511627776);
          default: return 0;
        }
    }


    /**
     * Echo data sent in a GET form field, useful for text areas.
     *
     * @param string $Index the name of the form field
     * @param boolean $Return if set to true, value is returned instead of echoed.
     * @return Sanitized value of field index if $Return == true
     */
    public static function form($Index, $Return = false)
    {
        if (!empty($_GET[$Index])) {
            if ($Return) {
                return esc($_GET[$Index]);
            } else {
                echo esc($_GET[$Index]);
            }
        }
    }


    /**
     * Convenience function to echo out selected="selected" and checked="checked" so you don't have to.
     *
     * @param string $Name the name of the option in the select (or field in $Array)
     * @param mixed $Value the value that the option must be for the option to be marked as selected or checked
     * @param string $Attribute The value returned/echoed is $Attribute="$Attribute" with a leading space
     * @param array $Array The array the option is in, defaults to GET.
     * @return void
     */
    public static function selected($Name, $Value, $Attribute = 'selected', $Array = [])
    {
        if (empty($Array)) {
            $Array = $_GET;
        }

        if (isset($Array[$Name]) && $Array[$Name] !== '') {
            if ($Array[$Name] === $Value) {
                echo " $Attribute='$Attribute'";
            }
        }
    }


    /**
     * Modified accessor for the $TorrentLabels array
     *
     * Converts $Text to lowercase and strips non-word characters
     *
     * @param string $Text Search string
     * @return string CSS class(es)
     */
    private static function find_torrent_label_class($Text)
    {
        $Index = mb_eregi_replace('(?:[^\w\d\s]+)', '', strtolower($Text));
        if (isset(self::$TorrentLabels[$Index])) {
            return self::$TorrentLabels[$Index];
        } else {
            return self::$TorrentLabels['default'];
        }
    }


    /**
     * Creates a strong element that notes the torrent's state.
     * E.g.: snatched/freeleech/neutral leech/reported
     *
     * The CSS class is inferred using find_torrent_label_class($Text)
     *
     * @param string $Text Display text
     * @param string $Class Custom CSS class
     * @return string <strong> element
     */
    public static function torrent_label($Text, $Class = '')
    {
        if (empty($Class)) {
            $Class = self::find_torrent_label_class($Text);
        }
        
        return sprintf(
            '<strong class="torrent_label tooltip %1$s" title="%2$s" style="white-space: nowrap;">%2$s</strong>',
            esc($Class),
            esc($Text)
        );
    }


    /**
     * Formats a CSS class name from a Category ID
     * @global array $Categories
     * @param int|string $CategoryID This number will be subtracted by one
     * @return string
     */
    public static function css_category($CategoryID = 1)
    {
        global $Categories;
        return 'cats_' . strtolower(str_replace(
            array('-', ' '),
            '',
            $Categories[$CategoryID - 1]
        ));
    }


    /**
     * Formats a CSS class name from a Category ID
     * @global array $Categories
     * @param int|string $CategoryID This number will be subtracted by one
     * @return string
     */
    public static function pretty_category($CategoryID = 1)
    {
        global $Categories;
        return ucwords(str_replace('-', ' ', $Categories[$CategoryID - 1]));
    }
}

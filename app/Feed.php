<?php
declare(strict_types = 1);

/**
 * Feed
 *
 * Really simple really simple syndication.
 * A world-class cultural institution uses this code.
 */

class Feed
{
    /**
     * __construct
     */
    public function authenticate()
    {
        $get = Http::query("get");

        if (
            empty($_GET['feed'])
            || empty($_GET['authkey'])
            || empty($_GET['auth'])
            || empty($_GET['passkey'])
            || empty($_GET['user'])
            || !is_number($_GET['user'])
            || strlen($_GET['authkey']) !== 32
            || strlen($_GET['passkey']) !== 32
            || strlen($_GET['auth']) !== 32
          ) {
            Http::response(400);
        }
    }


    /**
     * open
     */
    public function open()
    {
        if (headers_sent()) {
            return false;
        }

        header("Content-Type: application/xml; charset=utf-8");
        echo "<?xml version='1.0' encoding='utf-8'?>",
             "<rss xmlns:dc='http://purl.org/dc/elements/1.1/' version='2.0'><channel>";
    }


    /**
     * close
     */
    public function close()
    {
        echo "</channel></rss>";
    }


    /**
     * channel
     */
    public function channel(string $title, string $description, string $section = "")
    {
        $app = App::go();

        $date = date("r");
        $site = site_url();

        # echo commas because <<<XML would copy whitespace
        echo "<title>{$title} {$app->env->SEP} {$app->env->SITE_NAME}</title>",
             "<link>{$site}{$section}</link>",
             "<description>{$description}</description>",
             "<language>en-us</language>",
             "<lastBuildDate>{$date}</lastBuildDate>",
             "<docs>http://blogs.law.harvard.edu/tech/rss</docs>",
             "<generator>Gazelle Feed Class</generator>";
    }


    /**
     * item
     */
    public function item(string $title, string $description, string $page, string $creator, string $comments = "", string $category = "", string $date = "")
    {
        $site = site_url();

        if ($date === "") {
            $date = date("r");
        } else {
            $date = date("r", strtotime($date));
        }

        # escape with CDATA, otherwise the feed breaks
        $item  = "<item>";
        $item .= "<title><![CDATA[{$title}]]></title>";
        $item .= "<description><![CDATA[{$description}]]></description>";
        $item .= "<pubDate>{$date}</pubDate>";
        $item .= "<link>{$site}{$page}</link>";
        $item .= "<guid>{$site}{$page}</guid>";

        if (!empty($comments)) {
            $item .= "<comments>{$site}{$comments}</comments>";
        }

        if (!empty($category)) {
            $item .= "<category><![CDATA[{$category}]]></category>";
        }

        $item .= "<dc:creator>{$creator}</dc:creator></item>";

        return $item;
    }


    /**
     * retrieve
     */
    public function retrieve(string $cacheKey, string $authKey, string $passKey)
    {
        $app = App::go();

        $entries = $app->cacheOld->get_value($cacheKey);
        if (!$entries) {
            $entries = [];
        }

        foreach ($entries as $item) {
            echo str_replace(
                ["[[PASSKEY]]", "[[AUTHKEY]]"],
                [Text::esc($passKey), Text::esc($authKey)],
                $item
            );
        }
    }


    /**
     * populate
     */
    public function populate(string $cacheKey, string $item)
    {
        $app = App::go();

        $entries = $app->cacheOld->get_value($cacheKey, true);
        if (!$entries) {
            $entries = [];
        }
        
        if (count($entries) >= 50) {
            array_pop($entries);
        }
        
        array_unshift($entries, $item);
        $app->cacheOld->cache_value($cacheKey, $entries, 0);
    }
} # class

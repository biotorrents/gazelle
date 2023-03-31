<?php

declare(strict_types=1);


/**
 * Feed
 *
 * Really simple really simple syndication.
 * A world-class cultural institution uses this code.
 */

class Feed
{
    /**
     * authenticate
     *
     * @return void
     */
    public function authenticate(): void
    {
        $get = Http::query("get");

        if (empty($get["feed"])
            || empty($get["authkey"])
            || empty($get["auth"])
            || empty($get["passkey"])
            || empty($get["user"])
            || !is_numeric($get["user"])
            || strlen($get["authkey"]) !== 32
            || strlen($get["passkey"]) !== 32
            || strlen($get["auth"]) !== 32
        ) {
            Http::response(400);
        }
    }


    /**
     * open
     *
     * @return bool
     */
    public function open(): bool
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
     *
     * @return void
     */
    public function close(): void
    {
        echo "</channel></rss>";
    }


    /**
     * channel
     *
     * @param string $title
     * @param string $description
     * @param string $section
     * @return void
     */
    public function channel(string $title, string $description, string $section = ""): void
    {
        $app = \Gazelle\App::go();

        $date = date("r");
        $site = site_url();

        # echo commas because <<<XML would copy whitespace
        echo "<title>{$title} {$app->env->separator} {$app->env->siteName}</title>",
        "<link>{$site}{$section}</link>",
        "<description>{$description}</description>",
        "<language>en-us</language>",
        "<lastBuildDate>{$date}</lastBuildDate>",
        "<docs>http://blogs.law.harvard.edu/tech/rss</docs>",
        "<generator>Gazelle Feed Class</generator>";
    }


    /**
     * item
     *
     * @param string $title
     * @param string $description
     * @param string $page
     * @param string $creator
     * @param string $comments
     * @param string $category
     * @param string $date
     * @return string
     */
    public function item(string $title, string $description, string $page, string $creator, string $comments = "", string $category = "", string $date = ""): string
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
     *
     * @param string $cacheKey
     * @param string $authKey
     * @param string $passKey
     * @return void
     */
    public function retrieve(string $cacheKey, string $authKey, string $passKey): void
    {
        $app = \Gazelle\App::go();

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
     *
     * @param string $cacheKey
     * @param string $item
     * @return void
     */
    public function populate(string $cacheKey, string $item): void
    {
        $app = \Gazelle\App::go();

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

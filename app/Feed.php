<?php
declare(strict_types = 1);

class Feed
{
    /**
     * open_feed
     */
    public function open_feed()
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo "<?xml version='1.0' encoding='utf-8'?>",
             "<rss xmlns:dc='http://purl.org/dc/elements/1.1/' version='2.0'><channel>";
    }

    /**
     * close_feed
     */
    public function close_feed()
    {
        echo "</channel></rss>";
    }

    /**
     * channel
     */
    public function channel($Title, $Description, $Section = '')
    {
        $ENV = ENV::go();
        $Site = site_url();

        # echo commas because <<<XML would copy whitespace
        echo "<title>$Title $ENV->SEP $ENV->SITE_NAME</title>",
             "<link>$Site$Section</link>",
             "<description>$Description</description>",
             "<language>en-us</language>",
             "<lastBuildDate>".date('r')."</lastBuildDate>",
             "<docs>http://blogs.law.harvard.edu/tech/rss</docs>",
             "<generator>Gazelle Feed Class</generator>";
    }

    /**
     * item
     */
    public function item($Title, $Description, $Page, $Creator, $Comments = '', $Category = '', $Date = '')
    {
        $Site = site_url();
        if ($Date === '') {
            $Date = date('r');
        } else {
            $Date = date('r', strtotime($Date));
        }

        // Escape with CDATA, otherwise the feed breaks.
        $Item  = "<item>";
        $Item .= "<title><![CDATA[$Title]]></title>";
        $Item .= "<description><![CDATA[$Description]]></description>";
        $Item .= "<pubDate>$Date</pubDate>";
        $Item .= "<link>$Site$Page</link>";
        $Item .= "<guid>$Site$Page</guid>";

        if ($Comments !== '') {
            $Item .= "<comments>$Site$Comments</comments>";
        }

        if ($Category !== '') {
            $Item .= "<category><![CDATA[$Category]]></category>";
        }

        $Item .= "<dc:creator>$Creator</dc:creator></item>";
        return $Item;
    }

    /**
     * retrieve
     */
    public function retrieve($cacheKey, $AuthKey, $PassKey)
    {
        global $cache;
        $Entries = $cache->get_value($cacheKey);

        if (!$Entries) {
            $Entries = [];
        } else {
            foreach ($Entries as $Item) {
                echo str_replace(
                    array('[[PASSKEY]]', '[[AUTHKEY]]'),
                    array(esc($PassKey), esc($AuthKey)),
                    $Item
                );
            }
        }
    }

    /**
     * populate
     */
    public function populate($cacheKey, $Item)
    {
        global $cache;
        $Entries = $cache->get_value($cacheKey, true);

        if (!$Entries) {
            $Entries = [];
        } else {
            if (count($Entries) >= 50) {
                array_pop($Entries);
            }
        }
        
        array_unshift($Entries, $Item);
        $cache->cache_value($cacheKey, $Entries, 0); // inf cache
    }
}

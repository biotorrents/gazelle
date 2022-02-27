<?php
declare(strict_types = 1);

class Wiki
{
    /**
     * Normalize a wiki alias.
     * The database determines length:
     * wiki_aliases.Alias
     *
     * @param string $str
     * @return string
     */
    public static function normalize_alias($str)
    {
        return substr(
            preg_replace(
                '/[^a-z0-9]/',
                '',
                strtolower(
                    htmlentities(
                        trim($str)
                    )
                )
            ),
            0,
            50
        );
    }


    /**
     * Get all aliases in an associative array of Alias => ArticleID.
     *
     * @return array
     */
    public static function get_aliases()
    {
        $Aliases = G::$cache->get_value('wiki_aliases');
        if (!$Aliases) {
            $QueryID = G::$db->get_query_id();

            G::$db->prepared_query("
            SELECT
              `Alias`,
              `ArticleID`
            FROM
              `wiki_aliases`
            ");

            $Aliases = G::$db->to_pair('Alias', 'ArticleID');
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value('wiki_aliases', $Aliases, 3600 * 24 * 14); // 2 weeks
        }

        return $Aliases;
    }


    /**
     * Flush the alias cache.
     * Call this whenever you touch the wiki_aliases table.
     */
    public static function flush_aliases()
    {
        G::$cache->delete_value('wiki_aliases');
    }


    /**
     * Get the ArticleID corresponding to an alias.
     *
     * @param string $Alias
     * @return int
     */
    public static function alias_to_id($Alias)
    {
        $Aliases = self::get_aliases();
        $Alias = self::normalize_alias($Alias);

        if (!isset($Aliases[$Alias])) {
            return false;
        } else {
            return (int) $Aliases[$Alias];
        }
    }


    /**
     * Get an article; returns false on error if $Error = false.
     *
     * @param int $ArticleID
     * @param bool $Error
     * @return array|bool
     */
    public static function get_article($ArticleID, $Error = true)
    {
        $Contents = G::$cache->get_value('wiki_article_'.$ArticleID);
        if (!$Contents) {
            $QueryID = G::$db->get_query_id();

            G::$db->prepared_query("
            SELECT
              w.`Revision`,
              w.`Title`,
              w.`Body`,
              w.`MinClassRead`,
              w.`MinClassEdit`,
              w.`Date`,
              w.`Author`,
              u.`Username`,
              GROUP_CONCAT(a.`Alias`),
              GROUP_CONCAT(a.`UserID`)
            FROM
              `wiki_articles` AS w
            LEFT JOIN `wiki_aliases` AS a
            ON
              w.`ID` = a.`ArticleID`
            LEFT JOIN `users_main` AS u
            ON
              u.`ID` = w.`Author`
            WHERE
              w.`ID` = '$ArticleID'
            GROUP BY
              w.`ID`
            ");

            if (!G::$db->has_results()) {
                if ($Error) {
                    error(404);
                } else {
                    return false;
                }
            }
            
            $Contents = G::$db->to_array();
            G::$db->set_query_id($QueryID);
            G::$cache->cache_value('wiki_article_'.$ArticleID, $Contents, 3600 * 24 * 14); // 2 weeks
        }

        return $Contents;
    }


    /**
     * Flush an article's cache.
     * Call this whenever you edited a wiki article or its aliases.
     *
     * @param int $ArticleID
     */
    public static function flush_article($ArticleID)
    {
        G::$cache->delete_value('wiki_article_'.$ArticleID);
    }
}

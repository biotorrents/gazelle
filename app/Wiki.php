<?php

declare(strict_types=1);


/**
 * Gazelle\Wiki
 *
 * Literal notebooks powered by Starboard Notebook.
 *
 * @see https://github.com/gzuidhof/starboard-notebook
 */

namespace Gazelle;

class Wiki extends ObjectCrud
{
    # database table
    public string $object = "wiki_articles";

    # object properties
    public $uuid;
    public $id;
    public $revision;
    public $title;
    public $body;
    public $minClassRead;
    public $minClassEdit;
    public $authorId;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    # ["database" => "display"]
    protected array $maps = [
        "uuid" => "uuid",
        "ID" => "id",
        "Revision" => "revision",
        "Title" => "title",
        "Body" => "body",
        "MinClassRead" => "minClassRead",
        "MinClassEdit" => "minClassEdit",
        #"Date" => "createdAt",
        "Author" => "authorId",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];

    # cache settings
    private string $cachePrefix = "wiki:";
    private string $cacheDuration = "1 hour";


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
        $app = \Gazelle\App::go();

        $Aliases = $app->cache->get('wiki_aliases');
        if (!$Aliases) {
            $QueryID = $app->dbOld->get_query_id();

            $app->dbOld->prepared_query("
            SELECT
              `Alias`,
              `ArticleID`
            FROM
              `wiki_aliases`
            ");

            $Aliases = $app->dbOld->to_pair('Alias', 'ArticleID');
            $app->dbOld->set_query_id($QueryID);
            $app->cache->set('wiki_aliases', $Aliases, 3600 * 24 * 14); // 2 weeks
        }

        return $Aliases;
    }


    /**
     * Flush the alias cache.
     * Call this whenever you touch the wiki_aliases table.
     */
    public static function flush_aliases()
    {
        $app = \Gazelle\App::go();

        $app->cache->delete('wiki_aliases');
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
        $app = \Gazelle\App::go();

        $Contents = $app->cache->get('wiki_article_' . $ArticleID);
        if (!$Contents) {
            $QueryID = $app->dbOld->get_query_id();

            $app->dbOld->prepared_query("
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

            if (!$app->dbOld->has_results()) {
                if ($Error) {
                    error(404);
                } else {
                    return false;
                }
            }

            $Contents = $app->dbOld->to_array();
            $app->dbOld->set_query_id($QueryID);
            $app->cache->set('wiki_article_' . $ArticleID, $Contents, 3600 * 24 * 14); // 2 weeks
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
        $app = \Gazelle\App::go();

        $app->cache->delete('wiki_article_' . $ArticleID);
    }
}

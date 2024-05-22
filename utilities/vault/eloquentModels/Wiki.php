<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Wiki
 */

namespace Gazelle\Models;

class Wiki extends Base
{
    # https://laravel.com/docs/master/eloquent#table-names
    protected $table = "wiki_articles";

    # https://laravel.com/docs/master/eloquent#primary-keys
    protected $primaryKey = "id";
    public $incrementing = false;

    # https://laravel.com/docs/master/eloquent#mass-assignment
    protected $guarded = [];

    # index article
    public static int $indexArticleId = 1;


    /** relationships */


    /**
     * aliases
     *
     * Gets the aliases for a wiki article by id.
     *
     * @return ?array
     */
    public function aliases(): ?array
    {
        $app = \Gazelle\App::go();

        $query = "select alias from wiki_aliases where articleId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        return $ref;
    }


    /**
     * revisions
     */
    public function revisions()
    {
        #return $this->hasMany(TorrentGroup::class, "GroupID", "id");
    }


    /** methods */


    /**
     * getIdByAlias
     *
     * Gets the article id by alias.
     *
     * @param string $alias
     * @return ?int
     */
    public static function getIdByAlias(string $alias): ?int
    {
        $app = \Gazelle\App::go();

        $alias = self::normalizeAlias($alias);

        $query = "select articleId from wiki_aliases where alias = ?";
        $ref = $app->dbNew->single($query, [$alias]);

        return $ref;
    }


    /**
     * normalizeAlias
     *
     * Normalize a wiki alias.
     *
     * @param string $alias
     * @return string
     */
    public static function normalizeAlias(string $alias): string
    {
        $alias = \Gazelle\Text::utf8($alias);

        # only allow alphanumeric characters
        $alias = preg_replace("/[^a-z0-9]/", "", strtolower($alias));

        # limit to 64 characters
        $alias = substr($alias, 0, 64);

        return $alias;
    }


    /**
     * toJsonApi
     *
     * @return array
     */
    public function toJsonApi(): array
    {
        # todo
    }
} # class

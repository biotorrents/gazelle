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
     * update
     */
    public function update(int|string $identifier, array $data = []): void
    {
        $app = App::go();

        # check permissions
        if ($app->user->cant("site_edit_wiki")) {
            throw new Exception("invalid permissions");
        }

        if ($this->minClassEdit > $app->user->extra["Class"]) {
            throw new Exception("invalid permissions");
        }

        # first, create a revision
        $query = "
            insert into wiki_revisions (id, revision, title, body, date, author)
            values (:id, :revision, :title, :body, :date, :author)
        ";

        $variables = [
            "id" => $this->id,
            "revision" => $this->revision,
            "title" => $this->title,
            "body" => $this->body,
            "date" => $this->updatedAt ?? $this->createdAt,
            "author" => $this->authorId,
        ];

        $app->dbNew->do($query, $variables);

        # then, update the article
        $data["revision"] = $this->revision + 1;
        $data["author"] = $app->user->core["id"];

        parent::update($this->id, $data);
    }


    /**
     * delete
     */
    public function delete(int|string $identifier): void
    {
        $app = App::go();

        # check permissions
        if ($app->user->cant("admin_manage_wiki")) {
            throw new Exception("invalid permissions");
        }

        if ($this->minClassEdit > $app->user->extra["Class"]) {
            throw new Exception("invalid permissions");
        }

        # prevent deleting the wiki index
        if ($this->id === 1) {
            throw new Exception("can't delete the index article");
        }

        # write to the site log
        \Misc::write_log("the wiki article {$identifier} with the title {$this->title} was deleted by {$app->user->core["username"]}");

        # delete aliases and revisions
        $query = "delete from wiki_aliases where articleId = ?";
        $app->dbNew->do($query, [$this->id]);

        $query = "delete from wiki_revisions where id = ?";
        $app->dbNew->do($query, [$this->id]);

        # perform a soft delete on the article itself
        parent::delete($this->id);
    }


    /**
     * getAliases
     *
     * Gets the aliases for a wiki article by id.
     *
     * @return ?array
     */
    public function getAliases(): ?array
    {
        $app = App::go();

        $query = "select alias from wiki_aliases where articleId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        /*
        $aliases = [];
        foreach ($ref as $row) {
            $aliases[$row["id"]] = $row["alias"];
        }
        */

        return $ref;
    }


    /**
     * getOneRevision
     *
     * Gets one revision for a wiki article by id and revision.
     */
    public function getOneRevision(int $revision): array
    {
        $app = App::go();

        $query = "select * from wiki_revisions where id = ? and revision = ?";
        $ref = $app->dbNew->row($query, [$this->id, $revision]);

        return $ref;
    }


    /**
     * getAllRevisions
     *
     * Gets all the revisions for a wiki article by id.
     *
     * @return array e.g., [revisionId => date] in descending order
     */
    public function getAllRevisions(): array
    {
        $app = App::go();

        $query = "select revision, created_at from wiki_revisions where id = ? order by revision desc";
        $ref = $app->dbNew->multi($query, [$this->id]);

        $revisions = [];
        foreach ($ref as $row) {
            $revisions[$row["revision"]] = $row["created_at"];
        }

        return $revisions;
    }


    /** legacy code */


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

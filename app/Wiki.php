<?php

declare(strict_types=1);


/**
 * Gazelle\Wiki
 *
 * Literate programming notebooks powered by Starboard Notebook.
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
     *
     * @param int|string $identifier
     * @param array $data
     * @return void
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
     *
     * @param int|string $identifier
     * @return void
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


    /** */


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

        return $ref;
    }


    /**
     * getOneRevision
     *
     * Gets one revision for a wiki article by id and revision.
     *
     * @param int $revision
     * @return ?array
     */
    public function getOneRevision(int $revision): ?array
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


    /** static */


    /**
     * normalizeAlias
     *
     * Normalize a wiki alias.
     *
     * @param string $string
     * @return string
     */
    public static function normalizeAlias($string): string
    {
        $string = Text::utf8($string);

        # only allow alphanumeric characters
        $string = preg_replace("/[^a-z0-9]/", "", strtolower($string));

        # limit to 64 characters
        $string = substr($string, 0, 64);

        return $string;
    }


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
        $app = App::go();

        $alias = self::normalizeAlias($alias);

        $query = "select articleId from wiki_aliases where alias = ?";
        $ref = $app->dbNew->single($query, [$alias]);

        return $ref;
    }


    /**
     * search
     *
     * Naive database like "%foo%" search.
     * Index this with Manticorelater.
     *
     * @param ?string $searchWhat the search string, obviously
     * @param bool $titlesOnly only search article titles?
     * @return ?array array of Gazelle\Wiki objects
     */
    public static function search(?string $searchWhat = "*", bool $titlesOnly = false): ?array
    {
        $app = App::go();

        # strip garbage from the search string
        $searchWhat ??= "*";
        $searchWhat = Text::utf8($searchWhat);

        if (!$titlesOnly) {
            # search titles and bodies
            $query = "select id from wiki_articles where title like ? or body like ? and deleted_at is null order by title asc";
            $ref = $app->dbNew->multi($query, ["%{$searchWhat}%", "%{$searchWhat}%"]);
        } else {
            # search titles only
            $query = "select id from wiki_articles where title like ? and deleted_at is null order by title asc";
            $ref = $app->dbNew->multi($query, ["%{$searchWhat}%"]);
        }

        $results = [];
        foreach ($ref as $row) {
            # load it up
            $item = new self($row["id"]);

            # skip soft deletes or bad data
            if (empty($item->id || !empty($item->deletedAt))) {
                continue;
            }

            # add to the return array
            $results[] = $item;
        }

        return $results;
    }
} # class

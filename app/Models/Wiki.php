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
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "wikiArticles"; # resource name
    protected ?string $table = "wiki_articles"; # database table

    # cache settings
    private string $cachePrefix = "wiki:";
    private string $cacheDuration = "1 hour";

    # index article
    public static int $indexArticleId = 1;

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "userId" => "userId",
        "revision" => "revision",
        "title" => "title",
        "body" => "body",
        "minimumReadClass" => "minimumReadClass",
        "minimumEditClass" => "minimumEditClass",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** crud */


    /**
     * update
     *
     * @param array $data
     * @return void
     */
    public function update(array $data = []): void
    {
        $app = App::go();

        # check permissions
        if ($app->user->cant(["wiki" => "update"])) {
            throw new Exception("invalid permissions");
        }

        if ($this->attributes->minimumEditClass > $app->user->extra["Class"]) {
            throw new Exception("invalid permissions");
        }

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            # create a revision
            $query = "
                insert into wiki_revisions (id, articleId, userId, revision, title, body)
                values (:id, :articleId, :userId, :revision, :title, :body)
            ";

            $variables = [
                "id" => $app->dbNew->shortUuid(),
                "articleId" => $this->id,
                "userId" => $this->attributes->userId,
                "revision" => $this->attributes->revision,
                "title" => $this->attributes->title,
                "body" => $this->attributes->body,
            ];

            $app->dbNew->do($query, $variables);

            # then, update the article
            $data["revision"] = $this->attributes->revision + 1;
            $data["userId"] = $app->user->core["id"];

            # parent update
            parent::update($data);

            # commit the transaction
            $app->dbNew->commit();
        } catch (\Throwable $e) {
            # rollback and rethrow
            $app->dbNew->rollBack();
            throw $e;
        }
    }


    /**
     * delete
     *
     * @return void
     */
    public function delete(): void
    {
        $app = App::go();

        # check permissions
        if ($app->user->cant(["wiki" => "delete"])) {
            throw new Exception("invalid permissions");
        }

        if ($this->attributes->minimumEditClass > $app->user->extra["Class"]) {
            throw new Exception("invalid permissions");
        }

        # prevent deleting the wiki index
        if ($this->id === self::$indexArticleId) {
            throw new Exception("can't delete the index article");
        }

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            # delete aliases and revisions
            $query = "update wiki_aliases set deleted_at = now() where articleId = ?";
            $app->dbNew->do($query, [$this->id]);

            $query = "update wiki_revisions set deleted_at = now() where articleId = ?";
            $app->dbNew->do($query, [$this->id]);

            # parent delete
            parent::delete($this->id);

            # commit the transaction
            $app->dbNew->commit();

            # write to the site log
            \Misc::write_log("the wiki article {$this->id} with the title {$this->attributes->title} was deleted by {$app->user->core["username"]}");
        } catch (\Throwable $e) {
            # rollback and rethrow
            $app->dbNew->rollBack();
            throw $e;
        }
    }


    /** accessors */


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

        $query = "select alias from wiki_aliases where articleId = ? and deleted_at is null";
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

        $query = "select * from wiki_revisions where articleId = ? and revision = ? and deleted_at is null";
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

        $query = "select revision, created_at from wiki_revisions where articleId = ? and deleted_at is null order by revision desc";
        $ref = $app->dbNew->multi($query, [$this->id]);

        $revisions = [];
        foreach ($ref as $row) {
            $revisions[ $row["revision"] ] = $row["created_at"];
        }

        return $revisions;
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

        # normalize the alias
        $alias = self::normalizeAlias($alias);

        $query = "select articleId from wiki_aliases where alias = ?";
        $ref = $app->dbNew->single($query, [$alias]);

        return $ref;
    }


    /** methods */


    /**
     * searchDatabase
     *
     * Naive database like "%foo%" search.
     * Index this with Manticore later.
     *
     * @param ?string $searchWhat the search string, obviously
     * @param bool $titlesOnly only search article titles?
     * @return ?array array of Gazelle\Wiki objects
     */
    public static function searchDatabase(?string $searchWhat = "*", bool $titlesOnly = false): ?array
    {
        $app = App::go();

        # strip garbage from the search string
        $searchWhat ??= "*";
        $searchWhat = Escape::string($searchWhat);

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
            if (!$item->id) {
                continue;
            }

            # add to the return array
            $results[] = $item;
        }

        return $results;
    }


    /**
     * createAlias
     *
     * Creates an alias for a wiki article by id.
     *
     * @param string $alias
     * @return void
     */
    public function createAlias(string $alias): void
    {
        $app = App::go();

        # check permissions
        if ($app->user->cant(["wiki" => "update"])) {
            throw new Exception("invalid permissions");
        }

        if ($this->attributes->minimumEditClass > $app->user->extra["Class"]) {
            throw new Exception("invalid permissions");
        }

        # normalize the alias
        $alias = self::normalizeAlias($alias);

        # check for duplicates
        $query = "select articleId from wiki_aliases where alias = ?";
        $ref = $app->dbNew->single($query, [$alias]);

        if (!empty($ref)) {
            throw new Exception("alias already exists");
        }

        # create the alias
        $query = "insert into wiki_aliases (articleId, userId, alias) values (?, ?, ?)";
        $app->dbNew->do($query, [$this->id, $app->user->core["id"], $alias]);
    }


    /**
     * deleteAlias
     *
     * Deletes an alias for a wiki article by id.
     *
     * @param string $alias
     * @return void
     */
    public function deleteAlias(string $alias): void
    {
        $app = App::go();

        # check permissions
        if ($app->user->cant(["wiki" => "delete"])) {
            throw new Exception("invalid permissions");
        }

        if ($this->attributes->minimumEditClass > $app->user->extra["Class"]) {
            throw new Exception("invalid permissions");
        }

        # normalize the alias
        $alias = self::normalizeAlias($alias);

        # delete the alias
        $query = "update wiki_aliases set deleted_at = now() where articleId = ? and alias = ?";
        $app->dbNew->do($query, [$this->id, $alias]);
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
        $alias = Text::utf8($alias);

        # only allow alphanumeric characters
        $alias = preg_replace("/[^a-z0-9]/", "", strtolower($alias));

        # limit to 64 characters
        $alias = substr($alias, 0, 64);

        return $alias;
    }


    /**
     * hydrateNewArticle
     *
     * Hydrates a new article with some basic info such as id, title, body, etc.
     * Used to repurpose the same interface for creating and editing articles.
     *
     * @return self
     */
    public function hydrateNewArticle(): self
    {
        $app = App::go();

        if ($this->id) {
            throw new Exception("article already exists");
        }

        # starboard notebook default copy
        $defaultBodyText = <<<EOT
# %% [markdown]
{$app->env->siteName}'s wiki uses [Starboard Notebook](https://starboard.gg) to support literate programming notebooks. Starboard brings cell-by-cell Jupyter Notebooks to the browser, to interactively visualize data and to prototype concepts.

\
The notebook runs entirely in a secure browser sandbox, and it supports Markdown, $\LaTeX$, HTML, CSS, JavaScript, and Python. Please see the examples below to get an idea for what's possible. Press the ▶️ play button on the left to run a cell's code.

\
This brief introduction only covers a small subset of Starboard's features. Some more examples from Starboard's website include [Visualizing Exchange Rate Data](https://starboard.gg/#visualization) and [Python Support in Starboard Notebook](https://starboard.gg/#python).

# %% [javascript]
// we can import code dynamically, and top level await is supported
const {default: Confetti} = await import("https://cdn.skypack.dev/canvas-confetti");

function fireConfetti(event) {
    const x = event.clientX / document.body.clientWidth;
    const y = event.clientY / document.body.clientHeight;
    Confetti({origin: {x, y}});
}

// template literals are built in
html `<button @click=\${fireConfetti}>Fire Confetti 🎉</button>`

# %% [python]
# when you first run this cell, it'll load the Python runtime
# your browser should cache it, so it'll load faster next time
message = "Hello Python!"
print(message)

x = [i ** 2 for i in range(5)]
x
EOT;

        # set some important defaults
        $this->id = $app->dbNew->shortUuid();

        $attributes = [
            "userId" => $app->user->core["id"],
            "revision" => 1,
            "title" => "What will you call your new article?",
            "body" => $defaultBodyText,
            "minimumReadClass" => Roles::getGuestRoleId(),
            "minimumEditClass" => Roles::getUserRoleId(),
            "createdAt" => $app->dbNew->now(),
            "updatedAt" => $app->dbNew->now(),
        ];

        # make a RecursiveCollection
        $this->attributes = new RecursiveCollection($attributes);

        # return the object
        return $this;
    }
} # class

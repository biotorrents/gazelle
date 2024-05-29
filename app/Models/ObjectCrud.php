<?php

declare(strict_types=1);


/**
 * Gazelle\ObjectCrud
 *
 * A simple way to perform CRUD operations on core site objects without going "full Eloquent."
 * This class is intended to be extended by other classes that represent a specific object.
 *
 * Primary data MUST be either:
 *   - a single resource object, a single resource identifier object, or null, for requests that target single resources
 *   - an array of resource objects, an array of resource identifier objects, or an empty array ([]), for requests that target resource collections
 */

namespace Gazelle;

abstract class ObjectCrud extends RecursiveCollection
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = null; # resource name
    protected ?string $table = null; # database table

    public ?RecursiveCollection $attributes = null;
    public ?RecursiveCollection $relationships = null;


    /**
     * __construct
     *
     * @param int|string $identifier
     * @return void
     */
    public function __construct(int|string $identifier = null)
    {
        $this->read($identifier);
    }


    /** crud */


    /**
     * create
     *
     * @param array $data
     * @return void
     */
    public function create(array $data = []): void
    {
        $app = App::go();

        # map display => database
        $transform = $this->displayToDatabase($data);

        # create an id if none exists
        $transform["id"] ??= null;
        if (!$transform["id"]) {
            $transform["id"] = $app->dbNew->shortUuid();
        }

        # perform an upsert
        $upsert = $app->dbNew->upsert($this->table, $transform);

        # map database => display
        $attributes = [];
        $transform = $this->databaseToDisplay($upsert);

        foreach ($transform as $key => $value) {
            $attributes[$key] = $value;
        }

        # use a RecursiveCollection not an array
        $this->id = strval($upsert["id"] ?? null);
        $this->attributes = new RecursiveCollection($attributes);
    }


    /**
     * updateOrCreate
     *
     * @param array $data
     * @return void
     */
    public function updateOrCreate(array $data = []): void
    {
        $this->create($data);
    }


    /**
     * read
     *
     * @param int|string $identifier
     * @return void
     */
    public function read(int|string $identifier = null): void
    {
        $app = App::go();

        # set $this->attributes to null if the object doesn't exist
        if (!$this->exists($identifier)) {
            $nullAttributes = [];
            foreach ($this->maps as $key => $value) {
                $nullAttributes[$key] = null;
            }

            $this->attributes = new RecursiveCollection($nullAttributes);
            return;
        }

        # try to find the object
        $column = $app->dbNew->determineIdentifier($identifier);
        $query = "select * from {$this->table} where {$column} = ? and deleted_at is null";
        $row = $app->dbNew->row($query, [$identifier]);

        # set the id
        $this->id = strval($row["id"]);
        unset($row["id"]);

        # map database => display
        $attributes = [];
        $transform = $this->databaseToDisplay($row);

        foreach ($transform as $key => $value) {
            $attributes[$key] = $value ?? null;
        }

        # is it the user's own resource?
        $hasOwner = isset($attributes["userId"]);
        if ($hasOwner && $app->user->isLoggedIn()) {
            $attributes["isOwner"] = $attributes["userId"] === $app->user->core["id"];
        }

        # the value of the attributes key MUST be an object
        $this->attributes = new RecursiveCollection($attributes);

        # the value of the relationships key MUST be an object
        if (method_exists($this, "relationships")) {
            $this->relationships = new RecursiveCollection($this->relationships());
        }
    }


    /**
     * update
     *
     * @param array $data
     * @return void
     */
    public function update(array $data = []): void
    {
        $app = App::go();

        # does the object exist?
        if (!$this->exists($this->id)) {
            throw new Exception("can't update on {$this->type} with the id {$this->id}");
        }

        # map display => database
        $transform = $this->displayToDatabase($data);

        # add the identifier to the data
        $column = $app->dbNew->determineIdentifier($this->id);
        $transform[$column] = $this->id;

        # perform an upsert
        $upsert = $app->dbNew->upsert($this->table, $transform);
    }


    /**
     * delete
     *
     * @return void
     */
    public function delete(): void
    {
        $app = App::go();

        # does the object exist?
        if (!$this->exists($this->id)) {
            throw new Exception("can't delete from {$this->type} with the id {$this->id}");
        }

        # determine the identifier
        $column = $app->dbNew->determineIdentifier($this->id);

        # perform a soft delete
        $query = "update {$this->table} set deleted_at = now() where {$column} = ?";
        $app->dbNew->do($query, [$this->id]);
    }


    /**
     * restore
     *
     * @return void
     */
    public function restore(): void
    {
        $app = App::go();

        # does the object exist?
        if (!$this->exists($this->id)) {
            throw new Exception("can't restore {$this->type} with the id {$this->id}");
        }

        # determine the identifier
        $column = $app->dbNew->determineIdentifier($this->id);

        # perform a soft restore
        $query = "update {$this->table} set deleted_at = null where {$column} = ?";
        $app->dbNew->do($query, [$this->id]);
    }


    /** accessors: returns the relationship as an array of objects */


    /**
     * getRelationships
     *
     * Basic function for the helpers below.
     *
     * @param $object, e.g., TorrentGroups::class
     * @return array
     */
    private function getRelationships($object): array
    {
        $app = App::go();

        $this->relationships->{$object::$type} ??= null;
        if (!$this->relationships->{$object::$type}) {
            return [];
        }

        $data = [];
        foreach ($this->relationships->{$object::$type} as $row) {
            $data[] = new $object($row["id"]);
        }

        return $data;
    }


    /**
     * getCollages
     *
     * @return array
     */
    public function getCollages(): array
    {
        return $this->getRelationships(Collages::class);
    }


    /**
     * getConversations
     *
     * @return array
     */
    public function getConversations(): array
    {
        return $this->getRelationships(Conversations::class);
    }

    /**
     * getCreators
     *
     * @return array
     */
    public function getCreators(): array
    {
        return $this->getRelationships(Creators::class);
    }


    /**
     * getLiterature
     *
     * @return array
     */
    public function getLiterature(): array
    {
        return $this->getRelationships(Literature::class);
    }


    /**
     * getMessages
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->getRelationships(Messages::class);
    }


    /**
     * getRequests
     *
     * @return array
     */
    public function getRequests(): array
    {
        return $this->getRelationships(Requests::class);
    }


    /**
     * getRoles
     *
     * @return array
     */
    public function getRoles(): array
    {
        return $this->getRelationships(Roles::class);
    }


    /**
     * getSiteLog
     *
     * @return array
     */
    public function getSiteLog(): array
    {
        return $this->getRelationships(SiteLog::class);
    }


    /**
     * getTorrentGroups
     *
     * @return array
     */
    public function getTorrentGroups(): array
    {
        return $this->getRelationships(TorrentGroups::class);
    }


    /**
     * getTorrents
     *
     * @return array
     */
    public function getTorrents(): array
    {
        return $this->getRelationships(Torrents::class);
    }


    /**
     * getUsers
     *
     * @return array
     */
    public function getUsers(): array
    {
        throw new Exception("not implemented");

        /** */

        return $this->getRelationships(Users::class);
    }


    /**
     * getWiki
     *
     * @return array
     */
    public function getWiki(): array
    {
        return $this->getRelationships(Wiki::class);
    }


    /** mutators: updates $this->relationships with an array of objects */


    /**
     * loadRelationships
     *
     * Basic function for the helpers below.
     *
     * @param $object, e.g., TorrentGroups::class
     * @return void
     */
    private function loadRelationships($object): void
    {
        $app = App::go();

        $this->relationships->{$object::$type} ??= null;
        if (!$this->relationships->{$object::$type}) {
            unset($this->relationships->{$object::$type});

            return;
        }

        foreach ($this->relationships->{$object::$type} as $key => $row) {
            $this->relationships->{$object::$type}[$key] = new $object($row["id"]);
        }

        return;
    }


    /**
     * loadCollages
     *
     * @return void
     */
    public function loadCollages(): void
    {
        $this->loadRelationships(Collages::class);
    }


    /**
     * loadConversations
     *
     * @return void
     */
    public function loadConversations(): void
    {
        $this->loadRelationships(Conversations::class);
    }

    /**
     * loadCreators
     *
     * @return void
     */
    public function loadCreators(): void
    {
        $this->loadRelationships(Creators::class);
    }


    /**
     * loadLiterature
     *
     * @return void
     */
    public function loadLiterature(): void
    {
        $this->loadRelationships(Literature::class);
    }


    /**
     * loadMessages
     *
     * @return void
     */
    public function loadMessages(): void
    {
        $this->loadRelationships(Messages::class);
    }


    /**
     * loadRequests
     *
     * @return void
     */
    public function loadRequests(): void
    {
        $this->loadRelationships(Requests::class);
    }


    /**
     * loadRoles
     *
     * @return void
     */
    public function loadRoles(): void
    {
        $this->loadRelationships(Roles::class);
    }


    /**
     * loadSiteLog
     *
     * @return void
     */
    public function loadSiteLog(): void
    {
        $this->loadRelationships(SiteLog::class);
    }


    /**
     * loadTorrentGroups
     *
     * @return void
     */
    public function loadTorrentGroups(): void
    {
        $this->loadRelationships(TorrentGroups::class);
    }


    /**
     * loadTorrents
     *
     * @return void
     */
    public function loadTorrents(): void
    {
        $this->loadRelationships(Torrents::class);
    }


    /**
     * loadUsers
     *
     * @return void
     */
    public function loadUsers(): void
    {
        throw new Exception("not implemented");

        /** */

        $this->loadRelationships(Users::class);
    }


    /**
     * loadWiki
     *
     * @return void
     */
    public function loadWiki(): void
    {
        $this->loadRelationships(Wiki::class);
    }


    /** helpers */


    /**
     * exists
     *
     * @param int|string $identifier
     * @return bool
     */
    public function exists(int|string $identifier = null): bool
    {
        $app = App::go();

        $identifier ??= null;
        if (!$identifier) {
            return false;
        }

        # does the object exist?
        $column = $app->dbNew->determineIdentifier($identifier);
        $query = "select 1 from {$this->table} where {$column} = ? and deleted_at is null";

        $good = $app->dbNew->single($query, [$identifier]);
        return boolval($good);
    }


    /**
     * save
     *
     * Save the object to the database using an upsert.
     *
     * @return bool true on success, false on failure
     */
    public function save(): bool
    {
        $app = App::go();

        # does the object exist?
        if (!$this->exists($this->id)) {
            throw new Exception("can't save {$this->type} with the id {$this->id}");
        }

        foreach ($this->maps as $key => $value) {
            $data[$key] = $this->attributes->$value;
        }

        # add the identifier to the data
        $column = $app->dbNew->determineIdentifier($this->id);
        $data[$column] = $this->id;

        $upsert = $app->dbNew->upsert($this->table, $data);
        return boolval($upsert);
    }


    /** */


    /**
     * databaseToDisplay
     *
     * Maps the database column names to the display names.
     *
     * @param array $data, e.g., ["created_at" => $value]
     * @return array ["createdAt" => $value]
     */
    public function databaseToDisplay(array $data = []): array
    {
        $output = [];

        foreach ($data as $key => $value) {
            if (isset($this->maps[$key])) {
                $output[$this->maps[$key]] = $value;
            }
        }

        return $output;
    }


    /**
     * displayToDatabase
     *
     * Maps the display names to the database column names.
     *
     * @param array $data, e.g., ["createdAt" => $value]
     * @return array ["created_at" => $value]
     */
    public function displayToDatabase(array $data = []): array
    {
        $output = [];
        $reversed = array_flip($this->maps);

        foreach ($data as $key => $value) {
            if (isset($reversed[$key])) {
                $column = $reversed[$key];
                $output[$column] = $value;
            }
        }

        return $output;
    }
} # class

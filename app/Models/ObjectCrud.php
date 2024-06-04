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

    # all objects are available here
    public static array $objects = [
        Collages::class,
        Conversations::class,
        Creators::class,
        Literature::class,
        Messages::class,
        Organizations::class,
        Requests::class,
        Roles::class,
        SiteLog::class,
        Tags::class,
        TorrentGroups::class,
        Torrents::class,
        Users::class,
        Wiki::class,
    ];


    /**
     * __construct
     *
     * @param int|string $id
     * @return void
     */
    public function __construct(int|string $id = null)
    {
        $this->read($id);
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

        # log the action
        #$this->log("create");
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
     * @param int|string $id
     * @return void
     */
    public function read(int|string $id = null): void
    {
        $app = App::go();

        # set $this->attributes to null if the object doesn't exist
        if (!$this->exists($id)) {
            $nullAttributes = [];
            foreach ($this->maps as $key => $value) {
                $nullAttributes[$key] = null;
            }

            $this->attributes = new RecursiveCollection($nullAttributes);
            return;
        }

        # try to find the object
        $column = $app->dbNew->determineIdentifier($id);
        $query = "select * from {$this->table} where {$column} = ? and deleted_at is null";
        $row = $app->dbNew->row($query, [$id]);

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
        $this->relationships = new RecursiveCollection($this->relationships());
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

        # log the action
        $this->log("update");
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

        # log the action
        $this->log("delete");
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


    /** relationships */


    /**
     * relationships
     *
     * Loads all the relationships as ["id" => "string, "type" => "string"].
     *
     * @return array
     */
    private function relationships(): array
    {
        $app = App::go();

        # loop through all the objects
        $relationships = [];
        foreach (self::$objects as $object) {
            # set the linking table
            $object = new $object();
            $linksTable = $object->table . "_links";

            # does the table exist?
            $query = "show tables like '{$linksTable}'";
            $good = $app->dbNew->single($query, []);

            if (!$good) {
                continue;
            }

            # get the relationships
            $query = "select objectId from {$linksTable} where contentId = ? and contentType = ?";
            $rows = $app->dbNew->multi($query, [$this->id, $this::$type]);

            # collect the data
            foreach ($rows as $row) {
                $relationships[$object::$type][] = [
                    "id" => $row["objectId"],
                    "type" => $object::$type,
                ];
            }
        }

        return $relationships;
    }


    /**
     * log
     *
     * Records a site log entry.
     *
     * @param string $action
     * @param ?string $description
     * @return void
     */
    public function log(string $action, ?string $description = null): void
    {
        return;

        /** */

        $app = App::go();

        if (!in_array($action, SiteLog::$allowedActions)) {
            throw new Exception("invalid action");
        }

        $data = [
            "userId" => $app->user->core["id"],
            "contentId" => $this->id,
            "contentType" => self::$type,
            "action" => $action,
            "description" => $description,
        ];

        $siteLog = new SiteLog();
        $siteLog->create($data);
    }


    /**
     * syncRelationships
     */
    public function syncRelationships()
    {
        $app = App::go();

        # loop through all the objects
        $relationships = [];
        foreach (self::$objects as $object) {
            # set the linking table
            $object = new $object();
            $linksTable = $object->table . "_links";

            # does the table exist?
            $query = "show tables like '{$linksTable}'";
            $good = $app->dbNew->single($query, []);

            if (!$good) {
                continue;
            }

            # get the relationships from the database
            $query = "select objectId from {$linksTable} where contentId = ? and contentType = ?";
            $ref = $app->dbNew->column($query, [$this->id, $this::$type]);

            if (empty($ref)) {
                continue;
            }

            # get the relationships from the object
            $relationships = $this->relationships->{$object::$type} ?? [];

            # remove the relationships that don't exist in the object
            foreach ($ref as $objectId) {
                $found = false;
                foreach ($relationships as $relationship) {
                    if ($relationship["id"] === $objectId) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $query = "update {$linksTable} set deleted_at = now() where contentId = ? and contentType = ? and objectId = ?";
                    $app->dbNew->do($query, [$this->id, $this::$type, $objectId]);
                }
            }

            # add the relationships that don't exist in the database
            foreach ($relationships as $relationship) {
                $found = false;
                foreach ($ref as $objectId) {
                    if ($relationship["id"] === $objectId) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $data = [
                        "contentId" => $this->id,
                        "contentType" => $this::$type,
                        "objectId" => $relationship["id"],
                    ];

                    $app->dbNew->upsert($linksTable, $data);
                }
            }

        }

        return $relationships;
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
            $this->relationships->{$object::$type} = [];
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
     * loadOrganizations
     *
     * @return void
     */
    public function loadOrganizations(): void
    {
        $this->loadRelationships(Organizations::class);
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
     * loadTags
     *
     * @return void
     */
    public function loadTags(): void
    {
        $this->loadRelationships(Tags::class);
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
     * @param int|string $id
     * @return bool
     */
    public function exists(int|string $id = null): bool
    {
        $app = App::go();

        $id ??= null;
        if (!$id) {
            return false;
        }

        # does the object exist?
        $column = $app->dbNew->determineIdentifier($id);
        $query = "select 1 from {$this->table} where {$column} = ? and deleted_at is null";

        $good = $app->dbNew->single($query, [$id]);
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

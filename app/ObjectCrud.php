<?php

declare(strict_types=1);


/**
 * Gazelle\ObjectCrud
 *
 * A simple way to perform CRUD operations on core site objects without going "full Eloquent."
 * This class is intended to be extended by other classes that represent a specific object.
 */

namespace Gazelle;

abstract class ObjectCrud
{
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
        $upsert = $app->dbNew->upsert($this->type, $transform);

        # map database => display
        $attributes = [];
        $transform = $this->databaseToDisplay($upsert);

        foreach ($transform as $key => $value) {
            $attributes[$key] = $value;
        }

        # use a RecursiveCollection not an array
        $this->id = $upsert["id"];
        $this->attributes = new RecursiveCollection($attributes);
    }


    /**
     * updateOrCreate
     */
    public function updateOrCreate(array $data = []): void
    {
        $this->create($data);
    }


    /**
     * read
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

            $this->id = null;
            $this->attributes = new RecursiveCollection($nullAttributes);

            return;
        }

        # try to find the object
        $column = $app->dbNew->determineIdentifier($identifier);
        $query = "select * from {$this->type} where {$column} = ? and deleted_at is null";
        $row = $app->dbNew->row($query, [$identifier]);

        # set the id
        $this->id = $row["id"];
        unset($row["id"]);

        # map database => display
        $attributes = [];
        $transform = $this->databaseToDisplay($row);

        foreach ($transform as $key => $value) {
            $attributes[$key] = $value ?? null;
        }

        # use a RecursiveCollection not an array
        $this->attributes = new RecursiveCollection($attributes);

        # check for relationships
        if (method_exists($this, "relationships")) {
            $this->relationships(); # method sets $this
        }
    }


    /**
     * update
     */
    public function update(int|string $identifier = null, array $data = []): void
    {
        $app = App::go();

        # does the object exist?
        if (!$this->exists($identifier)) {
            throw new Exception("can't update on {$this->type} where the {$column} is {$identifier}");
        }

        # map display => database
        $transform = $this->displayToDatabase($data);

        # add the identifier to the data
        $column = $app->dbNew->determineIdentifier($identifier);
        $transform[$column] = $identifier;

        # perform an upsert
        $upsert = $app->dbNew->upsert($this->type, $transform);
    }


    /**
     * delete
     */
    public function delete(int|string $identifier = null): void
    {
        $app = App::go();

        # determine the identifier
        $column = $app->dbNew->determineIdentifier($identifier);

        # does the object exist?
        if (!$this->exists($identifier)) {
            throw new Exception("can't delete from {$this->type} where the {$column} is {$identifier}");
        }

        # perform a soft delete
        $query = "update {$this->type} set deleted_at = now() where {$column} = ?";
        $app->dbNew->do($query, [$identifier]);
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

        if (!$identifier) {
            return false;
        }

        # does the object exist?
        $column = $app->dbNew->determineIdentifier($identifier);
        $query = "select 1 from {$this->type} where {$column} = ? and deleted_at is null";

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
    public function save()
    {
        $app = App::go();

        foreach ($this->maps as $key => $value) {
            $data[$key] = $this->attributes->$value;
        }

        $upsert = $app->dbNew->upsert($this->type, $data);
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

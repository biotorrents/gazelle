<?php

declare(strict_types=1);


/**
 * Collages
 */

class Collages
{
    # the object itself
    public $object = null;

    # ["database" => "display"]
    private $maps = [
        "uuid" => "uuid",
        "ID" => "id",
        "Name" => "title",
        "Description" => "description",
        "UserID" => "userId",
        "NumTorrents" => "torrentCount",
        "Deleted" => "deletedAt",
        "Locked" => "isLocked",
        "CategoryID" => "categoryId",
        "TagList" => "tagList",
        "MaxGroups" => "maxGroups",
        "MaxGroupsPerUser" => "maxGroupsPerUser",
        "Featured" => "isFeatured",
        "Subscribers" => "subscriberCount",
        "updated" => "updatedAt",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /**
     * __construct
     */
    public function __construct(int|string $identifier = null)
    {
        if ($identifier) {
            $this->object = $this->read($identifier);
            #return $this->read($identifier);
        }
    }


    /** crud */


    /**
     * create
     */
    public function create(array $data = [])
    {
        throw new \Exception("not implemented");
    }


    /**
     * read
     */
    public function read(int|string $identifier)
    {
        $app = \Gazelle\App::go();

        $column = $app->dbNew->determineIdentifier($identifier);

        $query = "select * from collages where {$column} = ?";
        $row = $app->dbNew->row($query, [$identifier]);

        if (empty($row)) {
            return [];
        }

        $translatedRow = [];
        foreach ($row as $column => $value) {
            # does the column exist in the map?
            if (isset($this->maps[$column])) {
                $outputLabel = $this->maps[$column];
                $translatedRow[$outputLabel] = $value;
            }
        }

        return $translatedRow;
    }


    /**
     * update
     */
    public function update(int|string $identifier, array $data = [])
    {
        throw new \Exception("not implemented");
    }


    /**
     * delete
     */
    public function delete(int|string $identifier)
    {
        throw new \Exception("not implemented");
    }


    /** legacy */


    /**
     * addSubscription
     */
    public static function addSubscription(int $collageId): void
    {
        $app = \Gazelle\App::go();

        $query = "update collages set subscribers = subscribers + 1 where id = ?";
        $app->dbNew->do($query, [$collageId]);
    }


    /**
     * subtractSubscription
     */
    public static function subtractSubscription(int $collageId): void
    {
        $app = \Gazelle\App::go();

        $query = "select subscribers from collages where id = ?";
        $subscriberCount = $app->dbNew->single($query, [$collageId]) ?? 0;

        if (empty($subscriberCount)) {
            return;
        }

        $query = "update collages set subscribers = subscribers - 1 where id = ?";
        $app->dbNew->do($query, [$collageId]);
    }


    /**
     * createPersonal
     */
    public static function createPersonal(): void
    {
        $app = \Gazelle\App::go();

        $query = "select count(id) from collages where userId = ? and categoryId = ? and deleted = ?";
        $collageCount = $app->dbNew->single($query, [$app->user->core["id"], 0, 0]) ?? 0;

        # todo: permissions are meh and this iss hardcoded
        $maxCollages = $app->user->permissions["MaxCollages"] ?? 2;
        if ($collageCount >= $maxCollages) {
            return;
        }

        # default title and description
        $title = "{$app->user->core["username"]}'s personal collage";
        $description = "Personal collage for {$app->user->core["username"]}";

        # database insert
        $query = "insert into collages (name, description, categoryId, userId) values (?, ?, ?, ?)";
        $app->dbNew->do($query, [ $title, $description, 0, $app->user->core["id"] ]);

        # redirect to new collage
        $collageId = $app->dbNew->lastInsertId();
        Http::redirect("/collages.php?id={$collageId}");
    }
}

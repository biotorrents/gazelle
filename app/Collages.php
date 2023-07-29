<?php

declare(strict_types=1);


/**
 * Collages
 */

class Collages
{
    # the object itself
    public $uuid;
    public $id;
    public $title;
    public $description;
    public $userId;
    public $torrentCount;
    #public $deletedAt;
    public $isLocked;
    public $categoryId;
    public $tagList;
    public $maxGroups;
    public $maxGroupsPerUser;
    public $isFeatured;
    public $subscriberCount;
    #public $updatedAt;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

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

    # cache settings
    private $cachePrefix = "collages:";
    private $cacheDuration = "1 day";


    /**
     * __construct
     */
    public function __construct(int|string $identifier = null)
    {
        if ($identifier) {
            $this->read($identifier);
            #$this->object = $this->read($identifier);
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

                # set $this here
                $this->{$outputLabel} = $value;
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


    /** subscriptions */


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
     * isSubscribed
     */
    public function isSubscribed(): bool
    {
        $app = \Gazelle\App::go();

        $query = "select 1 from users_collage_subs where userId = ? and collageId = ?";
        $isSubscribed = $app->dbNew->single($query, [ $app->user->core["id"], $this->id ]);

        return boolval($isSubscribed);
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


    /**
     * torrentGroups
     */
    public function torrentGroups(): array
    {
        $app = \Gazelle\App::go();

        $query = "select groupId from collages_torrents where collageId = ?";
        $groupIds = $app->dbNew->column("groupId", $query, [$this->id]);

        return Torrents::get_groups($groupIds);
    }


    /**
     * readStats
     *
     * Returns the stats for a collage.
     * Normally shown in the sidebar.
     *
     * @param int $limit
     * @return array
     */
    public function readStats(?int $limit = 10): array
    {
        $app = \Gazelle\App::go();

        # return cached if available
        $cacheKey = $this->cachePrefix . __FUNCTION__ . "-collageId-{$this->id}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # return array
        $return = [
            "creatorCount" => 0,
            "topCreators" => [], # [creatorId => count]

            "contributorCount" => 0,
            "topContributors" => [], # [userId => count]

            "tagCount" => 0,
            "topTags" => [], # [tagId => count]
        ];

        # select [groupId, userId]
        $query = "
            select collages_torrents.groupId, collages_torrents.userId from collages_torrents
            inner join torrents_group on torrents_group.id = collages_torrents.groupId
            where collages_torrents.collageId = ?
            order by collages_torrents.sort
        ";
        $ref = $app->dbNew->multi($query, [$this->id]);

        # loop through it
        foreach ($ref as $row) {
            # load the torrent group
            $torrentGroup = \Gazelle\Models\Group::find($row["groupId"]);

            # get the topCreators: needs refactor after creatorObjects
            foreach ($torrentGroup->creators as $creator) {
                $return["topCreators"][$creator->ArtistID] ??= 0;
                $return["topCreators"][$creator->ArtistID] += 1;
            }

            # get the topContributors
            $return["topContributors"][$row["userId"]] ??= 0;
            $return["topContributors"][$row["userId"]] += 1;

            # get the topTags
            $query = "select tagId, groupId from torrents_tags where groupId = ?";
            $topTags = $app->dbNew->column("tagId", $query, [$row["groupId"]]);

            foreach ($topTags as $tagId) {
                $return["topTags"][$tagId] ??= 0;
                $return["topTags"][$tagId] += 1;
            }
        }

        # get the counts
        $return["creatorCount"] = count($return["topCreators"]);
        $return["contributorCount"] = count($return["topContributors"]);
        $return["tagCount"] = count($return["topTags"]);

        # sort and slice
        arsort($return["topCreators"]);
        arsort($return["topContributors"]);
        arsort($return["topTags"]);

        $return["topCreators"] = array_slice($return["topCreators"], 0, $limit, true);
        $return["topContributors"] = array_slice($return["topContributors"], 0, $limit, true);
        $return["topTags"] = array_slice($return["topTags"], 0, $limit, true);

        # fun and done
        $app->cache->set($cacheKey, $return, $this->cacheDuration);
        return $return;
    }
} # class

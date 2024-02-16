<?php

declare(strict_types=1);


/**
 * Collages
 */

class Collages extends \Gazelle\ObjectCrud
{
    # database table
    public string $object = "collages";

    # object properties
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
    protected array $maps = [
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
    private string $cachePrefix = "collages:";
    private string $cacheDuration = "1 hour";


    /**
     * addSubscription
     *
     * @param int $collageId
     * @return void
     */
    public static function addSubscription(int $collageId): void
    {
        $app = \Gazelle\App::go();

        $query = "update collages set subscribers = subscribers + 1 where id = ?";
        $app->dbNew->do($query, [$collageId]);
    }


    /**
     * subtractSubscription
     *
     * @param int $collageId
     * @return void
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
     *
     * @return bool
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
     *
     * Creates a personal collage.
     *
     * @return array collage data
     */
    public static function createPersonal(): array
    {
        $app = \Gazelle\App::go();

        $query = "select count(id) from collages where userId = ? and categoryId = ? and deleted = ?";
        $collageCount = $app->dbNew->single($query, [$app->user->core["id"], 0, 0]) ?? 0;

        # todo: permissions are meh and this is hardcoded
        $maxCollages = $app->user->permissions["MaxCollages"] ?? 5;
        if ($collageCount >= $maxCollages) {
            throw new Exception("you may only create {$maxCollages} personal collages");
        }

        # default title and description
        $title = "{$app->user->core["username"]}'s personal collage #{$collageCount}";
        $description = "Personal collage for {$app->user->core["username"]}";

        # database insert
        $query = "insert into collages (name, description, categoryId, userId) values (?, ?, ?, ?)";
        $app->dbNew->do($query, [ $title, $description, 0, $app->user->core["id"] ]);

        # return the collage data
        $collageId = $app->dbNew->lastInsertId();

        return [
            "id" => $collageId,
            "name" => $title,
            "description" => $description,
        ];

        /*
        # redirect to new collage
        $collageId = $app->dbNew->lastInsertId();
        Http::redirect("/collages.php?id={$collageId}");
        */
    }


    /**
     * torrentGroups
     *
     * @return array
     */
    public function torrentGroups(): array
    {
        $app = \Gazelle\App::go();

        $query = "select groupId from collages_torrents where collageId = ?";
        $groupIds = $app->dbNew->column($query, [$this->id]);

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
        $ref ??= [];

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
            $query = "select tagId from torrents_tags where groupId = ?";
            $topTags = $app->dbNew->column($query, [ $row["groupId"] ]);

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


    /**
     * addCreator
     *
     * Taken from the sections, potentially useful for "workgroup feature pages."
     *
     * @param int $collageId
     * @param int $creatorId
     * @return void
     */
    public function addCreator($collageId, $creatorId): void
    {
        $app = \Gazelle\App::go();

        # sorting info
        $query = "select max(sort) from collages_artists where collageId = ?";
        $sort = $app->dbNew->single($query, [$collageId]);
        $sort += 10;

        # is the creator there?
        $query = "select artistId from collages_artists where collageId = ? and artistId = ?";
        $ref = $app->dbNew->single($query, [$collageId, $creatorId]);

        # nothing to do
        if ($ref) {
            return;
        }

        # add the creator
        $query = "insert ignore into collages_artists (collageId, artistId, sort, addedOn) values (?, ?, ?, now())";
        $app->dbNew->do($query, [$collageId, $creatorId, $sort]);

        # update the collages table
        $query = "update collages set numTorrents = numTorrents + 1, updated = now() where id = ?";
        $app->dbNew->do($query, [$collageId]);

        # clear user subscriptions
        $query = "select userId from users_collage_subs where collageId = ?";
        $ref = $app->dbNew->column($query, [$collageId]);

        foreach ($ref as $userId) {
            $app->cache->delete("collage_subs_user_new_{$userId}");
        }
    }
} # class

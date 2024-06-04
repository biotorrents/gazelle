<?php

declare(strict_types=1);


/**
 * Gazelle\Collages
 */

namespace Gazelle;

class Collages extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "collages"; # resource name
    protected ?string $table = "collages"; # database table

    # cache settings
    private string $cachePrefix = "collages:";
    private string $cacheDuration = "1 hour";

    # categories
    public static array $categories = [
        1 => "Personal",
        2 => "Thematic",
        3 => "Group picks",
        4 => "Staff picks",
    ];

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "categoryId" => "categoryId",
        "userId" => "userId",
        "title" => "title",
        "slug" => "slug",
        "description" => "description",
        "tags" => "tags", # json
        "torrentCount" => "torrentCount",
        "subscriberCount" => "subscriberCount",
        "maximumGroups" => "maximumGroups",
        "groupsPerUser" => "groupsPerUser",
        "isFeatured" => "isFeatured", # bool
        "isLocked" => "isLocked", # bool
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** crud */


    /**
     * read
     *
     * @param int|string $id
     * @return void
     */
    public function read(int|string $id = null): void
    {
        # parent method
        parent::read($id);

        # decode the boolean fields
        $this->attributes->isFeatured = boolval($this->attributes->isFeatured);
        $this->attributes->isLocked = boolval($this->attributes->isLocked);

        # decode the json fields
        $this->attributes->tags = json_decode($this->attributes->tags ?? "{}");
    }


    /**
     * delete
     *
     * @return void
     */
    public function delete(): void
    {
        $app = App::go();

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            /*
            # delete the collage's torrents
            $query = "update collages_links set deleted_at = now() where objectId = ?";
            $app->dbNew->do($query, [$this->id]);
            */

            # delete the subscriptions
            $query = "update subscriptions_collages set deleted_at = now() where collageId = ?";
            $app->dbNew->do($query, [$this->id]);

            # parent delete
            parent::delete();

            # write to the site log
            \Misc::write_log("collage {$this->id} was deleted by {$app->user->core["username"]}");

            # commit the transaction
            $app->dbNew->commit();
        } catch (\Throwable $e) {
            # rollback and rethrow
            $app->dbNew->rollBack();
            throw $e;
        }
    }


    /** methods */


    /**
     * createSubscription
     *
     * @param int $collageId
     * @return void
     */
    public static function createSubscription(int $collageId): void
    {
        $app = App::go();

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            # increment the subscriberCount
            $query = "update collages set subscriberCount = subscriberCount + 1 where id = ? and deleted_at is null";
            $app->dbNew->do($query, [$collageId]);

            # add the user's subscription
            $query = "insert ignore into subscriptions_collages (collageId, userId) values (?, ?)";
            $app->dbNew->do($query, [ $collageId, $app->user->core["id"] ]);

            # commit the transaction
            $app->dbNew->commit();
        } catch (\Throwable $e) {
            # rollback and rethrow
            $app->dbNew->rollBack();
            throw $e;
        }
    }


    /**
     * deleteSubscription
     *
     * @param int $collageId
     * @return void
     */
    public static function deleteSubscription(int $collageId): void
    {
        $app = App::go();

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            # get the subscriberCount
            $query = "select subscriberCount from collages where id = ?";
            $subscriberCount = $app->dbNew->single($query, [$collageId]) ?? 0;

            # decrement the subscriberCount
            $query = "update collages set subscriberCount = subscriberCount - 1 where id = ?";
            $app->dbNew->do($query, [$collageId]);

            # remove the user's subscription
            $query = "update subscriptions_collages set deleted_at = now() where collageId = ? and userId = ?";
            $app->dbNew->do($query, [ $collageId, $app->user->core["id"] ]);

            # commit the transaction
            $app->dbNew->commit();
        } catch (\Throwable $e) {
            # rollback and rethrow
            $app->dbNew->rollBack();
            throw $e;
        }
    }


    /**
     * isSubscribed
     *
     * @return bool
     */
    public function isSubscribed(): bool
    {
        $app = App::go();

        $query = "select 1 from subscriptions_collages where collageId = ? and userId = ?";
        $isSubscribed = $app->dbNew->single($query, [ $this->id, $app->user->core["id"] ]);

        return boolval($isSubscribed);
    }


    /**
     * createPersonal
     *
     * Creates a personal collage.
     *
     * @return self
     */
    public static function createPersonal(): self
    {
        $app = App::go();

        $query = "select count(id) from collages where categoryId = ? and userId = ? and deleted_at is null";
        $collageCount = $app->dbNew->single($query, [ 1, $app->user->core["id"] ]) ?? 0;

        # todo: permissions are meh and this is hardcoded
        $maxCollages = $app->user->permissions["MaxCollages"] ?? 5;
        if ($collageCount >= $maxCollages) {
            throw new Exception("you may only create {$maxCollages} personal collages");
        }

        # seed the data
        $data = [
            "id" => $app->dbNew->shortUuid(),
            "categoryId" => 1,
            "userId" => $app->user->core["id"],
            "title" => "{$app->user->core["username"]}'s personal collage #{$collageCount}",
            "description" => "Personal collage for {$app->user->core["username"]}",
        ];

        # database insert
        $query = "
            insert into collages (id, categoryId, userId, title, description)
            values (:id, :categoryId, :userId, :title, :description)
        ";

        $app->dbNew->do($query, $data);

        # return the collage data
        return new self($data["id"]);

        /*
        # redirect to the new collage
        $collageId = $app->dbNew->lastInsertId();
        Http::redirect("/collages/{$data["id"]}");
        */
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
        $app = App::go();

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
            select collages_links.contentId, collages_links.userId from collages_links
            inner join torrents_group on torrents_group.id = collages_links.contentId
            where collages_links.objectId = ? and collages_links.contentType = ?
        ";

        $ref = $app->dbNew->multi($query, [$this->id, \Gazelle\Collages::$type]);
        $ref ??= [];

        # loop through it
        foreach ($ref as $row) {
            # load the torrent group
            $torrentGroup = new TorrentGroups($row["groupId"]);

            # get the top creators
            foreach ($torrentGroup->relationships->creators as $creator) {
                $return["topCreators"][$creator->id] ??= 0;
                $return["topCreators"][$creator->id] += 1;
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
    /*
    public function addCreator($collageId, $creatorId): void
    {
        $app = App::go();

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
    */
} # class

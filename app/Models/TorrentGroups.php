<?php

declare(strict_types=1);


/**
 * Gazelle\TorrentGroups
 */

namespace Gazelle;

class TorrentGroups extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "torrentGroups"; # resource name
    protected ?string $table = "torrents_group"; # database table

    # cache settings
    private string $cachePrefix = "torrentGroups:";
    private string $cacheDuration = "1 hour";

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "categoryId" => "categoryId",
        "revisionId" => "revisionId",
        "identifier" => "identifier",
        "title" => "title",
        "subject" => "subject",
        "object" => "object",
        "workgroup" => "workgroup",
        "location" => "location",
        "year" => "year",
        "description" => "description",
        "picture" => "picture",
        "tags" => "tags", # json
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** crud */


    /**
     * create
     *
     * @param array $data
     * @return void
     */
    public function create(array $data): void
    {
        throw new Exception("not implemented");

        /** */

        # encode the json fields
        $data["tags"] = json_encode($data["tags"] ?? []);

        # parent create
        parent::create($data);
    }


    /**
     * read
     */
    public function read(int|string $identifier = null): void
    {
        # parent method
        parent::read($identifier);

        # decode the json fields
        $this->attributes->tags = json_decode($this->attributes->tags ?? []);
    }


    /**
     * update
     *
     * @param array $data
     * @return void
     */
    public function update(array $data): void
    {
        throw new Exception("not implemented");

        /** */

        # encode the json fields
        $data["tags"] = json_encode($data["tags"] ?? []);

        # parent update
        parent::update($data);
    }


    /**
     * delete
     *
     * @return void
     */
    public function delete(): void
    {
        throw new Exception("not implemented");

        /** */

        # parent delete
        parent::delete();
    }


    /** relationships */


    /**
     * relationships
     *
     * @return ?array
     */
    public function relationships(): ?array
    {
        return [
            Torrents::$type => $this->relatedTorrents(),
            Creators::$type => $this->relatedCreators(),
        ];
    }


    /**
     * relatedTorrents
     */
    public function relatedTorrents(): ?array
    {
        $app = App::go();

        $query = "select id from torrents where groupId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        if (!$ref) {
            return null;
        }

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Torrents::$type];
        }

        return $data;
    }


    /**
     * relatedCreators
     */
    public function relatedCreators(): ?array
    {
        $app = App::go();

        $query = "select creatorId from creators_groups where groupId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        if (!$ref) {
            return null;
        }

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Creators::$type];
        }

        return $data;
    }
} # class

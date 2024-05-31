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
    public function create(array $data = []): void
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
    public function read(int|string $id = null): void
    {
        $app = App::go();

        # parent method
        parent::read($id);

        # decode the json fields
        $this->attributes->tags = json_decode($this->attributes->tags ?? []);

        # add openai content if it exists
        $fields = [
            "jobId",
            "object",
            "model",
            "text",
            #"index",
            "logprobs",
            "finishReason",
            "promptTokens",
            "completionTokens",
            "totalTokens",
            "failCount",
            "type",
        ];

        $query = "select " . implode(", ", $fields) . " from openai where groupId = ? and type = ?";
        $ref = $app->dbNew->row($query, [$this->id, "summary"]);

        $this->attributes->openai = $ref;
    }


    /**
     * update
     *
     * @param array $data
     * @return void
     */
    public function update(array $data = []): void
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
            Collages::$type => $this->relatedCollages(),
            Creators::$type => $this->relatedCreators(),
            Literature::$type => $this->relatedLiterature(),
            Tags::$type => $this->relatedTags(),
            Torrents::$type => $this->relatedTorrents(),
        ];
    }


    /**
     * relatedCollages
     */
    private function relatedCollages(): ?array
    {
        $app = App::go();

        $query = "select collageId from collages_torrents where groupId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        if (!$ref) {
            return null;
        }

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Collages::$type];
        }

        return $data;
    }


    /**
     * relatedCreators
     */
    private function relatedCreators(): ?array
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


    /**
     * relatedLiterature
     */
    private function relatedLiterature(): ?array
    {
        $app = App::go();

        $query = "select literatureId from literature_groups where groupId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        if (!$ref) {
            return null;
        }

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Literature::$type];
        }

        return $data;
    }


    /**
     * relatedTags
     */
    private function relatedTags(): ?array
    {
        $app = App::go();

        $query = "select tagId from torrents_tags where groupId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        if (!$ref) {
            return null;
        }

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Tags::$type];
        }

        return $data;
    }




    /**
     * relatedTorrents
     */
    private function relatedTorrents(): ?array
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


    /** methods */


    /**
     * hydrateObject
     *
     * Loads the relationships as full objects for one level of depth.
     *
     * @return void
     */
    public function hydrateObject(): void
    {
        foreach ($this->relationships as $type => $data) {
            if (!$data) {
                continue;
            }

            match ($type) {
                Torrents::$type => $this->relationships[$type]["data"] = new Torrents($data["id"]),
                Creators::$type => $this->relationships[$type]["data"] = new Creators($data["id"]),
            };
        }
    }
} # class

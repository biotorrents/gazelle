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
        "slug" => "slug",
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
        $this->attributes->tags = json_decode($this->attributes->tags ?? "{}");

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
        $app = App::go();

        # can the owner update?
        if ($this->isOwner && $app->user->cant(["torrents" => "update"])) {
            throw new Exception("forbidden");
        }

        # can someone else moderate?
        if (!$this->isOwner && $app->user->cant(["torrents" => "moderate"])) {
            throw new Exception("forbidden");
        }

        # validate the data
        $data = [
            "id" => $this->id,
            "categoryId" => $this->categoryId,
            "revisionId" => $this->revisionId + 1,
            "identifier" => $data["identifier"] ?? $this->identifier,
            "title" => $data["title"] ?? $this->title,
            "subject" => $data["subject"] ?? $this->subject,
            "object" => $data["object"] ?? $this->object,
            "workgroup" => $data["workgroup"] ?? $this->workgroup,
            "location" => $data["location"] ?? $this->location,
            "year" => $data["year"] ?? $this->year,
            "description" => $data["description"] ?? $this->description,
            "picture" => $data["picture"] ?? $this->picture,
            "tags" => json_encode($data["tags"] ?? $this->tags),
        ];

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            # get an array of literatureIds
            $currentLiteratureIds = array_column($this->relatedLiterature(), "id");
            $proposedLiteratureIds = $data["literatureIds"] ?? [];

            if (!empty($proposedLiteratureIds)) {
                # remove the ones that are no longer in the list
                $removeLiteratureIds = array_diff($currentLiteratureIds, $proposedLiteratureIds);
                if (!empty($removeLiteratureIds)) {
                    $placeholders = implode(",", $removeLiteratureIds);
                    $query = "update literature_groups set deleted_at = now() where groupId = ? and literatureId in ({$placeholders})";
                    $app->dbNew->run($query, array_merge([$this->id], $removeLiteratureIds));
                }

                # add the ones that are new
                $addLiteratureIds = array_diff($proposedLiteratureIds, $currentLiteratureIds);
                if (!empty($addLiteratureIds)) {
                    $placeholders = implode(",", $addLiteratureIds);
                    $values = [];
                    foreach ($addLiteratureIds as $id) {
                        $values[] = $this->id;
                        $values[] = $id;
                    }

                    $query = "insert into literature_groups (groupId, literatureId) values $placeholders";
                    $app->dbNew->run($query, $values);
                }
            }

        } catch (\Throwable $e) {
            $app->dbNew->rollBack();
            throw new Exception($e->getMessage());
        }

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

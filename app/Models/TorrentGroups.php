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
        "category_id" => "categoryId",
        "title" => "title",
        "subject" => "subject",
        "object" => "object",
        "year" => "year",
        "workgroup" => "workgroup",
        "location" => "location",
        "identifier" => "identifier",
        "tag_list" => "tags",
        "revision_id" => "revisionId",
        "description" => "description",
        "picture" => "picture",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** crud */


    /**
     * read
     */
    public function read(int|string $identifier = null): void
    {
        # parent method
        parent::read($identifier);

        # tags
        $this->attributes->tags = explode(" ", $this->attributes->tags ?? "");
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

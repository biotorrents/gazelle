<?php

declare(strict_types=1);


/**
 * Gazelle\TorrentGroups
 */

namespace Gazelle;

class TorrentGroups extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?int $id = null; # primary key
    public string $type = "torrents_group"; # database table
    public ?RecursiveCollection $attributes = null;
    public ?RecursiveCollection $relationships = null;

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

    # cache settings
    private string $cachePrefix = "torrentGroups:";
    private string $cacheDuration = "1 hour";


    /**
     * relationships
     */
    public function relationships(): void
    {
        $app = App::go();

        $this->relationships = new RecursiveCollection([
            "torrents" => $this->getTorrents(),
            "creators" => $this->getCreators(),
        ]);
    }


    /**
     * getTorrents
     */
    public function getTorrents(): array
    {
        $app = App::go();

        $query = "select id from torrents where groupId = ?";
        $ref = $app->dbNew->multi($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = new Torrents($row["id"]);
        }

        return $data;
    }


    /**
     * getCreators
     */
    public function getCreators(): array
    {
        $app = App::go();

        $query = "select artistId from torrents_artists where groupId = ?";
        $ref = $app->dbNew->multi($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $query = "select * from artists_group where artistId = ?";
            $data[] = $app->dbNew->row($query, [ $row["artistId"] ]);

            # this needs to happen after creator objects
            #$data[] = new Creators($row["creatorId"]);
        }

        return $data;
    }



} # class

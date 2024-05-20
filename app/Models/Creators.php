<?php

declare(strict_types=1);


/**
 * Gazelle\Creators
 */

namespace Gazelle;

class Creators extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?int $id = null; # primary key
    public string $type = "creators"; # database table
    public ?RecursiveCollection $attributes = null;
    public ?RecursiveCollection $relationships = null;

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "orcid" => "orcid",
        "semanticScholarId" => "semanticScholarId",
        "name" => "name",
        "slug" => "slug",
        "description" => "description",
        "aliases" => "aliases", # json
        "affiliations" => "affiliations", # json
        "homepage" => "homepage",
        "paperCount" => "paperCount",
        "citationCount" => "citationCount",
        "hIndex" => "hIndex",
        "createdAt" => "createdAt",
        "updatedAt" => "updatedAt",
        "deletedAt" => "deletedAt",
    ];

    # cache settings
    private string $cachePrefix = "creators:";
    private string $cacheDuration = "1 hour";


    /**
     * read
     */
    public function read(int|string $identifier = null)
    {
        $app = App::go();

        # default read
        parent::read($identifier);

        # decode the json fields
        $this->attributes->aliases = json_decode($this->attributes->aliases);
        $this->attributes->affiliations = json_decode($this->attributes->affiliations);
    }


    /**
     * relationships
     */
    public function relationships(): void
    {
        $app = App::go();

        $this->relationships = new RecursiveCollection([
            "torrentGroups" => $this->getTorrentGroups(),
        ]);
    }


    /**
     * getTorrentGroups
     *
     * Gets the torrent groups for a creator.
     *
     * @return array
     */
    public function getTorrentGroups(): array
    {
        $app = App::go();

        $query = "select groupId from creators_groups where creatorId = ?";
        $ref = $app->dbNew->multi($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = new TorrentGroup($row["groupId"]);
        }

        return $data;
    }
} # class

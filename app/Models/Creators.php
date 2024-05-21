<?php

declare(strict_types=1);


/**
 * Gazelle\Creators
 */

namespace Gazelle;

class Creators extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "creators"; # resource name
    protected ?string $table = "creators"; # database table

    # cache settings
    private string $cachePrefix = "creators:";
    private string $cacheDuration = "1 hour";

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "orcid" => "orcid",
        "semanticScholarId" => "semanticScholarId",
        "name" => "name",
        "slug" => "slug",
        "description" => "description",
        "picture" => "picture",
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


    /** crud */


    /**
     * read
     */
    public function read(int|string $identifier = null): void
    {
        $app = App::go();

        # default read
        parent::read($identifier);

        # decode the json fields
        $this->attributes->aliases = json_decode($this->attributes->aliases ?? "");
        $this->attributes->affiliations = json_decode($this->attributes->affiliations ?? "");
    }


    /** relationships */


    /**
     * relationships
     *
     * @return ?array
     */
    public function relationships()
    {
        return [
            TorrentGroups::$type => $this->relatedTorrentGroups(),
            Requests::$type => $this->relatedRequests(),
        ];
    }


    /**
     * relatedTorrentGroups
     *
     * @return ?array
     */
    public function relatedTorrentGroups(): ?array
    {
        $app = App::go();

        $query = "select groupId from creators_groups where creatorId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        if (!$ref) {
            return null;
        }

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => TorrentGroups::$type];
        }

        return $data;
    }


    /**
     * relatedRequests
     *
     * @return ?array
     */
    public function relatedRequests(): ?array
    {
        $app = App::go();

        $query = "select requestId from creators_requests where creatorId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        if (!$ref) {
            return null;
        }

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Requests::$type];
        }

        return $data;
    }


    /** methods */


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
        $ref = $app->dbNew->column($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = new TorrentGroups($row);
        }

        return $data;
    }


    /**
     * getRequests
     *
     * Gets the requests for a creator.
     *
     * @return array
     */
    public function getRequests(): array
    {
        $app = App::go();

        $query = "select requestId from creators_requests where creatorId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => "requests"];
        }

        return $data;
    }


    /** */


    /**
     * hydrateFromSemanticScholar
     *
     * Searches for a creator in the Semantic Scholar database and hydrates the object.
     *
     * @return ?array what's written to the database
     */
    public function hydrateFromSemanticScholar(): ?array
    {
        $app = App::go();

        $semanticScholar = new SemanticScholar();
        $encodedName = urlencode($this->attributes->name);
        $response = $semanticScholar->search($encodedName, "authors");

        if (empty($response["data"])) {
            # increment the failCount and return
            $query = "update creators set failCount = failCount + 1 where id = ?";
            $app->dbNew->do($query, [$this->id]);

            return null;
        }

        # sort the array by hIndex descending
        $highestIndex = 0;
        foreach ($response["data"] as $key => $value) {
            if ($value["hIndex"] > $response["data"][$highestIndex]["hIndex"]) {
                $highestIndex = $key;
            }
        }

        # we have the canonical record
        $canonicalCreator = $response["data"][$highestIndex];

        # prepare the data for the database
        $data = [
            "semanticScholarId" => $canonicalCreator["authorId"] ?? null,
            "name" => $canonicalCreator["name"] ?? null,
            "slug" => \Illuminate\Support\Str::slug($canonicalCreator["name"] ?? null),
            "aliases" => json_encode($canonicalCreator["aliases"] ?? null),
            "affiliations" => json_encode($canonicalCreator["affiliations"] ?? null),
            "homepage" => $canonicalCreator["homepage"] ?? null,
            "paperCount" => $canonicalCreator["paperCount"] ?? null,
            "citationCount" => $canonicalCreator["citationCount"] ?? null,
            "hIndex" => $canonicalCreator["hIndex"] ?? null,
        ];

        # now, save it and return
        $this->update($this->id, $data);

        return $data;
    }


    /**
     * stats
     */
    /*
    public function stats(): array
    {
        $app = App::go();

        # start collecting data
        $data = [];

        # get the number of requests
        $query = "select count(*) from creators_requests where creatorId = ?";
        $data["requestCount"] = $app->dbNew->single($query, [$this->id]);

        # get the number of torrent groups
        $query = "select count(*) from creators_groups where creatorId = ?";
        $data["torrentGroupCount"] = $app->dbNew->single($query, [$this->id]);

        return $data;
    }
    */
} # class

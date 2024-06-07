<?php

declare(strict_types=1);


/**
 * Gazelle\Publications
 */

namespace Gazelle;

class Publications extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "publications"; # resource name
    protected ?string $table = "publications"; # database table

    # cache settings
    private string $cachePrefix = "publications:";
    private string $cacheDuration = "1 hour";

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "userId" => "userId",
        "issn" => "issn",
        "openAlexId" => "openAlexId",
        "wikidataId" => "wikidataId",
        "title" => "title",
        "slug" => "slug",
        "homepage" => "homepage",
        "picture" => "picture",
        "isOpenAccess" => "isOpenAccess", # bool
        "currentDoiCount" => "currentDoiCount",
        "backfileDoiCount" => "backfileDoiCount",
        "totalDoiCount" => "totalDoiCount",
        "summaryStats" => "summaryStats", # json
        "topics" => "topics", # json
        "concepts" => "concepts", # json
        "coverage" => "coverage", # json
        "flags" => "flags", # json
        "countsByYear" => "countsByYear", # json
        "doisIssuedByYear" => "doisIssuedByYear", # json
        "degreesOfSeparation" => "degreesOfSeparation",
        "failCount" => "failCount",
        "updatedById" => "updatedById",
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
        $app = App::go();

        # parent read
        parent::read($id);

        # decode the boolean fields
        $this->attributes->isOpenAccess = boolval($this->attributes->isOpenAccess ?? false);

        # decode the json fields
        $this->attributes->summaryStats = json_decode($this->attributes->summaryStats ?? "[]");
        $this->attributes->topics = json_decode($this->attributes->topics ?? "[]");
        $this->attributes->concepts = json_decode($this->attributes->concepts ?? "[]");
        $this->attributes->coverage = json_decode($this->attributes->coverage ?? "[]");
        $this->attributes->flags = json_decode($this->attributes->flags ?? "[]");
        $this->attributes->countsByYear = json_decode($this->attributes->countsByYear ?? "[]");
        $this->attributes->doisIssuedByYear = json_decode($this->attributes->doisIssuedByYear ?? "[]");
    }
} # class

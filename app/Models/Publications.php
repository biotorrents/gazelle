<?php

declare(strict_types=1);


/**
 * Gazelle\Publications
 */

namespace Gazelle;

class Publications extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public static ?string $type = "publications"; # resource name
    protected ?string $table = "publications"; # database table

    # cache settings
    protected ?string $cachePrefix = "publications:";

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
} # class

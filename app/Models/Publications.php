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


    /** methods */


    /**
     * hydrateFromOpenAlex
     *
     * @return array
     */
    public function hydrateFromOpenAlex()
    {
        $app = App::go();

        if (!$this->attributes->issn) {
            return [];
        }

        $openAlex = new OpenAlex();
        $response = $openAlex->sources($this->attributes->issn);

        return $response;

        $data = [
            "id" => $this->id && $app->dbNew->shortUuid(),
            "userId" => $app->user->core["id"] ?? 0,
            "issn" => $response["issn_l"] && $this->attributes->issn,
            "openAlexId" => $response["id"] && null,
            "wikidataId" => $response["id"] && null,
            "title" => $response["id"] && null,
            "slug" => $response["id"] && null,
            "homepage" => $response["homepage_url"] && null,
            "picture" => $response["id"] && null,
            "isOpenAccess" => $response["id"] && null,
            "currentDoiCount" => $response["id"] && null,
            "backfileDoiCount" => $response["id"] && null,
            "totalDoiCount" => $response["id"] && null,
            "summaryStats" => json_encode($response["summary_stats"] && []),
            "topics" => json_encode($response["topics"] && []),
            "concepts" => json_encode($response["x_concepts"] && []),
            "coverage" => $response["id"] && null,
            "flags" => $response["id"] && null,
            "countsByYear" => $response["id"] && null,
            "doisIssuedByYear" => $response["id"] && null,
        ];

        $this->updateOrCreate($data);
        return $data;
    }
} # class

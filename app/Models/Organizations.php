<?php

declare(strict_types=1);


/**
 * Gazelle\Organizations
 */

namespace Gazelle;

class Organizations extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "organizations"; # resource name
    protected ?string $table = "organizations"; # database table

    # cache settings
    private string $cachePrefix = "organizations:";
    private string $cacheDuration = "1 hour";

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "userId" => "userId",
        "grid" => "grid",
        "openAlexId" => "openAlexId",
        "rorId" => "rorId",
        "wikidataId" => "wikidataId",
        "name" => "name",
        "slug" => "slug",
        "acronym" => "acronym",
        "established" => "established",
        "status" => "status",
        "relationships" => "relationships", # json
        "repositories" => "repositories", # json
        "latitude" => "latitude",
        "longitude" => "longitude",
        "reverseGeocode" => "reverseGeocode",
        "country" => "country",
        "state" => "state",
        "city" => "city",
        "postalCode" => "postalCode",
        "type" => "type",
        "homepage" => "homepage",
        "picture" => "picture",
        "wikipedia" => "wikipedia",
        "summaryStats" => "summaryStats", # json
        "countsByYear" => "countsByYear", # json
        "topics" => "topics", # json
        "concepts" => "concepts", # json
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
        # parent read
        parent::read($id);

        # decode the json fields
        $this->attributes->relationships = json_decode($this->attributes->relationships ?? "[]", true);
        $this->attributes->repositories = json_decode($this->attributes->repositories ?? "[]", true);
        $this->attributes->summaryStats = json_decode($this->attributes->summaryStats ?? "[]", true);
        $this->attributes->countsByYear = json_decode($this->attributes->countsByYear ?? "[]", true);
        $this->attributes->topics = json_decode($this->attributes->topics ?? "[]", true);
        $this->attributes->concepts = json_decode($this->attributes->concepts ?? "[]", true);
    }


    /** methods */


    /**
     * hydrateFromOpenAlex
     *
     *
     */


    /**
     * hydrateFromRor
     *
     * Resolves a user-submitted name string to a ROR entry.
     */
    public function hydrateFromRor(): array
    {
        $app = App::go();

        # search the ROR database
        $ror = new ResearchOrganizationRegistry();
        $response = $ror->match($this->attributes->name);
        $organization = $response["organization"] ?? [];

        if (empty($response) || empty($organization)) {
            return [];
        }

        # populate the object properties
        $data = [
            "id" => $this->id ?? $app->dbNew->shortUuid(),
            "userId" => $app->user->core["id"] ?? 0,
            "grid" => $organization["external_ids"]["GRID"]["preferred"] ?? null,
            "rorId" => $organization["id"] ?? null,

            "name" => $organization["name"] ?? null,
            "slug" => $app->dbNew->slug($organization["name"] ?? null),
            "acronym" => $organization["acronyms"][0] ?? null,
            "established" => $organization["established"] ?? null,
            "status" => $organization["status"] ?? null,
            "relationships" => json_encode($organization["relationships"] ?? []),

            "latitude" => $organization["addresses"][0]["lat"] ?? null,
            "longitude" => $organization["addresses"][0]["lng"] ?? null,
            "country" => $organization["country"]["country_code"] ?? null,
            "state" => $organization["addresses"][0]["state_code"] ?? null,
            "city" => $organization["addresses"][0]["city"] ?? null,
            "postalCode" => $organization["addresses"][0]["postcode"] ?? null,

            "type" => strtolower($organization["types"][0] ?? null),
            "homepage" => $organization["links"][0] ?? null,
            "wikipedia" => $organization["wikipedia_url"] ?? null,
        ];

        # bonus: do a reverse geocode lookup
        $data["reverseGeocode"] ??= null;
        if ($data["latitude"] && $data["longitude"]) {
            $data["reverseGeocode"] = $ror->reverseGeocode($data["latitude"], $data["longitude"]);
        }

        $this->updateOrCreate($data);
        return $data;
    }


    /**
     * supplementFromOpenAlex
     *
     * Adds extra info to the organizations table from the OpenAlex database.
     *
     * @return array
     */
    public function supplementFromOpenAlex(): array
    {
        $app = App::go();

        if (!$this->attributes->rorId) {
            return [];
        }

        # search the OpenAlex database
        $openAlex = new OpenAlex();
        $response = $openAlex->institutions($this->attributes->rorId);

        $data = [
            "id" => $this->id,
            "userId" => $app->user->core["id"] ?? 0,
            #"grid" => $response["grid"] ?? null,
            "openAlexId" => $response["id"] ?? null,
            #"rorId" => $response["rorId"] ?? null,
            "wikidataId" => $response["ids"]["wikidata"] ?? null,
            "name" => $response["display_name"] ?? null,
            "slug" => $app->dbNew->slug($organization["display_name"] ?? null),
            #"acronym" => $response["acronym"] ?? null,
            #"established" => $response["established"] ?? null,
            #"status" => $response["status"] ?? null,
            #"relationships" => $response["relationships"] ?? null,
            "repositories" => json_encode($response["repositories"] ?? []),
            #"latitude" => $response["latitude"] ?? null,
            #"longitude" => $response["longitude"] ?? null,
            #"reverseGeocode" => $response["reverseGeocode"] ?? null,
            #"country" => $response["country"] ?? null,
            #"state" => $response["state"] ?? null,
            #"city" => $response["city"] ?? null,
            #"postalCode" => $response["postalCode"] ?? null,
            #"type" => $response["type"] ?? null,
            #"homepage" => $response["homepage"] ?? null,
            "picture" => $response["image_url"] ?? null,
            #"wikipedia" => $response["wikipedia"] ?? null,
            "summaryStats" => json_encode($response["summary_stats"] ?? []),
            "countsByYear" => json_encode($response["counts_by_year"] ?? []),
            "topics" => json_encode($response["topics"] ?? []),
            "concepts" => json_encode($response["x_concepts"] ?? []),
        ];

        $this->updateOrCreate($data);
        return $data;
    }
} # class

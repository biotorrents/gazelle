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
        "rorId" => "rorId",
        "grid" => "grid",
        "userId" => "userId",
        "name" => "name",
        "acronym" => "acronym",
        "established" => "established",
        "status" => "status",
        "relationships" => "relationships", # json
        "latitude" => "latitude",
        "longitude" => "longitude",
        "reverseGeocode" => "reverseGeocode",
        "country" => "country",
        "state" => "state",
        "city" => "city",
        "postalCode" => "postalCode",
        "type" => "type",
        "homepage" => "homepage",
        "wikipedia" => "wikipedia",
        "failCount" => "failCount",
        "degreesOfSeparation" => "degreesOfSeparation",
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
        $this->attributes->relationships = json_decode($this->attributes->relationships ?? "{}");
    }


    /** methods */


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
            "rorId" => $organization["id"] ?? null,
            "grid" => $organization["external_ids"]["GRID"]["preferred"] ?? null,
            "userId" => $app->user->core["id"] ?? 0,

            "name" => $organization["name"] ?? null,
            "acronym" => $organization["acronyms"][0] ?? null,
            "established" => $organization["established"] ?? null,
            "status" => $organization["status"] ?? null,
            "relationships" => json_encode($organization["relationships"] ?? null),

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

        # parent updateOrCreate
        parent::updateOrCreate($data);

        return $data;
    }
} # class

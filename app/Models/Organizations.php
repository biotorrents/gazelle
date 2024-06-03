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
        "name" => "name",
        "acronym" => "acronym",
        "established" => "established",
        "status" => "status",
        "relationships" => "relationships", # json
        "latitude" => "latitude",
        "longitude" => "longitude",
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
} # class

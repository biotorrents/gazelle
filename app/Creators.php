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

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
    ];

    # cache settings
    private string $cachePrefix = "creators:";
    private string $cacheDuration = "1 hour";


    /**
     * create
     */
    public function create()
    {
        throw new Exception("not implemented");
    }
} # class

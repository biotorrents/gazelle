<?php

declare(strict_types=1);


/**
 * Gazelle\Models\TorrentGroup
 */

namespace Gazelle\Models;

class TorrentGroup extends Base
{
    # https://laravel.com/docs/master/eloquent#table-names
    protected $table = "torrents_group";

    # https://laravel.com/docs/master/eloquent#primary-keys
    protected $primaryKey = "id";
    public $incrementing = false;

    # https://laravel.com/docs/master/eloquent#mass-assignment
    protected $guarded = [];


    /** relationships */


    /**
     * torrents
     */
    public function torrents()
    {
        return $this->hasMany(Torrent::class, "groupId", "id");
    }


    /** methods */


    /**
     * toJsonApi
     *
     * @return array
     */
    public function toJsonApi(): array
    {
        return [
            "id" => $this->id,
            "type" => "torrentGroups",
            "attributes" => [
                "categoryId" => $this->category_id,
                "title" => $this->title,
                "subject" => $this->subject,
                "object" => $this->object,
                "year" => $this->year,
                "workgroup" => $this->workgroup,
                "location" => $this->location,
                "identifier" => $this->identifier,
                "tags" => explode(" ", $this->tag_list),
                "revisionId" => $this->revision_id,
                "description" => $this->description,
                "picture" => $this->picture,
                "createdAt" => $this->created_at,
                "updatedAt" => $this->updated_at,
                "deletedAt" => $this->deleted_at,
            ],
            "relationships" => [
                "torrents" => [
                    "data" => $this->torrents->map(function ($torrent) {
                        return [
                            "id" => $torrent->id,
                            "type" => "torrents",
                        ];
                    }),
                ],
            ],
        ];
    }
} # class

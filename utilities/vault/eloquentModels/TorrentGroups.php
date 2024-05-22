<?php

declare(strict_types=1);


/**
 * Gazelle\Models\TorrentGroups
 */

namespace Gazelle\Models;

class TorrentGroups extends Base
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
                "categoryId" => $this->categoryId,
                "description" => $this->description,
                "identifier" => $this->identifier,
                "location" => $this->location,
                "object" => $this->object,
                "picture" => $this->picture,
                "revisionId" => $this->revision_id,
                "subject" => $this->subject,
                "tags" => explode(" ", $this->tags),
                "title" => $this->title,
                "workgroup" => $this->workgroup,
                "year" => $this->year,
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

<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Torrents
 */

namespace Gazelle\Models;

class Torrents extends Base
{
    # https://laravel.com/docs/master/eloquent#table-names
    protected $table = "torrents";

    # https://laravel.com/docs/master/eloquent#primary-keys
    protected $primaryKey = "id";
    public $incrementing = false;

    # https://laravel.com/docs/master/eloquent#mass-assignment
    protected $guarded = [];


    /** relationships */


    /**
     * torrentGroup
     */
    public function torrentGroup()
    {
        return $this->belongsTo(TorrentGroup::class, "GroupID", "id");
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
            "type" => "torrents",
            "attributes" => [
                "archive" => $this->archive,
                "balance" => $this->balance,
                "dataSize" => $this->Size,
                "description" => $this->Description,
                "fileCount" => $this->FileCount,
                "fileList" => $this->FileList,
                "filePath" => $this->FilePath,
                "format" => $this->container,
                "freeLeechType" => $this->FreeLeechType,
                "groupId" => $this->GroupID,
                "infoHash" => $this->info_hash,
                "isAnonymous" => $this->Anonymous,
                "isCensored" => $this->Censored,
                "isFreeTorrent" => $this->FreeTorrent,
                "lastAction" => $this->last_action,
                "lastReseedRequest" => $this->LastReseedRequest,
                "leecherCount" => $this->Leechers,
                "license" => $this->codec,
                "platform" => $this->media,
                "scope" => $this->resolution,
                "seederCount" => $this->Seeders,
                "snatchCount" => $this->Snatched,
                "userId" => $this->UserID,
                "version" => $this->version,
                "createdAt" => $this->created_at,
                "updatedAt" => $this->updated_at,
                "deletedAt" => $this->deleted_at,
            ],
            "relationships" => [
                "torrentGroups" => [
                    "data" => [
                        "id" => $this->GroupID,
                        "type" => "torrentGroups",
                    ],
                ],
            ],
        ];
    }
} # class

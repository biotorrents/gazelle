<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Torrent
 */

namespace Gazelle\Models;

class Torrent extends Base
{
    # https://laravel.com/docs/master/eloquent#table-names
    protected $table = "torrents";

    # https://laravel.com/docs/master/eloquent#primary-keys
    protected $primaryKey = "ID";
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
            "id" => $this->ID,
            "type" => "torrents",
            "attributes" => [
                "groupId" => $this->GroupID,
                "userId" => $this->UserID,
                "platform" => $this->media,
                "format" => $this->container,
                "license" => $this->codec,
                "scope" => $this->resolution,
                "version" => $this->version,
                "isCensored" => $this->Censored,
                "isAnonymous" => $this->Anonymous,
                "infoHash" => $this->info_hash,
                "fileCount" => $this->FileCount,
                "fileList" => $this->FileList,
                "filePath" => $this->FilePath,
                "dataSize" => $this->Size,
                "leecherCount" => $this->Leechers,
                "seederCount" => $this->Seeders,
                "lastAction" => $this->last_action,
                "isFreeTorrent" => $this->FreeTorrent,
                "freeLeechType" => $this->FreeLeechType,
                "description" => $this->Description,
                "snatchCount" => $this->Snatched,
                "balance" => $this->balance,
                "lastReseedRequest" => $this->LastReseedRequest,
                "archive" => $this->archive,
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

<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Torrent
 */

namespace Gazelle\Models;

class Torrent extends Base
{
    # the table associated with the model
    protected $table = "torrents";

    # the primary key associated with the table
    protected $primaryKey = "ID";

    # the attributes that aren't mass assignable
    protected $guarded = ["ID", "uuid", "info_hash"];


    /** relationships */


    /**
     * group
     */
    public function group()
    {
        return $this->belongsTo(Group::class, "id", "groupId");
    }
} # class

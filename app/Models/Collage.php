<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Collage
 */

namespace Gazelle\Models;

class Collage extends Base
{
    # https://laravel.com/docs/master/eloquent#uuid-and-ulid-keys
    #use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    # the table associated with the model
    protected $table = "collages";

    # the primary key associated with the table
    protected $primaryKey = "ID";

    # the attributes that aren't mass assignable
    protected $guarded = ["ID"];


    /** relationships */


    /**
     * groups
     */
    public function groups()
    {
        return $this->hasMany(Torrent::class, "collages_torrents", "collageId", "groupId");
    }
} # class

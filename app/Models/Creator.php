<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Creator
 */

namespace Gazelle\Models;

class Creator extends Base
{
    # https://laravel.com/docs/master/eloquent#uuid-and-ulid-keys
    #use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    # the table associated with the model
    protected $table = "torrents_artists"; # or artists_group?
    #protected $table = "creators";

    # the primary key associated with the table
    protected $primaryKey = "ArtistID";
    #protected $primaryKey = "id";

    # the attributes that aren't mass assignable
    #protected $guarded = ["id"];


    /** relationships */


    /**
     * groups
     */
    public function groups()
    {
        return $this->hasMany(Group::class, "ArtistID", "groupId");
    }
} # class

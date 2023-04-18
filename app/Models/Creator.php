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
    protected $table = "creators";

    # the primary key associated with the table
    protected $primaryKey = "id";

    # indicates if the model should be timestamped
    public $timestamps = false;

    # the attributes that aren't mass assignable
    protected $guarded = ["id"];


    /** relationships */


    /**
     * groups
     */
    public function groups()
    {
        return $this->hasMany(Group::class, "id", "groupId");
    }
} # class

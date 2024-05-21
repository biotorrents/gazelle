<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Collage
 */

namespace Gazelle\Models;

class Collage extends Base
{
    # https://laravel.com/docs/master/eloquent#table-names
    protected $table = "collages";

    # https://laravel.com/docs/master/eloquent#primary-keys
    protected $primaryKey = "ID";
    public $incrementing = false;

    # https://laravel.com/docs/master/eloquent#mass-assignment
    protected $guarded = [];


    /** relationships */


    /**
     * torrentGroups
     */
    public function torrentGroups()
    {
        return $this->hasMany(TorrentGroup::class, "GroupID", "id");
    }


    /** methods */


    /**
     * toJsonApi
     *
     * @return array
     */
    public function toJsonApi(): array
    {
    }
} # class

<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Group
 */

namespace Gazelle\Models;

class Group extends Base
{
    # the table associated with the model
    protected $table = "torrents_group";

    # the primary key associated with the table
    protected $primaryKey = "id";

    # the attributes that aren't mass assignable
    protected $guarded = ["id"];


    /** relationships */


    /**
     * torrents
     */
    public function torrents()
    {
        return $this->hasMany(Torrent::class, "groupId", "id");
    }


    /**
     * creators
     *
     * todo: depends on creatorObjects merge
     */
    public function creators()
    {
        return $this->hasMany(Creator::class, "groupId", "id");
    }


    /**
     * tags
     *
     * todo: need to avoid making models of everything
     */
    /*
    public function tags()
    {
        return $this->hasMany(Tag::class, "groupId", "id");
    }
    */
} # class

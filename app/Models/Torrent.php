<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Torrent
 */

namespace Gazelle\Models;

class Torrent extends Base
{
    # https://laravel.com/docs/master/eloquent#uuid-and-ulid-keys
    #use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    # the table associated with the model
    protected $table = "torrents";

    # the primary key associated with the table
    protected $primaryKey = "ID";

    # indicates if the model should be timestamped
    public $timestamps = false;

    # the attributes that aren't mass assignable
    protected $guarded = ["ID", "info_hash"];
} # class

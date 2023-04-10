<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Request
 */

namespace Gazelle\Models;

class Request extends Base
{
    # https://laravel.com/docs/master/eloquent#uuid-and-ulid-keys
    #use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    # the table associated with the model
    protected $table = "requests";

    # the primary key associated with the table
    protected $primaryKey = "ID";

    # indicates if the model should be timestamped
    public $timestamps = false;

    # the attributes that aren't mass assignable
    protected $guarded = ["ID"];
} # class

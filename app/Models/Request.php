<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Request
 */

namespace Gazelle\Models;

class Request extends Base
{
    # the table associated with the model
    protected $table = "requests";

    # the primary key associated with the table
    protected $primaryKey = "ID";

    # the attributes that aren't mass assignable
    protected $guarded = ["ID"];
} # class

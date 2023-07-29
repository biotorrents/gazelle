<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Tag
 */

namespace Gazelle\Models;

class Tag extends Base
{
    # the table associated with the model
    protected $table = "tags";

    # the primary key associated with the table
    protected $primaryKey = "ID";

    # the attributes that aren't mass assignable
    protected $guarded = ["ID", "uuid"];
} # class

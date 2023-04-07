<?php

declare(strict_types=1);


/**
 * Gazelle\Models\Base
 *
 * Eloquent model wrapper class.
 *
 * @see https://laravel.com/docs/master/eloquent
 */

namespace Gazelle\Models;

class Base extends \Illuminate\Database\Eloquent\Model
{
    # https://laravel.com/docs/master/eloquent#soft-deleting
    #use \Illuminate\Database\Eloquent\SoftDeletes;

    # eloquent capsule
    public $eloquent = null;


    /**
     * __construct
     */
    public function __construct(array $options = [])
    {
        $app = \Gazelle\App::go();

        try {
            $this->eloquent = new \Illuminate\Database\Capsule\Manager();

            $this->eloquent->addConnection([
                "driver" => "mysql",
                "host" => $app->env->getPriv("sqlHost"),
                "database" => $app->env->getPriv("sqlDatabase"),
                "username" => $app->env->getPriv("sqlUsername"),
                "password" => $app->env->getPriv("sqlPassphrase"),
                "charset" => "utf8mb4",
                "collation" => "utf8mb4_unicode_ci",
                "prefix" => "",
            ]);

            $this->eloquent->setAsGlobal();
            $this->eloquent->bootEloquent();
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }
} # class

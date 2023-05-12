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
     *
     * Create a new Eloquent model instance.
     * Defaults to the source database.
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        $app = \Gazelle\App::go();

        # database variables
        $source = $app->env->getPriv("databaseSource");
        $replicas = $app->env->getPriv("databaseReplicas");

        # default options
        $defaultOptions = [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ];

        # merge with any options passed
        $options = array_replace($defaultOptions, $options);

        try {
            # instantiate an eloquent capsule
            $this->eloquent = new \Illuminate\Database\Capsule\Manager();
            $this->eloquent->addConnection([
                "driver" => "mysql",
                "host" => $source["host"],
                "database" => $source["database"],
                "username" => $source["username"],
                "password" => $source["passphrase"],
                "charset" => $source["charset"],
                #"collation" => "utf8mb4_unicode_ci",
                "prefix" => "",
                "options" => $options,
            ]);

            $this->eloquent->setAsGlobal();
            $this->eloquent->bootEloquent();
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }
} # class

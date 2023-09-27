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
    use \Illuminate\Database\Eloquent\SoftDeletes;

    # https://laravel.com/docs/master/eloquent#uuid-and-ulid-keys
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

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
        $source = $app->env->private("databaseSource");
        $replicas = $app->env->private("databaseReplicas");

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


    /** uuid v7 */


    /**
     * newUniqueId
     *
     * Generate a new UUID for the model.
     *
     * @see https://laravel.com/docs/master/eloquent#uuid-and-ulid-keys
     */
    public function newUniqueId(): string
    {
        $app = \Gazelle\App::go();

        return $app->dbNew->uuid();
    }


    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     *
     * @see https://laravel.com/docs/master/eloquent#uuid-and-ulid-keys
     */
    public function uniqueIds(): array
    {
        return ["uuid"];
    }


    /** accessors */


    /**
     * uuid
     *
     * Get a string uuid.
     *
     * @see https://laravel.com/docs/master/eloquent-mutators#defining-an-accessor
     */
    protected function uuid(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        $app = \Gazelle\App::go();

        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (string $value) => $app->dbNew->stringUuid($value),
        );

    }
} # class

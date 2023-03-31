<?php

declare(strict_types=1);


/**
 * Database
 *
 * The blunt singleton, for your procedural code.
 * @see https://phpdelusions.net/pdo/pdo_wrapper
 *
 * Also uses the Laravel Eloquent ORM for migrations.
 * Maybe for models, I'm not really sold on this idea.
 * @see https://laravel.com/docs/9.x/eloquent
 */

class Database extends PDO
{
    # instance
    private static $instance;

    # pdo connection
    public $pdo = null;

    # eloquent capsule
    public $eloquent = null;

    # hash algo for cache keys
    private $algorithm = "sha3-512";

    # cache settings
    private $cachePrefix = "database_";
    private $cacheDuration = 60; # one minute


    /**
     * __functions
     */
    public function __construct()
    {
        return;
    }

    public function __clone()
    {
        return trigger_error(
            "clone not allowed",
            E_USER_ERROR
        );
    }

    public function __wakeup()
    {
        return trigger_error(
            "wakeup not allowed",
            E_USER_ERROR
        );
    }


    /**
     * go
     */
    public static function go(array $options = [])
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->factory($options);
        }

        return self::$instance;
    }


    /**
     * factory
     */
    private function factory(array $options = [])
    {
        $app = \Gazelle\App::go();

        # vars
        $host = $app->env->getPriv("sqlHost");
        $port = $app->env->getPriv("sqlPort");

        $username = $app->env->getPriv("sqlUsername");
        $password = $app->env->getPriv("sqlPassphrase");

        $db = $app->env->getPriv("sqlDatabase");
        $charset = "utf8mb4";

        # defaults
        $defaultOptions = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        # construct
        $options = array_replace($defaultOptions, $options);
        $dsn = "mysql:host={$host};dbname={$db};port={$port};charset={$charset}";

        # do it
        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), intval($e->getCode()));
        }

        /*
        # eloquent
        try {
            $this->eloquent = new Illuminate\Database\Capsule\Manager;
            $this->eloquent->addConnection([
                "driver" => "mysql",
                "host" => $host,
                "database" => $db,
                "username" => $username,
                "password" => $password,
                "charset" => $charset,
                "collation" => "utf8_unicode_ci",
                "prefix" => "",
            ]);

            $this->eloquent->setAsGlobal();
            $this->eloquent->bootEloquent();
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), intval($e->getCode()));
        }
        */
    }


    /**
     * do
     *
     * For update, insert, etc.
     */
    public function do(string $query, array $args = [])
    {
        $app = \Gazelle\App::go();

        # debug
        if ($app->env->dev) {
            /*
            $app->debug["database"]->log(
                $this->pdo->debugDumpParams()
            );
            */
        }

        # prepare
        $statement = $this->pdo->prepare($query);

        # no params
        if (empty($args)) {
            return $this->pdo->query($query);
        }

        # execute
        $statement->execute($args);

        # errors
        $errors = $this->pdo->errorInfo();
        if ($errors[0] !== "00000") {
            throw new Exception("{$errors[0]}: {$errors[2]}");
        }

        # good
        return $statement;
    }


    /**
     * single
     *
     * Gets a single value.
     */
    public function single(string $query, array $args = [])
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode([$query, $args]));
        if ($app->cacheOld->get_value($cacheKey) && !$app->env->dev) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $statement = $this->do($query, $args);
        $ref = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ref as $row) {
            foreach ($row as $key => $value) {
                $app->cacheOld->cache_value($cacheKey, $value, $this->cacheDuration);
                return $value;
            }
        }
    }


    /**
     * row
     *
     * Gets a single row.
     */
    public function row(string $query, array $args = [])
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode([$query, $args]));
        if ($app->cacheOld->get_value($cacheKey) && !$app->env->dev) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $statement = $this->do($query, $args);
        $ref = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ref as $row) {
            $app->cacheOld->cache_value($cacheKey, $row, $this->cacheDuration);
            return $row;
        }
    }


    /**
     * column
     *
     * Gets a single column.
     */
    public function column(string $query, string $column, array $args = [])
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode([$query, $args]));
        if ($app->cacheOld->get_value($cacheKey) && !$app->env->dev) {
            return $app->cacheOld->get_value($cacheKey);
        }

        /*
        $statement = $this->do($query, $args);
        $ref = $statement->fetchColumn();
        */

        $ref = $this->multi($query, $args);
        $ref = array_column($ref, $column);

        $app->cacheOld->cache_value($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /**
     * multi
     *
     * Gets all results.
     */
    public function multi(string $query, array $args = []): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode([$query, $args]));
        if ($app->cacheOld->get_value($cacheKey) && !$app->env->dev) {
            return $app->cacheOld->get_value($cacheKey);
        }

        $statement = $this->do($query, $args);
        $ref = $statement->fetchAll(PDO::FETCH_ASSOC);

        $app->cacheOld->cache_value($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /** statement metadata */


    /**
     * lastInsertId
     *
     * Gets the last inserted id.
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId();
    }


    /**
     * rowCount
     *
     * Gets the number of rows.
     */
    public function rowCount(string $query, array $args = []): int
    {
        $statement = $this->do($query, $args);
        $rowCount = $statement->rowCount();

        return $rowCount;
    }


    /**
     * columnCount
     *
     * Gets the number of columns.
     */
    public function columnCount(string $query, array $args = []): int
    {
        $statement = $this->do($query, $args);
        $columnCount = $statement->columnCount();

        return $columnCount;
    }


    /**
     * meta
     *
     * Gets the query metadata.
     */
    public function meta(PDOStatement $statement): array
    {
        $meta = [ "pdo" => [], "statement" => [] ];

        /** */

        # https://www.php.net/manual/en/pdo.errorcode.php
        $meta["pdo"]["errorCode"] = $this->pdo->errorCode();

        # https://www.php.net/manual/en/pdo.errorinfo.php
        $meta["pdo"]["errorInfo"] = $this->pdo->errorInfo();

        # https://www.php.net/manual/en/pdo.getavailabledrivers.php
        $meta["pdo"]["availableDrivers"] = $this->pdo->getAvailableDrivers();

        # https://www.php.net/manual/en/pdo.intransaction.php
        $meta["pdo"]["inTransaction"] = $this->pdo->inTransaction();

        # https://www.php.net/manual/en/pdo.lastinsertid.php
        $meta["pdo"]["lastInsertId"] = $this->pdo->lastInsertId();

        /** */

        # https://www.php.net/manual/en/pdostatement.columncount.php
        $meta["statement"]["columnCount"] = $statement->columnCount();

        # https://www.php.net/manual/en/pdostatement.debugdumpparams.php
        $meta["statement"]["debugDumpParams"] = $statement->debugDumpParams();

        # https://www.php.net/manual/en/pdostatement.errorcode.php
        $meta["statement"]["errorCode"] = $statement->errorCode();

        # https://www.php.net/manual/en/pdostatement.errorinfo.php
        $meta["statement"]["errorInfo"] = $statement->errorInfo();

        # https://www.php.net/manual/en/pdostatement.getcolumnmeta.php
        #$meta["statement"]["columnMeta"] = $statement->getColumnMeta($todo);

        # https://www.php.net/manual/en/pdostatement.rowcount.php
        $meta["statement"]["rowCount"] = $statement->rowCount();

        /** */

        return $meta;
    }


    /** transaction wrappers */


    /**
     * beginTransaction
     *
     * @see https://www.php.net/manual/en/pdo.begintransaction.php
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }


    /**
     * commit
     *
     * @see https://www.php.net/manual/en/pdo.commit.php
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }


    /**
     * rollBack
     *
     * @see https://www.php.net/manual/en/pdo.rollback.php
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
} # class

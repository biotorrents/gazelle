<?php

declare(strict_types=1);


/**
 * Gazelle\Database
 *
 * The blunt singleton, for your procedural code.
 *
 * @see https://phpdelusions.net/pdo/pdo_wrapper
 */

namespace Gazelle;

class Database extends \PDO
{
    # instance
    private static $instance;

    # pdo connection
    public $pdo = null;

    # hash algo for cache keys
    private $algorithm = "sha3-512";

    # cache settings
    private $cachePrefix = "database:";
    private $cacheDuration = "1 minute";


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
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ];

        # construct
        $options = array_replace($defaultOptions, $options);
        $dsn = "mysql:host={$host};dbname={$db};port={$port};charset={$charset}";

        # do it
        try {
            $this->pdo = new \PDO($dsn, $username, $password, $options);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /** identifiers */


    /**
     * getId
     *
     * Get the string representation of a binary uuid.
     *
     * @param string $binary uuid v7 binary
     * @return string uuid v7 string
     *
     * @see https://uuid.ramsey.dev/en/stable/rfc4122/version7.html
     * @see https://uuid.ramsey.dev/en/stable/database.html
     */
    public function getId(string $binary): string
    {
        return \Ramsey\Uuid\Uuid::fromBytes($binary)->toString();
    }


    /**
     * setId
     *
     * Generate a unique id suitable for a database key.
     *
     * @return string uuid v7 binary
     *
     * @see https://uuid.ramsey.dev/en/stable/rfc4122/version7.html
     * @see https://uuid.ramsey.dev/en/stable/database.html
     */
    public function setId(): string
    {
        return \Ramsey\Uuid\Uuid::uuid7()->getBytes();
    }


    /**
     * slug
     *
     * Generate a hashed slug from a string, e.g.,
     * $app->db->slug($title) => "my-title-d4dce101"
     *
     * @see https://laravel.com/api/master/Illuminate/Support/Str.html#method_words
     * @see https://laravel.com/api/master/Illuminate/Support/Str.html#method_slug
     *
     */
    public function slug(string $string): string
    {
        $string = \Illuminate\Support\Str::words($string, 10, "");
        $slug = \Illuminate\Support\Str::slug($string);

        $hash = bin2hex(random_bytes(4));
        $good = "{$slug}-{$hash}";

        # lazy af
        if (strlen($good) > 255) {
            throw new \Exception("slug too long");
        }

        return $good;
    }


    /** query operations */


    /**
     * do
     *
     * For update, insert, etc.
     */
    public function do(string $query, array $arguments = [])
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
        if (empty($arguments)) {
            return $this->pdo->query($query);
        }

        # execute
        $statement->execute($arguments);

        # errors
        $errors = $this->pdo->errorInfo();
        if ($errors[0] !== "00000") {
            throw new \Exception("{$errors[0]}: {$errors[2]}");
        }

        # good
        return $statement;
    }


    /**
     * single
     *
     * Gets a single value.
     */
    public function single(string $query, array $arguments = [])
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode([$query, $arguments]));
        if ($app->cache->get($cacheKey) && !$app->env->dev) {
            return $app->cache->get($cacheKey);
        }

        $statement = $this->do($query, $arguments);
        $ref = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($ref as $row) {
            # binary uuid v7 handling
            $row["uuid"] ??= null;
            if ($row["uuid"]) {
                $row["uuid"] = $this->getId($row["uuid"]);
            }

            foreach ($row as $key => $value) {
                $app->cache->set($cacheKey, $value, $this->cacheDuration);
                return $value;
            }
        }
    }


    /**
     * row
     *
     * Gets a single row.
     */
    public function row(string $query, array $arguments = [])
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode([$query, $arguments]));
        if ($app->cache->get($cacheKey) && !$app->env->dev) {
            return $app->cache->get($cacheKey);
        }

        $statement = $this->do($query, $arguments);
        $ref = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($ref as $row) {
            # binary uuid v7 handling
            $row["uuid"] ??= null;
            if ($row["uuid"]) {
                $row["uuid"] = $this->getId($row["uuid"]);
            }

            $app->cache->set($cacheKey, $row, $this->cacheDuration);
            return $row;
        }
    }


    /**
     * column
     *
     * Gets a single column.
     */
    public function column(string $query, string $column, array $arguments = [])
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode([$query, $arguments]));
        if ($app->cache->get($cacheKey) && !$app->env->dev) {
            return $app->cache->get($cacheKey);
        }

        /*
        $statement = $this->do($query, $arguments);
        $ref = $statement->fetchColumn();
        */

        $ref = $this->multi($query, $arguments);
        $ref = array_column($ref, $column);

        $app->cache->set($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /**
     * multi
     *
     * Gets all results.
     */
    public function multi(string $query, array $arguments = []): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode([$query, $arguments]));
        if ($app->cache->get($cacheKey) && !$app->env->dev) {
            return $app->cache->get($cacheKey);
        }

        $statement = $this->do($query, $arguments);
        $ref = $statement->fetchAll(\PDO::FETCH_ASSOC);

        # binary uuid v7 handling
        foreach ($ref as $key => $row) {
            $row["uuid"] ??= null;
            if ($row["uuid"]) {
                $ref[$key]["uuid"] = $this->getId($row["uuid"]);
            }
        }

        $app->cache->set($cacheKey, $ref, $this->cacheDuration);
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
    public function rowCount(string $query, array $arguments = []): int
    {
        $statement = $this->do($query, $arguments);
        $rowCount = $statement->rowCount();

        return $rowCount;
    }


    /**
     * columnCount
     *
     * Gets the number of columns.
     */
    public function columnCount(string $query, array $arguments = []): int
    {
        $statement = $this->do($query, $arguments);
        $columnCount = $statement->columnCount();

        return $columnCount;
    }


    /**
     * meta
     *
     * Gets the query metadata.
     */
    public function meta(\PDOStatement $statement): array
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

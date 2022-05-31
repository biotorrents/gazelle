<?php
declare(strict_types=1);

/**
 * Database
 *
 * The blunt singleton, for your procedural code.
 * @see https://phpdelusions.net/pdo/pdo_wrapper
 */

class Database extends PDO
{
    # instance
    private static $instance;

    # pdo connection
    public $pdo = null;

    # hash algo for cache keys
    private $algorithm = "sha3-512";

    # cache settings
    private $cachePrefix = "database_";
    private $cacheDuration = 300; # five minutes


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
    public static function go($options = [])
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->factory($options = []);
        }

        return self::$instance;
    }


    /**
     * factory
     */
    private function factory($options = [])
    {
        $app = App::go();

        # vars
        $host = $app->env->getPriv("SQL_HOST");
        $port = $app->env->getPriv("SQL_PORT");

        $username = $app->env->getPriv("SQL_USER");
        $password = $app->env->getPriv("SQL_PASS");

        $db = $app->env->getPriv("SQL_DB");
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
            throw new PDOException($e->getMessage(), intval($e->getCode()));
        }
    }


    /**
     * do
     *
     * For update, insert, etc.
     */
    public function do(string $query, array $args = [])
    {
        $app = App::go();

        /*
        # debug
        if ($app->env->DEV) {
            $app->debug["database"]->log(
                $this->pdo->debugDumpParams()
            );
        }
        */

        # prepare
        $statement = $this->pdo->prepare($query);

        /*
        # return cached if available
        $cacheKey = $this->cachePrefix . hash($this->algorithm, $statement);
        if ($app->cacheOld->get_value($cacheKey)) {
            return $app->cacheOld->get_value($cacheKey);
        }
        */

        # no params
        if (empty($args)) {
            #$app->cacheOld->cache_value($cacheKey, $query, $this->cacheDuration);
            return $this->pdo->query($query);
        }

        # execute
        $statement->execute($args);

        # errors
        $errors = $this->pdo->errorInfo();
        if ($errors[0] !== "00000") {
            throw new PDOException("{$errors[0]}: {$errors[2]}");
        }

        # good
        #$app->cacheOld->cache_value($cacheKey, $statement, $this->cacheDuration);
        return $statement;
    }


    /**
     * single
     *
     * Gets a single value.
     */
    public function single(string $query, array $args = [])
    {
        $statement = $this->do($query, $args);
        $single = $statement->fetchColumn();

        if (is_array($single)) {
            return array_shift($single);
        } else {
            return $single;
        }
    }


    /**
     * row
     *
     * Gets a single row.
     */
    public function row(string $query, array $args = [])
    {
        $statement = $this->do($query, $args);
        $row = $statement->fetch();

        return $row;
    }


    /**
     * column
     *
     * Gets a single column.
     */
    public function column(string $query, array $args = [])
    {
        $statement = $this->do($query, $args);
        $column = $statement->fetchColumn();

        return $column;
    }


    /**
     * multi
     *
     * Gets all results.
     */
    public function multi(string $query, array $args = []): array
    {
        $statement = $this->do($query, $args);
        $multi = $statement->fetchAll();

        return $multi;
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
     * Gets the column metadata.
     */
    public function meta(string $query, array $args = []): int
    {
        $statement = $this->do($query, $args);
        $meta = $statement->getColumnMeta();

        return $meta;
    }
} # class

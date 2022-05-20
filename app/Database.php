<?php
declare(strict_types=1);

/**
 * PDO
 *
 * The blunt singleton, for your procedural code.
 * @see https://phpdelusions.net/pdo/pdo_wrapper
 */
class Database
{
    # instance
    private $pdo;

    # hash algo for cache keys
    private static $algorithm = "sha3-512";

    # cache settings
    private $cachePrefix = 'stats_';
    private $cacheDuration = 3600;
    

    /**
     * __construct
     */
    public function __construct($options = [])
    {
        $ENV = ENV::go();

        $host = $ENV->getPriv("SQL_HOST");
        $port = $ENV->getPriv("SQL_PORT");

        $username = $ENV->getPriv("SQL_USER");
        $password = $ENV->getPriv("SQL_PASS");

        $db = $ENV->getPriv("SQL_DB");
        $charset = "utf8mb4";

        $default_options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];

        $options = array_replace($default_options, $options);
        $dsn = "mysql:host={$host};dbname={$db};port={$port};charset={$charset}";

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
        if (empty($args)) {
            return $this->pdo->query($query);
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute($args);

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
        $count = $statement->rowCount();

        return $count;
    }


    /**
     * columnCount
     *
     * Gets the number of columns.
     */
    public function columnCount(string $query, array $args = []): int
    {
        $statement = $this->do($query, $args);
        $count = $statement->columnCount();

        return $count;
    }


    /**
     * lastInsertId
     *
     * Gets the last auto-increment.
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }


    /**
     * errorInfo
     *
     * Gets the last error details.
     */
    public function errorInfo()
    {
        return $this->pdo->errorInfo();
    }
} # class

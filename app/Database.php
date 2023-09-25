<?php

declare(strict_types=1);


/**
 * Gazelle\Database
 *
 * The blunt singleton, for your procedural code.
 *
 * @see https://phpdelusions.net/pdo/pdo_wrapper
 * @see https://github.com/DoctorMcKay/php-mypdoms
 *
 * todo: consider updating this to use RecursiveCollection instances
 */

namespace Gazelle;

class Database extends \PDO
{
    # instance
    private static $instance;

    # database meta
    public $source = null;
    public $replicas = [];
    public $last = null;

    # cache settings
    private $cachePrefix = "database:";
    private $cacheDuration = "1 minute";

    # commands that should only hit the source
    private $sourceCommands = [
        "alter",
        "create",
        "delete",
        "drop",
        "insert",
        "load",
        "rename",
        "replace",
        "truncate",
        "update",
    ];


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


    /** */


    /**
     * go
     *
     * @param array $options the options to use
     * @return self
     */
    public static function go(array $options = []): self
    {
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->factory($options);
        }

        return self::$instance;
    }


    /**
     * factory
     *
     * @param array $options the options to use
     * @return void
     */
    private function factory(array $options = []): void
    {
        $app = \Gazelle\App::go();

        # don't cache on dev
        if ($app->env->dev) {
            $this->cacheDuration = "0 seconds";
        }

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

        # construct the source dsn
        $dsn = "mysql:host={$source->host};dbname={$source->database};port={$source->port};charset={$source->charset}";
        if ($source->socket) {
            $dsn = str_replace("port={$source->port}", "unix_socket={$source->socket}", $dsn);
        }

        try {
            # try to instantiate the source
            $this->source = new \PDO($dsn, $source->username, $source->passphrase, $options);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        # set the last host to the source
        $this->last = $this->source;

        # bail out if replicas aren't defined
        if (!$app->env->enableDatabaseReplication || empty($replicas)) {
            return;
        }

        # set up the replicas
        foreach ($replicas as $key => $replica) {
            # construct the replica dsn
            $dsn = "mysql:host={$replica->host};dbname={$replica->database};port={$replica->port};charset={$replica->charset}";
            if ($replica->socket) {
                $dsn = str_replace("port={$replica->port}", "unix_socket={$replica->socket}", $dsn);
            }

            try {
                # try to instantiate the replica
                $this->replicas[$key] = new \PDO($dsn, $replica->username, $replica->passphrase, $options);
            } catch (\Throwable $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }


    /**
     * determineHost
     *
     * Determine the host to use for a query.
     *
     * @param string $query the query to be executed
     * @param ?string $hostname the host to use
     * @return \PDO the host to use
     */
    private function determineHost(string $query = "", ?string $hostname = null): \PDO
    {
        # force certain queries to use the source
        if (preg_match("/^(" . implode("|", $this->sourceCommands) . ")/i", $query)) {
            $this->last = $this->source;
            return $this->last;
        }

        # check for a specific host
        if ($hostname === "source") {
            $this->last = $this->source;
            return $this->last;
        }

        if ($hostname === "last") {
            $this->last = $this->last;
            return $this->last;
        }

        if (in_array($hostname, array_keys($this->replicas))) {
            $this->last = $this->replicas[$hostname];
            return $this->last;
        }

        # pick a random replica
        if (!empty($this->replicas)) {
            $this->last = $this->replicas[array_rand($this->replicas)];
            return $this->last;
        }

        # default to the source
        $this->last = $this->source;
        return $this->last;
    }


    /** identifiers */


    /**
     * uuidShort
     *
     * Generate a short uuid.
     *
     * @return int e.g., 100455158982377479
     *
     * @see https://mariadb.com/kb/en/uuid_short/
     */
    public function uuidShort(): int
    {
        $query = "select uuid_short()";
        return $this->single($query);
    }


    /**
     * uuid
     *
     * Generate a unique id suitable for a database key.
     *
     * @return string uuid v7 binary
     *
     * @see https://uuid.ramsey.dev/en/stable/rfc4122/version7.html
     * @see https://uuid.ramsey.dev/en/stable/database.html
     */
    public function uuid(): string
    {
        return \Ramsey\Uuid\Uuid::uuid7()->getBytes();
    }


    /**
     * uuidBinary
     *
     * Gets the binary representation of a string uuid.
     *
     * @param string $string uuid v7 string
     * @return string uuid v7 binary
     *
     * @see https://uuid.ramsey.dev/en/stable/rfc4122/version7.html
     * @see https://uuid.ramsey.dev/en/stable/database.html
     */
    public function uuidBinary(string $string): string
    {
        return \Ramsey\Uuid\Uuid::fromString($string)->getBytes();
    }


    /**
     * binaryUuid
     */
    public function binaryUuid(string $string): string
    {
        return $this->uuidBinary($string);
    }


    /**
     * uuidString
     *
     * Get the string representation of a binary uuid.
     *
     * @param string $binary uuid v7 binary
     * @return string uuid v7 string
     *
     * @see https://uuid.ramsey.dev/en/stable/rfc4122/version7.html
     * @see https://uuid.ramsey.dev/en/stable/database.html
     */
    public function uuidString(string $binary): string
    {
        return \Ramsey\Uuid\Uuid::fromBytes($binary)->toString();
    }


    /**
     * stringUuid
     */
    public function stringUuid(string $binary): string
    {
        return $this->uuidString($binary);
    }


    /**
     * slug
     *
     * Generate a hashed slug from a string, e.g.,
     * $app->db->slug($title) => "my-title-d4dce101"
     *
     * @param string $string
     * @return string
     *
     * @see https://laravel.com/api/master/Illuminate/Support/Str.html#method_words
     * @see https://laravel.com/api/master/Illuminate/Support/Str.html#method_slug
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


    /**
     * determineIdentifier
     *
     * Determine the identifier to use for a query.
     * Used for finding stuff by id, uuid, or slug.
     *
     * @param int|string $id
     * @return string
     */
    public function determineIdentifier(int|string $id): string
    {
        $app = \Gazelle\App::go();

        if (is_int($id) || is_numeric($id)) {
            return "id";
        }

        # https://ihateregex.io/expr/uuid/
        if (is_string($id) && strlen($id) === 36 && preg_match("/{$app->env->regexUuid}/iD", $id)) {
            return "uuid";
        }

        # is it binary?
        if (\Gazelle\Text::isBinary($id) && strlen($id) === 16) {
            return "uuid";
        }

        # default slug
        return "slug";
    }


    /**
     * translateBinary
     *
     * Translates a binary field to a string representation.
     *
     * @param array $row single database row
     * @return array translated row
     */
    private function translateBinary(array $row): array
    {
        # uuid v7
        $row["uuid"] ??= null;
        if ($row["uuid"]) {
            $row["uuid"] = $this->uuidString($row["uuid"]);
        } else {
            unset($row["uuid"]);
        }

        # webauthn
        $row["aaguid"] ??= null;
        if ($row["aaguid"]) {
            $row["aaguid"] = $this->uuidString($row["aaguid"]);
        } else {
            unset($row["aaguid"]);
        }

        # peer_id
        $row["peer_id"] ??= null;
        if ($row["peer_id"]) {
            $row["peer_id"] = bin2hex($row["peer_id"]);
        } else {
            unset($row["peer_id"]);
        }

        # infoHash
        $row["infoHash"] ??= null;
        if ($row["infoHash"]) {
            $row["infoHash"] = bin2hex($row["infoHash"]);
        } else {
            unset($row["infoHash"]);
        }

        # legacy PascalCase
        $row["InfoHash"] ??= null;
        if ($row["InfoHash"]) {
            $row["InfoHash"] = bin2hex($row["InfoHash"]);
        } else {
            unset($row["InfoHash"]);
        }

        # info_hash
        $row["info_hash"] ??= null;
        if ($row["info_hash"]) {
            $row["info_hash"] = bin2hex($row["info_hash"]);
        } else {
            unset($row["info_hash"]);
        }

        return $row;
    }


    /** query operations */


    /**
     * do
     *
     * For update, insert, etc.
     *
     * @param string $query
     * @param array $arguments
     * @param ?string $hostname
     * @return \PDOStatement
     */
    public function do(string $query, array $arguments = [], ?string $hostname = null): \PDOStatement
    {
        $app = \Gazelle\App::go();

        # determine the host
        $host = $this->determineHost($query, $hostname);

        # prepare
        $statement = $host->prepare($query);

        # debug: before the first return
        if ($app->env->dev) {
            $app->debug["database"]->log(
                $this->meta()
            );
        }

        # no params
        if (empty($arguments)) {
            return $host->query($query);
        }

        # https://ihateregex.io/expr/uuid/
        foreach ($arguments as $key => $value) {
            if (is_string($value) && strlen($value) === 36 && preg_match("/{$app->env->regexUuid}/iD", $value)) {
                $arguments[$key] = $this->uuidBinary($value);
            }
        }

        # execute
        $statement->execute($arguments);

        # errors
        $errors = $host->errorInfo();
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
     *
     * @param string $query
     * @param array $arguments
     * @param ?string $hostname
     * @return mixed
     */
    public function single(string $query, array $arguments = [], ?string $hostname = null): mixed
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($app->env->cacheAlgorithm, strval(json_encode([$query, $arguments])));
        if ($app->cache->get($cacheKey) && !$app->env->dev) {
            return $app->cache->get($cacheKey);
        }

        $statement = $this->do($query, $arguments, $hostname);
        $ref = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($ref as $row) {
            # translate binary
            $row = $this->translateBinary($row);

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
     *
     * @param string $query
     * @param array $arguments
     * @param ?string $hostname
     * @return ?array
     */
    public function row(string $query, array $arguments = [], ?string $hostname = null): ?array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($app->env->cacheAlgorithm, strval(json_encode([$query, $arguments])));
        if ($app->cache->get($cacheKey) && !$app->env->dev) {
            return $app->cache->get($cacheKey);
        }

        $statement = $this->do($query, $arguments, $hostname);
        $ref = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($ref as $row) {
            # translate binary
            $row = $this->translateBinary($row);

            $app->cache->set($cacheKey, $row, $this->cacheDuration);
            return $row;
        }
    }


    /**
     * column
     *
     * Gets a single column.
     *
     * @param string $column
     * @param string $query
     * @param array $arguments
     * @param ?string $hostname
     * @return ?array
     */
    public function column(string $column, string $query, array $arguments = [], ?string $hostname = null): ?array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($app->env->cacheAlgorithm, strval(json_encode([$query, $arguments])));
        if ($app->cache->get($cacheKey) && !$app->env->dev) {
            return $app->cache->get($cacheKey);
        }

        /*
        $statement = $this->do($query, $arguments, $hostname);
        $ref = $statement->fetchColumn();
        */

        $ref = $this->multi($query, $arguments, $hostname);
        foreach ($ref as $key => $row) {
            # translate binary
            $ref[$key] = $this->translateBinary($row);
        }

        # technically incorrect variable name
        $row = array_column($ref, $column);

        $app->cache->set($cacheKey, $row, $this->cacheDuration);
        return $row;
    }


    /**
     * multi
     *
     * Gets all results.
     *
     * @param string $query
     * @param array $arguments
     * @param ?string $hostname
     * @return array
     */
    public function multi(string $query, array $arguments = [], ?string $hostname = null): array
    {
        $app = \Gazelle\App::go();

        $cacheKey = $this->cachePrefix . hash($app->env->cacheAlgorithm, strval(json_encode([$query, $arguments])));
        if ($app->cache->get($cacheKey) && !$app->env->dev) {
            return $app->cache->get($cacheKey);
        }

        $statement = $this->do($query, $arguments, $hostname);
        $ref = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($ref as $key => $row) {
            # translate binary
            $ref[$key] = $this->translateBinary($row);
        }

        $app->cache->set($cacheKey, $ref, $this->cacheDuration);
        return $ref;
    }


    /** pseudo orm */


    /**
     * upsert
     *
     * Mass assigns a data array to a table.
     * Similar to Eloquent's updateOrCreate.
     *
     * @param string $table
     * @param array $data
     * @return ?array
     */
    public function upsert(string $table, array $data = []): ?array
    {
        # extract the columns and values
        $columns = array_keys($data);
        $values = array_values($data);

        # comma-separated list of columns and named placeholders
        $insertColumns = implode(", ", $columns);
        $insertPlaceholders = ":" . implode(", :", $columns);

        # update column expressions with named placeholders
        $updateColumns = array_map(function ($column) {
            return "{$column} = :{$column}_update";
        }, $columns);

        # named placeholders for the update column values
        $updatePlaceholders = array_map(function ($column) {
            return ":{$column}_update";
        }, $columns);

        # construct the sql query string for the upsert operation
        /*
        $query = "
            replace into {$table} ({$insertColumns})
            values ({$insertPlaceholders})
        ";
        */

        $query = "
            insert into {$table} ({$insertColumns})
            values ({$insertPlaceholders})
            on duplicate key update " . implode(", ", $updateColumns);

        # merge the original data array with the update placeholders and their corresponding values
        $data = array_merge($data, array_combine($updatePlaceholders, $values));

        # execute the query with the data
        $statement = $this->do($query, $data);

        # return the newly created or updated row
        $lastInsertId = $this->lastInsertId(); # 0 if not inserted
        if (!empty($lastInsertId)) {
            $query = "select * from {$table} where id = ?";
            return $this->row($query, [$lastInsertId], "source");
        }

        # it was updated, resolve a key from the data
        foreach ($data as $key => $value) {
            if (in_array(strtolower(strval($key)), ["id", "uuid", "slug"])) {
                $column = $this->determineIdentifier($value);
                $query = "select * from {$table} where {$column} = ?";
                return $this->row($query, [$value], "source");
            }
        }

        # this should never happen
        #throw new \Exception("unable to upsert into {$table}");
    }


    /**
     * findOne
     *
     * Finds a single row by a set of contraints.
     *
     * @param string $table
     * @param array $data ["column" => "value"]
     * @return ?array
     */
    public function findOne(string $table, array $data = []): ?array
    {
        # important! trailing whitespace
        $query = "select * from {$table} where ";

        $parameters = [];
        foreach ($data as $key => $value) {
            $parameters[] = "{$key} = :{$key}";
        }

        $query .= implode(" and ", $parameters);

        return $this->row($query, $data);
    }


    /**
     * findAll
     *
     * Finds all rows by a set of contraints.
     *
     * @param string $table
     * @param array $data ["column" => "value"]
     * @return array
     */
    public function findAll(string $table, array $data = []): array
    {
        # important! trailing whitespace
        $query = "select * from {$table} where ";

        $parameters = [];
        foreach ($data as $key => $value) {
            $parameters[] = "{$key} = :{$key}";
        }

        $query .= implode(" and ", $parameters);

        return $this->multi($query, $data);
    }


    /** statement metadata */


    /**
     * lastInsertId
     *
     * Gets the last inserted id.
     * Defaults to the source.
     *
     * @param ?string $name
     * @return string|false
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->source->lastInsertId($name);
    }


    /**
     * rowCount
     *
     * Gets the number of rows.
     *
     * @param string $query
     * @param array $arguments
     * @param ?string $hostname
     * @return int
     */
    public function rowCount(string $query, array $arguments = [], ?string $hostname = null): int
    {
        $statement = $this->do($query, $arguments, $hostname);
        $rowCount = $statement->rowCount();

        return $rowCount;
    }


    /**
     * columnCount
     *
     * Gets the number of columns.
     *
     * @param string $query
     * @param array $arguments
     * @param ?string $hostname
     * @return int
     */
    public function columnCount(string $query, array $arguments = [], ?string $hostname = null): int
    {
        $statement = $this->do($query, $arguments, $hostname);
        $columnCount = $statement->columnCount();

        return $columnCount;
    }


    /**
     * meta
     *
     * Gets the query metadata.
     *
     * @param ?\PDOStatement $statement
     * @param ?string $hostname
     * @return array
     */
    public function meta(?\PDOStatement $statement = null, ?string $hostname = null): array
    {
        $host = $this->determineHost("", $hostname);
        $meta = [ "attributes" => [], "pdo" => [], "statement" => [] ];

        /** */

        # https://www.php.net/manual/en/pdo.getattribute.php
        $meta["attributes"] = [
            "autocommit" => $host->getAttribute(\PDO::ATTR_AUTOCOMMIT),
            "case" => $host->getAttribute(\PDO::ATTR_CASE),
            "clientVersion" => $host->getAttribute(\PDO::ATTR_CLIENT_VERSION),
            "connectionStatus" => $host->getAttribute(\PDO::ATTR_CONNECTION_STATUS),
            "driverName" => $host->getAttribute(\PDO::ATTR_DRIVER_NAME),
            "errmode" => $host->getAttribute(\PDO::ATTR_ERRMODE),
            "oracleNulls" => $host->getAttribute(\PDO::ATTR_ORACLE_NULLS),
            "persistent" => $host->getAttribute(\PDO::ATTR_PERSISTENT),
            "serverInfo" => $host->getAttribute(\PDO::ATTR_SERVER_INFO),
            "serverVersion" => $host->getAttribute(\PDO::ATTR_SERVER_VERSION),
        ];

        /** */

        # https://www.php.net/manual/en/pdo.errorcode.php
        $meta["pdo"]["errorCode"] = $host->errorCode();

        # https://www.php.net/manual/en/pdo.errorinfo.php
        $meta["pdo"]["errorInfo"] = $host->errorInfo();

        # https://www.php.net/manual/en/pdo.getavailabledrivers.php
        $meta["pdo"]["availableDrivers"] = $host->getAvailableDrivers();

        # https://www.php.net/manual/en/pdo.intransaction.php
        $meta["pdo"]["inTransaction"] = $host->inTransaction();

        # https://www.php.net/manual/en/pdo.lastinsertid.php
        $meta["pdo"]["lastInsertId"] = $host->lastInsertId();

        /** */

        if ($statement) {
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
        }

        /** */

        return $meta;
    }


    /** transaction wrappers */


    /**
     * beginTransaction
     *
     * Defaults to the source.
     *
     * @return bool
     *
     * @see https://www.php.net/manual/en/pdo.begintransaction.php
     */
    public function beginTransaction(): bool
    {
        return $this->source->beginTransaction();
    }


    /**
     * commit
     *
     * Defaults to the source.
     *
     * @return bool
     *
     * @see https://www.php.net/manual/en/pdo.commit.php
     */
    public function commit(): bool
    {
        return $this->source->commit();
    }


    /**
     * rollBack
     *
     * Defaults to the source.
     *
     * @return bool
     *
     * @see https://www.php.net/manual/en/pdo.rollback.php
     */
    public function rollBack(): bool
    {
        return $this->source->rollBack();
    }
} # class

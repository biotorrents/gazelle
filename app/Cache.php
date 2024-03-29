<?php

declare(strict_types=1);


/**
 * Gazelle\Cache
 *
 * A simple wrapper for the PhpRedis extension.
 * Uses serialized strings for Memcached compatibility.
 *
 * todo: transactions, maybe lists and hashes, etc.
 *
 * @see https://github.com/phpredis/phpredis
 * @see https://redis.io/docs/
 */

namespace Gazelle;

class Cache # extends \Redis
{
    # singleton
    private static $instance = null;

    # redis
    private $redis = null;

    # global default cache settings
    private $cachePrefix = "gazelle:"; # e.g., gazelle:development:stats:overview
    private $cacheDuration = 3600; # default one hour, if not otherwise specified

    # torrent group cache version
    public $groupVersion = "2023-04-01";

    # are we in a transaction?
    private $transactionMode = false;

    # are we running a cluster or a single server?
    private $clusterMode = null;


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
     *
     * @param array $options
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
     * @param array $options
     * @return void
     */
    private function factory(array $options = []): void
    {
        $app = \Gazelle\App::go();

        # https://github.com/phpredis/phpredis/blob/develop/cluster.md
        if ($app->env->redisClusterEnabled) {
            $this->redis = new \RedisCluster(
                null,
                $app->env->getPriv("redisNodes"),
                1.5,
                1.5,
                true,
                $app->env->getPriv("redisPassphrase")
            );

            # failure
            if (empty($this->info())) {
                throw new \Exception("unable to establish PhpRedis cache connection");
            }

            # set cluster mode
            $this->clusterMode = true;
        }

        # single redis server (not a cluster)
        if (!$app->env->redisClusterEnabled) {
            $this->redis = new \Redis();
            $this->redis->connect(
                $app->env->getPriv("redisHost"),
                $app->env->getPriv("redisPort"),
            );

            # authentication
            $redisUsername = $app->env->getPriv("redisUsername") ?? null;
            $redisPassphrase = $app->env->getPriv("redisPassphrase") ?? null;

            if ($redisUsername && $redisPassphrase) {
                $this->redis->auth([
                    $redisUsername,
                    $redisPassphrase,
                ]);
            }

            # failure
            if (!$this->redis->isConnected()) {
                throw new \Exception("unable to establish PhpRedis cache connection");
            }

            # set cluster mode
            $this->clusterMode = false;
        }

        # avoid key collisions
        if (!$app->env->dev) {
            $this->cachePrefix = $this->cachePrefix . "production:";
        } else {
            $this->cachePrefix = $this->cachePrefix . "development:";
        }

        # set options
        $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_JSON);
        $this->redis->setOption(\Redis::OPT_PREFIX, $this->cachePrefix);
    }


    /**
     * handleError
     *
     * Throw an exception, if we're so inclined.
     *
     * @param \Throwable $throwable
     * @return void
     */
    public function handleError(\Throwable $throwable): void
    {
        $app = \Gazelle\App::go();

        # log the error
        error_log($throwable->getMessage());

        # throw an exception
        if ($app->env->dev) {
            throw $throwable;
        }
    }


    /** crud */


    /**
     * set
     *
     * @param string $key the cache key
     * @param mixed $value the value to store
     * @param int|string $cacheDuration the expiration time
     * @return array the key/value pair
     *
     * @see https://github.com/phpredis/phpredis#set
     */
    public function set(string $key, mixed $value, int|string $cacheDuration = null): array
    {
        # we passed an integer
        if (is_int($cacheDuration)) {
            $cacheDuration = time() + $cacheDuration;
        }

        # we passed a string, god help us
        if (is_string($cacheDuration)) {
            try {
                $cacheDuration = \Carbon\Carbon::parse($cacheDuration)->timestamp;
            } catch (\Throwable $e) {
                $cacheDuration = null;
            }
        }

        # default expiration time in seconds
        if (!$cacheDuration) {
            $cacheDuration = time() + $this->cacheDuration;
        }

        /** */

        try {
            # store the value
            $this->redis->set($key, $value);

            # if cacheDuration = 0, persist the value
            if ($cacheDuration === 0) {
                $this->redis->persist($key);
            }

            # else, set the expiration time
            else {
                $this->redis->expireAt($key, $cacheDuration);
            }

            # return the input
            return [$key => $value];
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * get
     *
     * @param string $key the cache key
     * @return mixed the value, or false on failure
     *
     * @see https://github.com/phpredis/phpredis#get
     */
    public function get(string $key): mixed
    {
        try {
            return $this->redis->get($key);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * exists
     *
     * @param string $key the cache key
     * @return bool true if the key exists, false otherwise
     *
     * @see https://github.com/phpredis/phpredis#exists
     */
    public function exists(string $key): bool
    {
        try {
            return boolval($this->redis->exists($key));
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * append
     *
     * @param string $key the cache key
     * @param string $value the value to append
     * @return int the new string length
     *
     * @see https://github.com/phpredis/phpredis#append
     */
    public function append(string $key, string $value): int
    {
        try {
            return $this->redis->append($key, $value);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * increment
     *
     * @param string $key the cache key
     * @param int|float $value the value to increment by
     * @return int|float the new value
     *
     * @see https://github.com/phpredis/phpredis#incr-incrby
     * @see https://github.com/phpredis/phpredis#incrbyfloat
     */
    public function increment(string $key, int|float $value = 1): int|float
    {
        try {
            if (!is_int($value)) {
                return $this->redis->incrByFloat($key, $value);
            }

            return $this->redis->incrBy($key, $value);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * decrement
     *
     * @param string $key the cache key
     * @param int $value the value to decrement by
     * @return int the new value
     *
     * @see https://github.com/phpredis/phpredis#decr-decrby
     */
    public function decrement(string $key, int $value = 1): int
    {
        # cast to an int
        $unsafe = $this->redis->get($key);
        $safe = intval($unsafe);

        try {
            # set the integer value
            $this->redis->set($key, $safe);

            return $this->redis->decrBy($key, $value);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * persist
     *
     * @param string $key the cache key
     * @return bool true on success, false on failure
     *
     * @see https://github.com/phpredis/phpredis#persist
     */
    public function persist(string $key): bool
    {
        try {
            return $this->redis->persist($key);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * delete
     *
     * @param string ...$keys the keys to delete
     * @return void
     *
     * @see https://github.com/phpredis/phpredis#del-delete-unlink
     */
    public function delete(string ...$keys): void
    {
        try {
            foreach ($keys as $key) {
                $this->redis->unlink($key);
            }
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * flush
     *
     * Flush the keys from a given node.
     *
     * @param int $node the node to flush
     * @return bool true on success, false on failure
     *
     * @see https://github.com/phpredis/phpredis#flushall
     */
    public function flush(int $node = 1): bool
    {
        try {
            return $this->redis->flushAll($node);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * flushAll
     *
     * Flush the keys from all nodes.
     *
     * @return ?array the status of each node flush
     *
     * @see https://github.com/phpredis/phpredis#flushall
     */
    public function flushAll(): ?array
    {
        $return = [];

        if (!$this->clusterMode) {
            return null;
        }

        try {
            foreach ($this->masters() as $node => $master) {
                $return[$node] = $this->redis->flushAll($node);
            }
        } catch (\Throwable $e) {
            $this->handleError($e);
        }

        return $return;
    }


    /** meta */


    /**
     * info
     *
     * @return array the server info
     *
     * @see https://github.com/phpredis/phpredis#info
     * @see https://redis.io/commands/info/
     */
    public function info(string $pattern = "*"): array
    {
        try {
            return $this->redis->info($pattern);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * error
     *
     * @return ?string the last error message
     *
     * @see https://github.com/phpredis/phpredis#getlasterror
     */
    public function error(): ?string
    {
        try {
            return $this->redis->getLastError();
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * slowLog
     *
     * @param int $limit the number of entries to return
     * @return array the slow log entries
     *
     * @see https://github.com/phpredis/phpredis#slowlog
     */
    public function slowLog(int $limit = 10): array
    {
        try {
            $slowLog = $this->redis->slowLog("get", $limit);

            if (!empty($slowLog)) {
                return $slowLog;
            }
        } catch (\Throwable $e) {
            $this->handleError($e);
        }

        return [];
    }


    /**
     * keys
     *
     * @param string $pattern the pattern to match
     * @return array the keys that match the pattern
     *
     * @see https://github.com/phpredis/phpredis#keys-getkeys
     */
    public function keys(string $pattern = "*"): array
    {
        try {
            return $this->redis->keys($pattern);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * count
     *
     * @return int the number of keys in the database
     *
     * @see https://github.com/phpredis/phpredis#dbsize
     */
    public function count(int $node = 1): int
    {
        try {
            return $this->redis->dbSize($node);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * ping
     *
     * @param string $message the message to send
     * @return string the server response
     *
     * @see https://github.com/phpredis/phpredis#ping
     */
    public function ping(string $message = "hello"): string
    {
        try {
            return $this->redis->ping($message);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * raw
     *
     * @param string $command the command to run
     * @param array $arguments the arguments to pass
     * @return mixed the response
     *
     * @see https://github.com/phpredis/phpredis#rawcommand
     */
    public function raw(string $command, array $arguments = []): mixed
    {
        try {
            return $this->redis->rawCommand($command, ...$arguments);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /**
     * masters
     *
     * @return ?array the cluster masters
     */
    public function masters(): ?array
    {
        if (!$this->clusterMode) {
            return null;
        }

        try {
            return $this->redis->_masters();
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }


    /** query locks (legacy) */


    /**
     * setQueryLock
     *
     * todo: remove this method
     */
    public function setQueryLock(string $lockName): bool
    {
        return true;
        #return $this->set("queryLock:{$lockName}", true, $this->cacheDuration);
    }


    /**
     * clearQueryLock
     *
     * todo: remove this method
     */
    public function clearQueryLock(string $lockName): bool
    {
        return true;
        #return $this->delete("queryLock:{$lockName}");
    }
} # class

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

    # reserved characters
    # see https://github.com/symfony/cache-contracts/blob/main/ItemInterface.php
    private $reservedCharacters = ["{", "}", "(", ")", "/", "\\", "@"]; # you can totally use ":"
    #private $reservedCharacters = ["{", "}", "(", ")", "/", "\\", "@", ":"];


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
        # reserved characters
        $key = $this->sanitize($key);

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
        $key = $this->sanitize($key);
        return $this->redis->get($key);
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
        $key = $this->sanitize($key);
        return boolval($this->redis->exists($key));
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
        $key = $this->sanitize($key);
        return $this->redis->append($key, $value);
    }


    /**
     * increment
     *
     * @param string $key the cache key
     * @param int|float $value the value to increment by
     * @return int the new value
     *
     * @see https://github.com/phpredis/phpredis#incr-incrby
     * @see https://github.com/phpredis/phpredis#incrbyfloat
     */
    public function increment(string $key, int|float $value = 1): int|float
    {
        $key = $this->sanitize($key);

        if (!is_int($value)) {
            return $this->redis->incrByFloat($key, $value);
        }

        return $this->redis->incrBy($key, $value);
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
        $key = $this->sanitize($key);

        # cast to an int
        $unsafe = $this->redis->get($key);
        $safe = intval($unsafe);

        # set the integer value
        $this->redis->set($key, $safe);

        return $this->redis->decrBy($key, $value);
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
        $key = $this->sanitize($key);
        return $this->redis->persist($key);
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
        foreach ($keys as $key) {
            $key = $this->sanitize($key);
            $this->redis->unlink($key);
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
        return $this->redis->flushAll($node);
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
        if (!$this->clusterMode) {
            return null;
        }

        $return = [];
        foreach ($this->masters() as $node => $master) {
            $return[$node] = $this->redis->flushAll($node);
        }

        return $return;
    }


    /** meta */


    /**
     * sanitize
     *
     * Sanitize a cache key.
     *
     * @param string $key the cache key
     * @return string the sanitized key
     */
    private function sanitize(string $key): string
    {
        return str_replace($this->reservedCharacters, "-", $key);
    }


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
        return $this->redis->info($pattern);
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
        return $this->redis->getLastError();
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
        $slowLog = $this->redis->slowLog("get", $limit);
        if (!empty($slowLog)) {
            return $slowLog;
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
        return $this->redis->keys($pattern);
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
        return $this->redis->dbSize($node);
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
        return $this->redis->ping($message);
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
        return $this->redis->rawCommand($command, ...$arguments);
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

        return $this->redis->_masters();
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

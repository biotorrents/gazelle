<?php

declare(strict_types=1);


/**
 * Gazelle\Cache
 *
 * A wrapper for the PhpRedis extension.
 *
 * @see https://github.com/phpredis/phpredis
 */

namespace Gazelle;

class Cache # extends \Redis
{
    # singleton
    private static $instance = null;

    # redis
    private static $redis = null;

    # default key lifetime (seconds)
    private $cachePrefix = "gazelle:";
    private $cacheDuration = "1 hour";

    # torrent group cache version
    public const GROUP_VERSION = "2023-04-01";

    # are we in a transaction?
    private $transactionMode = false;


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
        if (self::$instance === null) {
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

        # establish connection
        $redis = new \Redis();
        $redis->connect(
            $app->env->getPriv("redisHost"),
            $app->env->getPriv("redisPort"),
        );

        # failure
        if (!$redis->isConnected()) {
            throw new \Exception("unable to establish PhpRedis cache connection");
        }

        # set options
        $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_JSON);
        $redis->setOption(\Redis::OPT_PREFIX, $this->cachePrefixs);

        # done
        self::$redis = $redis;
    }


    /** crud */


    /**
     * set
     *
     * @param string $key the cache key
     * @param mixed $value the value to store
     * @param int|string $cacheDuration the number of seconds to store the value
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
                $cacheDuration = \Carbon\Carbon::parse($cacheDuration);
            } catch (\Throwable $e) {
                $cacheDuration = null;
            }
        }

        # default expiration time in seconds
        if (!$cacheDuration) {
            $cacheDuration = time() + $this->cacheDuration;
        }

        # store the value
        self::$redis->set($key, $value);
        self::$redis->expireAt($key, $cacheDuration);

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
        return self::$redis->get($key);
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
        return self::$redis->exists($key);
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
        return self::$redis->append($key, $value);
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
        if (!is_int($value)) {
            return self::$redis->incrByFloat($key, $value);
        }

        return self::$redis->incrBy($key, $value);
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
        return self::$redis->decrBy($key, $value);
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
        return self::$redis->persist($key);
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
            self::$redis->unlink($key);
        }
    }


    /**
     * flush
     *
     * @return bool true on success, false on failure
     *
     * @see https://github.com/phpredis/phpredis#flushall
     */
    public function flush(): bool
    {
        return self::$redis->flushAll();
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
    public function info(string $pattern = ""): array
    {
        return self::$redis->info($pattern);
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
        return self::$redis->getLastError();
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
        return self::$redis->slowLog("get", $limit);
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
        return self::$redis->keys($pattern);
    }


    /**
     * count
     *
     * @return int the number of keys in the database
     *
     * @see https://github.com/phpredis/phpredis#dbsize
     */
    public function count(): int
    {
        return self::$redis->dbSize();
    }


    /**
     * ping
     *
     * @param string $message the message to send
     * @return string the server response
     *
     * @see https://github.com/phpredis/phpredis#ping
     */
    public function ping(string $message = ""): string
    {
        return self::$redis->ping($message);
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
        return self::$redis->rawCommand($command, ...$arguments);
    }


    /** transactions */


    # todo


    /** query locks (legacy) */


    /**
     * setQueryLock
     *
     * Set a query lock.
     * Expires in an hour.
     *
     * @param string $lockName the name on the lock
     * @return array the key/value pair
     */
    public function setQueryLock(string $lockName): array
    {
        return $this->set("query_lock_{$lockName}", 1, 3600);
    }


    /**
     * clearQueryLock
     *
     * Remove a query lock.
     *
     * @param string $lockName the name on the lock
     * @return void
     */
    public function clearQueryLock(string $lockName): void
    {
        $this->delete("query_lock_{$lockName}");
    }
} # class

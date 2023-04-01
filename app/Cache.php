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
    private $cachePrefix = "gazelle:"; # e.g., gazelle:stats:overview
    private $cacheDuration = 3600; # one hour, if not otherwise specified

    # torrent group cache version
    public $groupVersion = "2023-04-01";

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
        $redis->setOption(\Redis::OPT_PREFIX, $this->cachePrefix);

        # done
        $this->redis = $redis;
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

        # store the value
        $this->redis->set($key, $value);
        $this->redis->expireAt($key, $cacheDuration);

        # if cacheDuration = 0, persist the value
        if ($cacheDuration === 0) {
            $this->redis->persist($key);
        }

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
        return $this->redis->exists($key);
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
            $this->redis->unlink($key);
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
        return $this->redis->flushAll();
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
        return $this->redis->slowLog("get", $limit);
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
    public function count(): int
    {
        return $this->redis->dbSize();
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
        return $this->set("queryLock:{$lockName}", true, $this->cacheDuration);
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
        $this->delete("queryLock:{$lockName}");
    }
} # class

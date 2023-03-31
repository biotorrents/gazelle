<?php

declare(strict_types=1);


/**
 * Gazelle\Cache
 *
 * @see https://github.com/phpredis/phpredis
 * @see https://www.tutorialspoint.com/redis/redis_php.htm
 */

namespace Gazelle;

class Cache # extends \Redis
{
    # singleton
    private static $instance = null;

    # redis
    private static $redis = null;

    # default key lifetime (seconds)
    private $cacheDuration = 86400; # one day

    # torrent group cache version
    public const GROUP_VERSION = 5;


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
        $redis->setOption(\Redis::OPT_PREFIX, "gazelle:");

        # done
        self::$redis = $redis;
    }


    /** */


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
     * set
     *
     * @param string $key the cache key
     * @param mixed $value the value to store
     * @param int $cacheDuration the number of seconds to store the value
     * @return array the key/value pair
     *
     * @see https://github.com/phpredis/phpredis#set
     * @see https://github.com/phpredis/phpredis#mset-msetnx
     */
    public function set(string $key, mixed $value, ?int $cacheDuration = null): array
    {
        # default expiration time in seconds
        if (!$cacheDuration) {
            $cacheDuration = time() + $this->cacheDuration;
        } else {
            $cacheDuration = time() + $cacheDuration;
        }

        # store the value
        self::$redis->set($key, $value);
        self::$redis->expireAt($key, $cacheDuration);

        return [$key => $value];
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
     * increment
     *
     * @param string $key the cache key
     * @param int $value the value to increment by
     * @return int the new value
     */
    public function increment(string $key, int $value = 1): int
    {
        return self::$redis->incrBy($key, $value);
    }


    /**
     * decrement
     *
     * @param string $key the cache key
     * @param int $value the value to decrement by
     * @return int the new value
     */
    public function decrement(string $key, int $value = 1): int
    {
        return self::$redis->decrBy($key, $value);
    }


    /** */


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
     * info
     *
     * @return array the server info
     *
     * @see https://github.com/phpredis/phpredis#info
     */
    public function info(): array
    {
        return self::$redis->info();
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


    /**
     * count
     *
     * @return int the number of keys in the database
     *
     * @see https://github.com/phpredis/phpredis#mset-msetnx
     */
    public function count(): int
    {
        return self::$redis->dbSize();
    }


    /** */


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

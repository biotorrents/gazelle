<?php

declare(strict_types=1);


/**
 * Gazelle\Cache
 *
 * @see https://github.com/phpredis/phpredis
 * @see https://www.tutorialspoint.com/redis/redis_php.htm
 */

namespace Gazelle;

class Cache # extends Redis
{
    # singleton
    private static $instance = null;

    # redis
    private static $redis = null;

    # default key lifetime (seconds)
    private $cacheDuration = 86400; # one day


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

        $redis = new \Redis();
        $redis->connect(
            $app->env->getPriv("redisHost"),
            $app->env->getPriv("redisPort"),
        );

        if (!$redis->isConnected()) {
            throw new \Exception("unable to establish PhpRedis cache connection");
        }

        self::$redis = $redis;
        return $redis;
    }


    /** non-singleton methods */


    /**
     * get
     *
     * @see https://github.com/phpredis/phpredis#get
     */
    public function get(string $key)
    {
        $value = self::$redis->get($key);

        # try to decode a serialized value
        $decoded = json_decode($value, true);
        if ($decoded) {
            return $decoded;
        }

        # it's just a single value
        return $value;
    }


    /**
     * set
     *
     * @param string $key the cache key
     * @param mixed $value anything you wanna store
     * @param int $cacheDuration how long it should last, in seconds
     * @return bool true on success, false on failure
     *
     * @see https://github.com/phpredis/phpredis#set
     * @see https://github.com/phpredis/phpredis#mset-msetnx
     */
    public function set(string $key, mixed $value, ?int $cacheDuration = null)
    {
        # default expiration time in seconds
        if (!$cacheDuration) {
            $cacheDuration = time() + $this->cacheDuration;
        } else {
            $cacheDuration = time() + $cacheDuration;
        }

        # serialize the value if it's an array or object
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        # store the value
        self::$redis->set($key, $value);
        self::$redis->expireAt($key, $cacheDuration);

        return [$key => $value];
    }


    /**
     * keys
     *
     * @see https://github.com/phpredis/phpredis#keys-getkeys
     */
    public function keys(string $pattern = "*")
    {
        return self::$redis->keys($pattern);
    }


    /**
     * info
     *
     * @see https://github.com/phpredis/phpredis#info
     */
    public function info()
    {
        return self::$redis->info();
    }


    /**
     * ping
     *
     * @see https://github.com/phpredis/phpredis#ping
     */
    public function ping(string $message = "")
    {
        return self::$redis->ping($message);
    }


    /**
     * flush
     *
     * @see https://github.com/phpredis/phpredis#flushall
     */
    public function flush()
    {
        return self::$redis->flushAll();
    }


    /**
     * delete
     *
     * ACTUALLY UNLINK: ASYNC DELETION.
     * @see https://github.com/phpredis/phpredis#del-delete-unlink
     */
    public function delete(bool $now = false, string ...$keys)
    {
        foreach ($keys as $key) {
            if ($now === false) {
                self::$redis->unlink($key);
            } else {
                self::$redis->del($key);
            }
        }
    }


    /**
     * count
     *
     * @see https://github.com/phpredis/phpredis#mset-msetnx
     */
    public function count()
    {
        return self::$redis->dbSize();
    }
}

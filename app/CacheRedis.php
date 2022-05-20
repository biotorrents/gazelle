<?php
declare(strict_types=1);

/**
 * Cache
 *
 * @see https://github.com/phpredis/phpredis
 * @see https://www.tutorialspoint.com/redis/redis_php.htm
 */

class CacheRedis # extends Redis
{
    # singleton
    private static $instance = null;

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
        $app = App::go();

        $redis = new Redis();
        $redis->connect(
            $app->env->getPriv("REDIS_HOST"),
            $app->env->getPriv("REDIS_PORT"),
        );

        if ($redis->isConnected()) {
            return $redis;
        } else {
            throw new Exception("Unable to establish PhpRedis cache connection");
        }
    }


    /** NON-SINGLETON METHODS */


    /**
     * __construct
     * /
    public function __construct()
    {
        $ENV = ENV::go();

        $cache = new Redis();
        $cache->connect(
            $ENV->getPriv("REDIS_HOST"),
            $ENV->getPriv("REDIS_PORT"),
        );

        if ($cache->isConnected()) {
            $app->cacheNew = $cache;
            return $cache;
        } else {
            throw new Exception("Unable to establish cache connection");
        }
    }
    */


    /**
     * get
     *
     * @see https://github.com/phpredis/phpredis#get
     */
    public function get(string $key)
    {
        $app = App::go();

        $value = $app->cacheNew->get($key);

        # PhpRedis returns false on bad key
        if ($value === false) {
            throw new Exception("Cache key {$key} doesn't exist");
        }
        
        # just a single value
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
    public function set(string $key, $value, int $cacheDuration = 0)
    {
        $app = App::go();

        # default expiration time in seconds
        if ($cacheDuration === 0) {
            $cacheDuration = time() + $this->cacheDuration;
        } else {
            $cacheDuration = time() + $cacheDuration;
        }


        # $k => $v
        if (!is_array($value) || !is_object($value)) {
            $app->cacheNew->set($key, $value);
            $app->cacheNew->expireAt($key, $cacheDuration);

            return true;
        }
        
        # $k => [$a, $b, $c, [$d, $e, $f]]
        else {
            $app->cacheNew->mset([$key, $value]);
            $app->cacheNew->expireAt($key, $cacheDuration);

            return true;
        }

        # bad value provided
        throw new Exception("Provided value {$value} is malformed");
        return false;
    }


    /**
     * keys
     *
     * @see https://github.com/phpredis/phpredis#keys-getkeys
     */
    public function keys(string $pattern = "*")
    {
        $app = App::go();

        return $app->cacheNew->keys($pattern);
    }


    /**
     * info
     *
     * @see https://github.com/phpredis/phpredis#info
     */
    public function info()
    {
        $app = App::go();

        return $app->cacheNew->info();
    }


    /**
     * ping
     *
     * @see https://github.com/phpredis/phpredis#ping
     */
    public function ping(string $message = "")
    {
        $app = App::go();

        return $app->cacheNew->ping($message);
    }


    /**
     * flush
     *
     * @see https://github.com/phpredis/phpredis#flushall
     */
    public function flush()
    {
        $app = App::go();

        return $app->cacheNew->flushAll();
    }


    /**
     * delete
     *
     * ACTUALLY UNLINK: ASYNC DELETION.
     * @see https://github.com/phpredis/phpredis#del-delete-unlink
     */
    public function delete(bool $now = false, string ...$keys)
    {
        $app = App::go();

        foreach ($keys as $key) {
            if ($now === false) {
                $app->cacheNew->unlink($key);
            } else {
                $app->cacheNew->del($key);
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
        $app = App::go();

        return $app->cacheNew->dbSize();
    }
}

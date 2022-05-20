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
    # redis connection storage
    private $redis = null;

    # default key lifetime (seconds)
    private $expires = 86400;


    /**
     * __construct
     */
    public function __construct()
    {
        $ENV = ENV::go();

        $cache = new Redis();
        $cache->connect(
            $ENV->getPriv("REDIS_HOST"),
            $ENV->getPriv("REDIS_PORT"),
        );

        if ($cache->isConnected()) {
            $this->redis = $cache;
            return $cache;
        } else {
            throw new Exception("Unable to establish cache connection");
        }
    }


    /**
     * get
     *
     * @see https://github.com/phpredis/phpredis#get
     */
    public function get(string $key)
    {
        $value = $this->redis->get($key);

        # PhpRedis returns false on bad key
        if ($value === false) {
            throw new Exception("Cache key {$key} doesn't exist");
        }

        /*
        # try to decode the json
        $json = json_decode($value);
        if ($json !== null) {
            return $json;
        }
        */

        # just a single value
        return $value;
    }


    /**
     * set
     *
     * @param string $key the cache key
     * @param mixed $value anything you wanna store
     * @param int $expires how long it should last, in seconds
     * @return bool true on success, false on failure
     *
     * @see https://github.com/phpredis/phpredis#set
     * @see https://github.com/phpredis/phpredis#mset-msetnx
     */
    public function set(string $key, $value, int $expires = 0)
    {
        # default expiration time in seconds
        if ($expires === 0) {
            $expires = time() + $this->expires;
        } else {
            $expires = time() + $expires;
        }


        # $k => $v
        if (!is_array($value) || !is_object($value)) {
            $this->redis->set($key, $value);
            $this->redis->expireAt($key, $expires);

            return true;
        }
        
        # $k => [$a, $b, $c, [$d, $e, $f]]
        else {
            $this->redis->mset([$key, $value]);
            $this->redis->expireAt($key, $expires);

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
        return $redis->keys($pattern);
    }


    /**
     * info
     *
     * @see https://github.com/phpredis/phpredis#info
     */
    public function info()
    {
        return $redis->info();
    }


    /**
     * ping
     *
     * @see https://github.com/phpredis/phpredis#ping
     */
    public function ping(string $message = "")
    {
        return $redis->ping($message);
    }


    /**
     * flush
     *
     * @see https://github.com/phpredis/phpredis#flushall
     */
    public function flush()
    {
        return $redis->flushAll();
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
                $redis->unlink($key);
            } else {
                $redis->del($key);
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
        return $redis->dbSize();
    }
}

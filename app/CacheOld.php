<?php

declare(strict_types=1);


/**
 * Cache class
 *
 * This class is a wrapper for the Memcache class.
 * It's been written to better handle caching full pages with bits of dynamic content.
 *
 * As this inherits Memcache, all of the default memcache methods work.
 * However, this class has superior page caching functions.
 *
 * Memcache::get and Memcache::set are wrapped by Cache::get_value and Cache::cache_value.
 * Cache::get_value uses the same arguments as Memcache::get.
 * Cache::cache_value only takes the key, value, and duration.
 *
 * Unix socket:
 *   memcached -d -m 5120 -s /var/run/memcached.sock -a 0777 -t16 -C -u root
 *
 * TCP bind:
 *   memcached -d -m 8192 -l 10.10.0.1 -t8 -C
 */

class CacheOld extends Memcache
{
    public $memcacheDBArray = [];
    public $memcacheDBKey = '';
    protected $inTransaction = false;
    private $servers = [];
    protected $connection;


    /**
     * __construct
     */
    public function __construct($servers = [])
    {
        $ENV = ENV::go();

        if (empty($servers)) {
            $servers = $ENV->getPriv("MEMCACHED_SERVERS");
        }

        if (is_subclass_of($this, 'Memcached')) {
            parent::__construct();
        }

        $this->servers = $servers;
        foreach ($servers as $server) {
            if (is_subclass_of($this, 'Memcache')) {
                $this->addServer(
                    $server['host'],
                    $server['port'],
                    true,
                    $server['buckets']
                );
            } else {
                $this->addServer(
                    str_replace('unix://', '', $server['host']),
                    $server['port'],
                    $server['buckets']
                );
            }
        }
    }


    /************************
     * MEMCACHEDB FUNCTIONS *
     ************************/


    /**
     * begin_transaction
     */
    public function begin_transaction($key)
    {
        $value = $this->get($key);
        if (!is_array($value)) {
            $this->inTransaction = false;
            $this->memcacheDBKey = [];
            $this->memcacheDBKey = '';

            return false;
        }

        $this->memcacheDBArray = $value;
        $this->memcacheDBKey = $key;
        $this->inTransaction = true;

        return true;
    }


    /**
     * cancel_transaction
     */
    public function cancel_transaction()
    {
        $this->inTransaction = false;
        $this->memcacheDBKey = [];
        $this->memcacheDBKey = '';
    }


    /**
     * commit_transaction
     */
    public function commit_transaction($time = 2592000)
    {
        $app = \Gazelle\App::go();

        if (!$this->inTransaction) {
            return false;
        }

        # trickery
        $app->cacheNew->set($this->memcacheDBKey, $this->memcacheDBArray, $time);
        $this->inTransaction = false;
    }


    /**
     * update_transaction
     *
     * Updates multiple rows in an array.
     */
    public function update_transaction($rows, $values)
    {
        if (!$this->inTransaction) {
            return false;
        }

        $array = $this->memcacheDBArray;
        if (is_array($rows)) {
            $i = 0;
            $keys = $rows[0];
            $property = $rows[1];

            foreach ($keys as $row) {
                $array[$row][$property] = $values[$i];
                $i++;
            }
        } else {
            $array[$rows] = $values;
        }

        $this->memcacheDBArray = $array;
    }


    /**
     * update_row
     *
     * Updates multiple values in a single row in an array.
     * $values must be an associative array with key:value pairs like in the array we're updating.
     */
    public function update_row($row, $values)
    {
        if (!$this->inTransaction) {
            return false;
        }

        if ($row === false) {
            $updateArray = $this->memcacheDBArray;
        } else {
            $updateArray = $this->memcacheDBArray[$row];
        }

        foreach ($values as $key => $value) {
            if (!array_key_exists($key, $updateArray)) {
                trigger_error("Bad transaction key {$key} for cache {$this->memcacheDBKey}");
            }

            if ($value === '+1') {
                if (!is_numeric($updateArray[$key])) {
                    trigger_error("Tried to increment non-number {$key} for cache {$this->memcacheDBKey}");
                }

                $updateArray[$key]++; // Increment value
            } elseif ($value === '-1') {
                if (!is_numeric($updateArray[$key])) {
                    trigger_error("Tried to decrement non-number {$key} for cache {$this->memcacheDBKey}");
                }

                $updateArray[$key]--; // Decrement value
            } else {
                $updateArray[$key] = $value; // Otherwise, just alter value
            }
        }

        if ($row === false) {
            $this->memcacheDBArray = $updateArray;
        } else {
            $this->memcacheDBArray[$row] = $updateArray;
        }
    }


    /**
     * insert
     */
    public function insert($key, $value)
    {
        if (!$this->inTransaction) {
            return false;
        }

        if ($key === '') {
            $this->memcacheDBArray[] = $value;
        } else {
            $this->memcacheDBArray[$key] = $value;
        }
    }
}

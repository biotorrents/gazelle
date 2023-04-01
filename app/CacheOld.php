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
    public $cacheHits = [];
    public $canClear = false;
    public $internalCache = true;
    public $memcacheDBArray = [];
    public $memcacheDBKey = '';
    public $time = 0;

    protected $inTransaction = false;

    private $clearedKeys = [];
    private $servers = [];
    private $persistentKeys = [
        'ajax_requests_*',
        'query_lock_*',
        #'stats_*',
        'top10tor_*',
        'users_snatched_*',

        // Cache-based features
        'global_notification',
        'notifications_one_reads_*',
    ];

    # php 8.2 warnings
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


    /*********************
     * CACHING FUNCTIONS *
     *********************/


    /**
     * cache_value
     *
     * Wrapper for Memcache::set, with the zlib option removed and default duration of 30 days.
     */
    public function cache_value($key, $value, $duration = 2592000)
    {
        $startTime = microtime(true);

        if (is_string($duration)) {
            $parsed = strtotime($duration) ?? time();
            $duration = time() - $parsed;
        }
        if (empty($key)) {
            trigger_error('Cache insert failed for empty key');
        }

        $setParams = [$key, $value, 0, $duration];
        if (is_subclass_of($this, 'Memcached')) {
            unset($setParams[2]);
        }

        if (!$this->set(...$setParams)) {
            trigger_error("Cache insert failed for key {$key}");
        }

        if ($this->internalCache && array_key_exists($key, $this->cacheHits)) {
            $this->cacheHits[$key] = $value;
        }

        $this->time += (microtime(true) - $startTime) * 1000;
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
        if (!$this->inTransaction) {
            return false;
        }

        $this->cache_value($this->memcacheDBKey, $this->memcacheDBArray, $time);
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


    /**
     * delete_row
     */
    public function delete_row($row)
    {
        if (!$this->inTransaction) {
            return false;
        }

        if (!isset($this->memcacheDBArray[$row])) {
            trigger_error("Tried to delete non-existent row {$row} for cache {$this->memcacheDBKey}");
        }

        unset($this->memcacheDBArray[$row]);
    }


    /**
     * update
     */
    public function update($key, $rows, $values, $time = 2592000)
    {
        if (!$this->inTransaction) {
            $this->begin_transaction($key);
            $this->update_transaction($rows, $values);
            $this->commit_transaction($time);
        } else {
            $this->update_transaction($rows, $values);
        }
    }
}

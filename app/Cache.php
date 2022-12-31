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
class Cache extends Memcache
{
    // Torrent Group cache version
    public const GROUP_VERSION = 5;

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
     * expire_value
     *
     * Allows us to set an expiration on otherwise perminantly cached values.
     * Useful for disabled users, locked threads, basically reducing RAM usage.
     */
    public function expire_value($key, $duration = 2592000)
    {
        $startTime = microtime(true);
        $this->set($key, $this->get($key), $duration);
        $this->time += (microtime(true) - $startTime) * 1000;
    }


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


    /**
     * add_value
     *
     * Wrapper for Memcache::add, with the zlib option removed and default duration of 30 days.
     */
    public function add_value($key, $value, $duration = 2592000)
    {
        $startTime = microtime(true);
        $added = $this->add($key, $value, 0, $duration);
        $this->time += (microtime(true) - $startTime) * 1000;
        return $added;
    }


    /**
     * replace_value
     */
    public function replace_value($key, $value, $duration = 2592000)
    {
        $startTime = microtime(true);
        $replaceParams = [$key, $value, false, $duration];

        if (is_subclass_of($this, 'Memcached')) {
            unset($replaceParams[2]);
        }

        $this->replace(...$replaceParams);

        if ($this->internalCache && array_key_exists($key, $this->cacheHits)) {
            $this->cacheHits[$key] = $value;
        }

        $this->time += (microtime(true) - $startTime) * 1000;
    }


    /**
     * get_value
     */
    public function get_value($key, $noCache = false)
    {
        $query = Http::query("get");
        $clearCache = $query["clearcache"] ?? null;

        if (!$this->internalCache) {
            $noCache = true;
        }

        $startTime = microtime(true);
        if (empty($key)) {
            trigger_error('Cache retrieval failed for empty key');
        }

        if (!empty($clearCache) && $this->canClear && !isset($this->clearedKeys[$key]) && !Misc::in_array_partial($key, $this->persistentKeys)) {
            if (intval($clearCache) === 1) {
                // Because check_perms() isn't true until LoggedUser is pulled from the cache, we have to remove the entries loaded before the LoggedUser data
                // Because of this, not user cache data will require a secondary pageload following the clearcache to update
                if (count($this->cacheHits) > 0) {
                    foreach (array_keys($this->cacheHits) as $hitKey) {
                        if (!isset($this->clearedKeys[$hitKey]) && !Misc::in_array_partial($hitKey, $this->persistentKeys)) {
                            $this->delete($hitKey);
                            unset($this->cacheHits[$hitKey]);
                            $this->clearedKeys[$hitKey] = true;
                        }
                    }
                }

                $this->delete($key);
                $this->time += (microtime(true) - $startTime) * 1000;

                return null;
            } elseif ($clearCache === $key) {
                $this->delete($key);
                $this->time += (microtime(true) - $startTime) * 1000;

                return false;
            } elseif (substr($clearCache, -1) === '*') {
                $prefix = substr($clearCache, 0, -1);

                if ($prefix === '' || $prefix === substr($key, 0, strlen($prefix))) {
                    $this->delete($key);
                    $this->time += (microtime(true) - $startTime) * 1000;

                    return false;
                }
            }

            $this->clearedKeys[$key] = true;
        }

        // For cases like the forums, if a key is already loaded, grab the existing pointer
        if (isset($this->cacheHits[$key]) && !$noCache) {
            $this->time += (microtime(true) - $startTime) * 1000;
            return $this->cacheHits[$key] ?? false;
        }

        $return = $this->get($key) ?? false;
        if ($return !== false) {
            $this->cacheHits[$key] = $noCache ? null : $return;
        }

        $this->time += (microtime(true) - $startTime) * 1000;
        return $return ?? false;
    }


    /**
     * delete_value
     *
     * Wrapper for Memcache::delete.
     * For a reason, see above.
     */
    public function delete_value($key)
    {
        $startTime = microtime(true);
        if (empty($key)) {
            trigger_error('Cache deletion failed for empty key');
        }

        if (!$this->delete($key)) {
            #trigger_error("Cache delete failed for key {$key}");
        }

        unset($this->cacheHits[$key]);
        $this->time += (microtime(true) - $startTime) * 1000;
    }


    /**
     * increment_value
     */
    public function increment_value($key, $value = 1)
    {
        $startTime = microtime(true);
        $newVal = $this->increment($key, $value);

        if (isset($this->cacheHits[$key])) {
            $this->cacheHits[$key] = $newVal;
        }

        $this->time += (microtime(true) - $startTime) * 1000;
    }


    /**
     * decrement_value
     */
    public function decrement_value($key, $value = 1)
    {
        $startTime = microtime(true);
        $newVal = $this->decrement($key, $value);

        if (isset($this->cacheHits[$key])) {
            $this->cacheHits[$key] = $newVal;
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
                if (!is_number($updateArray[$key])) {
                    trigger_error("Tried to increment non-number {$key} for cache {$this->memcacheDBKey}");
                }

                $updateArray[$key]++; // Increment value
            } elseif ($value === '-1') {
                if (!is_number($updateArray[$key])) {
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
     * increment_row
     *
     * Increments multiple values in a single row in an array.
     * $values must be an associative array with key:value pairs like in the array we're updating.
     */
    public function increment_row($row, $values)
    {
        if (!$this->InTransaction) {
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

            if (!is_number($value)) {
                trigger_error("Tried to increment with non-number {$key} for cache {$this->memcacheDBKey}");
            }
            $updateArray[$key] += $value; // Increment value
        }

        if ($row === false) {
            $this->memcacheDBArray = $updateArray;
        } else {
            $this->memcacheDBArray[$row] = $updateArray;
        }
    }


    /**
     * insert_front
     *
     * Insert a value at the beginning of the array.
     */
    public function insert_front($key, $value)
    {
        if (!$this->inTransaction) {
            return false;
        }

        if ($key === '') {
            array_unshift($this->memcacheDBArray, $value);
        } else {
            $this->memcacheDBArray = [$key => $value] + $this->memcacheDBArray;
        }
    }


    /**
     * insert_back
     *
     * Insert a value at the end of the array.
     */
    public function insert_back($key, $value)
    {
        if (!$this->inTransaction) {
            return false;
        }

        if ($key === '') {
            array_push($this->memcacheDBArray, $value);
        } else {
            $this->memcacheDBArray = $this->memcacheDBArray + [$key => $value];
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

    /**
     * get_query_lock
     *
     * Tries to set a lock.
     * Expiry time is one hour to avoid indefinite locks.
     *
     * @param string $lockName The name on the lock
     * @return true If the lock was acquired
     */
    public function get_query_lock($lockName)
    {
        return $this->add_value("query_lock_{$lockName}", 1, 3600);
    }

    /**
     * clear_query_lock
     *
     * Remove lock.
     *
     * @param string $lockName The name on the lock
     */
    public function clear_query_lock($lockName)
    {
        $this->delete_value("query_lock_{$lockName}");
    }

    /**
     * server_status
     *
     * Get cache server status.
     *
     * @return array [host => int status, ...]
     */
    public function server_status()
    {
        $status = [];
        if (is_subclass_of($this, 'Memcached')) {
            $memcachedStats = $this->getStats();
        }

        foreach ($this->servers as $server) {
            if (is_subclass_of($this, 'Memcached')) {
                $status["{$server['host']}:{$server['port']}"] = gettype($memcachedStats["{$server['host']}:{$server['port']}"]) === 'array' ? 1 : 0;
            } else {
                $status["{$server['host']}:{$server['port']}"] = $this->getServerStatus($server['host'], $server['port']);
            }
        }

        return $status;
    }
}

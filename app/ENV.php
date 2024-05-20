<?php

declare(strict_types=1);


/**
 * Gazelle\ENV
 *
 * The PHP singleton is considered bad design for nebulous reasons,
 * but for securely loading a site config it does exactly what we need:
 *
 *  - ensure that only one instance of itself can ever exist
 *  - load the instance everywhere we need to do $app->env->configValue
 *  - no memory penalty because of multiple app instances
 *  - static values in config/foo.php are immutable
 *  - site configs don't exist in the constants table
 *  - separate public and private config values
 *
 * @see https://phpenthusiast.com/blog/the-singleton-design-pattern-in-php
 *
 *
 * Oh yeah, it also supports all of Laravel's Collection methods.
 * The underlying structure is a Collection not an ArrayObject.
 *
 * @see https://laravel.com/docs/master/collections#available-methods
 */

namespace Gazelle;

class ENV
{
    # disinstantiates itself
    private static ?self $instance = null;

    # config option receptacles
    public RecursiveCollection $public; # site meta, options, resources, etc.
    private RecursiveCollection $private; # passwords, app keys, database, etc.


    /**
     * __functions
     */

    # prevents outside construction
    public function __construct()
    {
        # would be expensive, e.g.,
        #   $env = new ENV();
        return;
    }

    # prevents multiple instances
    public function __clone()
    {
        return trigger_error(
            "clone not allowed",
            E_USER_ERROR
        );
    }

    # prevents unserializing
    public function __wakeup()
    {
        return trigger_error(
            "wakeup not allowed",
            E_USER_ERROR
        );
    }


    /** */


    /**
     * __get
     *
     * @param mixed $key the key to get
     * @return mixed the value of the key
     */
    public function __get(mixed $key): mixed
    {
        return $this->public[$key] ?? null;
    }


    /**
     * __set
     *
     * @param mixed $key the key to set
     * @param mixed $value the value to set
     * @return void
     */
    public function __set(mixed $key, mixed $value): void
    {
        $this->public[$key] = $this->collect($value);
    }


    /**
     * __isset
     *
     * @param mixed $key the key to check
     * @return bool whether the key is set
     */
    public function __isset(mixed $key): bool
    {
        return isset($this->public[$key]);
    }


    /**
     * __unset
     *
     * @param mixed $key the key to unset
     * @return void
     */
    public function __unset(mixed $key): void
    {
        unset($this->public[$key]);
    }


    /**
     * __call
     *
     * @param string $method the method to call
     * @param array $arguments the arguments to pass
     */
    public function __call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this->public, $method)) {
            return trigger_error(
                "the method {$method} doesn't exist",
                E_USER_ERROR
            );
        }

        return $this->public->$method($arguments);
    }


    /** */


    /**
     * go
     *
     * Calls its self's creation or returns itself.
     *
     * @param array $options the options to use
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
     * @param array $options the options to use
     * @return void
     */
    private function factory(array $options = []): void
    {
        $this->public = new RecursiveCollection();
        $this->private = new RecursiveCollection();
    }


    /**
     * collect
     *
     * Converts stuff into a RecursiveCollection
     *
     * @param mixed $array the stuff to convert
     * @return mixed a scalar or RecursiveCollection
     *
     * @see https://stackoverflow.com/a/54131002
     */
    public function collect(mixed $array = []): mixed
    {
        if (is_iterable($array)) {
            $return = new RecursiveCollection($array);

            foreach ($return as &$item) {
                $item = $this->collect($item);
            }

            return $return;
        }

        return $array;
    }


    /**
     * private
     *
     * Sets a private key if $value !== null.
     * Otherwise, returns the value of $key.
     * This returns the value on set, not void!
     *
     * @param mixed $key the key to set or get
     * @param mixed $value the value to set
     * @return mixed the value of the key
     */
    public function private(mixed $key, mixed $value = null): mixed
    {
        # get
        if (is_null($value)) {
            return $this->private[$key] ?? null;
        }

        # set
        return $this->private[$key] = $this->collect($value);
    }
} # class

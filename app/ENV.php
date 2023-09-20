<?php

declare(strict_types=1);


/**
 * ENV
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
 * @see https://stackoverflow.com/a/3724689
 * @see https://phpenthusiast.com/blog/the-singleton-design-pattern-in-php
 */

class ENV
{
    # disinstantiates itself
    private static $instance = null;

    # config option receptacles
    public static $public = []; # site meta, options, resources, etc.
    private static $private = []; # passwords, app keys, database, etc.


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

    # $this->key returns public->key
    public function __get($key)
    {
        return self::$public[$key] ?? null;
    }

    # $this->foo = "bar"
    public function __set($key, $value)
    {
        return self::$public[$key] = $value;
    }

    # isset
    public function __isset($key)
    {
        return isset(self::$public[$key]);
    }


    /**
     * go
     *
     * Calls its self's creation or returns itself.
     */
    public static function go(array $options = []): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * private
     *
     * Sets a private key if $value !== null.
     * Otherwise, returns the value of $key.
     *
     * @param string $key the key to set or get
     * @param mixed $value the value to set
     * @return mixed the value of the key
     */

    public function private(string $key, mixed $value = null): mixed
    {
        # get
        if (!$value) {
            return self::$private[$key] ?? null;
        }

        # set
        return self::$private[$key] = $value;
    }


    /**
     * convert
     *
     * Take a mixed input and returns a RecursiveArrayObject.
     * This function is the sausage grinder, so to speak.
     */
    public function convert(array|object|string $object): RecursiveArrayObject
    {
        switch (gettype($object)) {
            case "string":
                $out = json_decode($object, true);
                return (json_last_error() === JSON_ERROR_NONE)
                    ? new RecursiveArrayObject($out)
                    : trigger_error("json_last_error_msg: " . json_last_error_msg(), E_USER_ERROR);
                break;

            case "array":
            case "object":
                return new RecursiveArrayObject($object);

            default:
                return trigger_error("ENV->convert expects a JSON string, array, or object", E_USER_ERROR);
                break;
        }
    }


    /**
     * toArray
     *
     * Takes an object and returns an array.
     *
     * @param object|string $object thing to turn into an array
     * @return array|string $new new recursive array with $object contents
     *
     * @see https://ben.lobaugh.net/blog/567/php-recursively-convert-an-object-to-an-array
     */
    public function toArray(object|string $object): array|string
    {
        if (is_object($object)) {
            $object = (array) $object;
        }

        if (is_array($object)) {
            $new = [];
            foreach ($object as $key => $value) {
                $new[$key] = $this->toArray($value);
            }
        } else {
            $new = $object;
        }

        return $new;
    }


    /**
     * dedupe
     *
     * Takes a collection (usually an array) of various jumbled $app->env slices.
     * Returns a once-deduplicated RecursiveArrayObject with original nesting intact.
     * Simple and handy if you need to populate a form with arbitrary collections of metadata.
     */
    public function dedupe(array|object $object): RecursiveArrayObject
    {
        if (is_object($object)) {
            $object = (array) $object;
        }

        return new RecursiveArrayObject(
            array_unique($this->toArray($object))
        );
    }


    /**
     * flatten
     *
     * Takes an $app->env node or array of them,
     * and flattens out the multi-dimensionality.
     * It returns a flat array with keys intact.
     *
     * @param array|object $object the thing to flatten
     * @param int $level currently unused (this function is buggy)
     * @return array the flattened array
     */
    public function flatten(array|object $object, int $level = null): array
    {
        $new = [];

        foreach ($object as $key => $value) {
            if (is_object($value)) {
                $value = $this->toArray($value);
            }

            if (is_array($value)) {
                $new = array_merge($new, $this->flatten($value));
            } else {
                $new[$key] = $value;
            }
        }

        return $new;
    }


    /**
     * map
     *
     * Simple array_map() object wrapper.
     * Maps a callback (or default) to an object.
     *
     * Example output:
     * $hashes = $app->env->map("md5", $app->env->categories->{6});
     * var_dump($hashes);
     *
     * object(RecursiveArrayObject)#324 (1) {
     *   ["storage":"ArrayObject":private]=>
     *   array(6) {
     *     ["id"]=>
     *     string(32) "28c8edde3d61a0411511d3b1866f0636"
     *     ["name"]=>
     *     string(32) "fe83ccb5dc96dbc0658b3c4672c7d5fe"
     *     ["icon"]=>
     *     string(32) "52963afccc006d2bce3c890ad9e8f73a"
     *     ["platforms"]=>
     *     string(32) "d41d8cd98f00b204e9800998ecf8427e"
     *     ["formats"]=>
     *     string(32) "d41d8cd98f00b204e9800998ecf8427e"
     *     ["description"]=>
     *     string(32) "ca6628e8c13411c800d1d9d0eaccd849"
     *   }
     * }
     *
     * @param string $function callback function
     * @param object|string $object object or property to operate on
     * @return object function-mapped RecursiveArrayObject
     */
    public function map(string $function = "", object|string $object = null): RecursiveArrayObject
    {
        # set a default function if desired
        if (empty($function) && !is_object($function)) {
            $function = "array_filter";
        }

        # quick sanity check
        if ($function === "array_map") {
            throw new Exception("ENV->map can't invoke the function it wraps");
        }

        /**
         * $function not a closure
         *
         * var_dump(
         *   gettype(
         *     (function() { return; })
         * ));
         * string(6) "object"
         */
        if (is_string($function) && !is_object($function)) {
            $function = trim(strtok($function, " "));
        }

        # map the sanitized function name
        # to a mapped array conversion
        return new RecursiveArrayObject(
            array_map(
                $function,
                array_map(
                    $function,
                    $this->toArray($object)
                )
            )
        );
    }
} # class


/** */


/**
 * RecursiveArrayObject
 *
 * @author: etconsilium@github
 * @license: BSDLv2
 * @see https://github.com/etconsilium/php-recursive-array-object
 */

class RecursiveArrayObject extends ArrayObject
{
    /**
     * __functions
     */

    # __construct
    public function __construct($input = null, $flags = self::ARRAY_AS_PROPS, $iterator_class = "ArrayIterator")
    {
        foreach ($input as $key => $value) {
            $this->__set($key, $value);
        }

        return $this;
    }

    # __get
    public function __get($key)
    {
        if ($this->offsetExists($key)) {
            return $this->offsetGet($key);
        } elseif (array_key_exists($key, $this)) {
            return $this[$key];
        } else {
            throw new InvalidArgumentException("the instance doesn't have the property {$key}");
        }
    }

    # __set
    public function __set($key, $value)
    {
        if (is_array($value) || is_object($value)) {
            $this->offsetSet($key, (new self($value)));
        } else {
            $this->offsetSet($key, $value);
        }
    }

    # __isset
    public function __isset($key)
    {
        return array_key_exists($key, $this);
    }

    # __unset
    public function __unset($key)
    {
        unset($this[$key]);
    }


    /**
     * __call
     *
     * Enables the use of all PHP array functions,
     * e.g., $app->env->array_keys() and similar.
     *
     * @see https://www.php.net/manual/en/class.arrayobject.php#107079
     */
    public function __call($function, $arguments)
    {
        if (!is_callable($function) || substr($function, 0, 6) !== "array_") {
            throw new BadMethodCallException(__CLASS__ . "->" . $function);
        }

        return call_user_func_array(
            $function,
            array_merge(
                [$this->getArrayCopy()],
                $arguments
            )
        );
    }


    /**
     * toArray
     */
    public function toArray(): array|string
    {
        $app = \Gazelle\App::go();

        return $app->env->toArray($this);
    }
} # class

<?php

declare(strict_types=1);


/**
 * ENV
 *
 * The PHP singleton is considered bad design for nebulous reasons,
 * but for securely loading a site config it does exactly what we need:
 *
 *  - Ensure that only one instance of itself can ever exist
 *  - Load the instance everywhere we need to do $app->env->configValue
 *  - No memory penalty because of multiple app instances
 *  - Static values in config/foo.php are immutable
 *  - Site configs don't exist in the constants table
 *  - Separate public and private config values
 *
 * @see https://stackoverflow.com/a/3724689
 * @see https://phpenthusiast.com/blog/the-singleton-design-pattern-in-php
 */

class ENV
{
    # disinstantiates itself
    private static $instance = null;

    # config options receptacles
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
        return isset(self::$public[$key])
            ? self::$public[$key]
            : false;
    }

    # isset
    public function __isset($key)
    {
        return isset(self::$public[$key]);
    }


    /**
     * Gets n' Sets
     */

    # calls its self's creation or returns itself
    public static function go(array $options = []): ENV
    {
        if (self::$instance === null) {
            self::$instance = new self();
            #self::$instance->factory($options);
        }

        return self::$instance;
    }

    # getPriv
    public function getPriv($key)
    {
        return isset(self::$private[$key])
            ? self::$private[$key]
            : false;
    }

    # getPub
    public function getPub($key)
    {
        return isset(self::$public[$key])
            ? self::$public[$key]
            : false;
    }

    # setPriv
    public static function setPriv($key, $value)
    {
        return self::$private[$key] = $value;
    }

    # setPub
    public static function setPub($key, $value)
    {
        return self::$public[$key] = $value;
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
                return trigger_error("ENV->convert expects a JSON string, array, or object.", E_USER_ERROR);
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
     */
    public function flatten(array|object $array, int $level = null): array
    {
        $new = [];
        foreach ($array as $k => $v) {
            if (is_object($v)) {
                $v = $this->toArray($v);
            }

            if (is_array($v)) {
                $new = array_merge($new, $this->flatten($v));
            } else {
                $new[$k] = $v;
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
     * $hashes = $app->env->map("md5", $app->env->CATS->{6});
     * var_dump($hashes);
     *
     * object(RecursiveArrayObject)#324 (1) {
     *   ["storage":"ArrayObject":private]=>
     *   array(6) {
     *     ["ID"]=>
     *     string(32) "28c8edde3d61a0411511d3b1866f0636"
     *     ["Name"]=>
     *     string(32) "fe83ccb5dc96dbc0658b3c4672c7d5fe"
     *     ["Icon"]=>
     *     string(32) "52963afccc006d2bce3c890ad9e8f73a"
     *     ["Platforms"]=>
     *     string(32) "d41d8cd98f00b204e9800998ecf8427e"
     *     ["Formats"]=>
     *     string(32) "d41d8cd98f00b204e9800998ecf8427e"
     *     ["Description"]=>
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


/**
 * @author: etconsilium@github
 * @license: BSDLv2
 * @see https://github.com/etconsilium/php-recursive-array-object
 */

class RecursiveArrayObject extends ArrayObject
{
    /**
     * __construct
     */
    public function __construct($input = null, $flags = self::ARRAY_AS_PROPS, $iterator_class = "ArrayIterator")
    {
        foreach ($input as $key => $value) {
            $this->__set($key, $value);
        }

        return $this;
    }


    /**
     * __set
     */
    public function __set($name, $value)
    {
        if (is_array($value) || is_object($value)) {
            $this->offsetSet($name, (new self($value)));
        } else {
            $this->offsetSet($name, $value);
        }
    }


    /**
     * __get
     */
    public function __get($name)
    {
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        } elseif (array_key_exists($name, $this)) {
            return $this[$name];
        } else {
            throw new InvalidArgumentException("the instance doesn't have the property {$name}");
        }
    }


    /**
     * __isset
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this);
    }


    /**
     * __unset
     */
    public function __unset($name)
    {
        unset($this[$name]);
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
} # class

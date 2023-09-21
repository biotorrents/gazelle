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

    # $this->key returns self::$public["key"]
    public function __get(string $key)
    {
        return self::$public[$key] ?? null;
    }

    # $this->key = "value"
    public function __set(string $key, mixed $value)
    {
        return self::$public[$key] = $this->toObject($value);
        #return self::$public[$key] = $value;
    }

    # isset
    public function __isset(string $key)
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
        return self::$private[$key] = $this->toObject($value);
        #return self::$private[$key] = $value;
    }


    /**
     * convert
     *
     * Take a mixed input and returns a RecursiveArrayObject.
     * This function is the sausage grinder, so to speak.
     *
     * @param mixed $object the thing to convert
     * @return RecursiveArrayObject the converted thing
     */
    /*
    public function convert(mixed $object): RecursiveArrayObject
    {
        switch (gettype($object)) {
            case "string":
                $return = json_decode($object, true);
                return (json_last_error() === JSON_ERROR_NONE)
                    ? new RecursiveArrayObject($return)
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
    */


    /**
     * toArray
     *
     * Takes an object and returns an array.
     *
     * @param mixed $object thing to turn into an array
     * @return mixed $new array with $object contents
     *
     * @see https://stackoverflow.com/a/54131002
     */
    public function toArray(mixed $object): mixed
    {
        if (is_object($object) || is_array($object)) {
            $return = (array) $object;

            foreach ($return as &$item) {
                $item = $this->toArray($item);
            }

            return $return;
        }

        return $object;
    }


    /**
     * toObject
     *
     * Takes an array and returns an object.
     *
     * @param mixed $object thing to turn into an object
     * @return mixed $new object with $array contents
     *
     * @see https://stackoverflow.com/a/54131002
     */
    public function toObject(mixed $array): mixed
    {
        if (is_object($array) || is_array($array)) {
            $return = new RecursiveArrayObject($array);

            foreach ($return as &$item) {
                $item = $this->toObject($item);
            }

            return $return;
        }

        return $array;
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
        $object = $this->toArray($object);

        return new RecursiveArrayObject(
            array_unique($object)
        );
    }


    /**
     * flatten
     *
     * Takes an $app->env node or an array of them.
     * Flattens it to $depth and returns a RecursiveArrayObject.
     *
     * @param array|object $object the thing(s) to flatten
     * @param int $depth how far down the rabbit hole to go
     * @return RecursiveArrayObject the flattened collection
     *
     * @see https://github.com/laravel/framework/blob/master/src/Illuminate/Collections/Arr.php
     */
    public function flatten(array|object $object, int|float $depth = INF): RecursiveArrayObject
    {
        $object = $this->toArray($object);

        foreach ($object as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($item)
                    : $this->flatten($item, $depth - 1);

                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }

        return new RecursiveArrayObject($result);
    }


    /**
     * map
     *
     * Simple array_map() object wrapper.
     *
     * gazelle ⟫ $app->env->map($app->env->metadata->licenses, "md5");
     * ⇒ RecursiveArrayObject {#6447
     *   storage: [
     *     "637e33dbf653615ccc4e48e6e3c24cbe",
     *     "7164f3d949c8d27791d49cfb7db2fa8e",
     *     "93b8aa3b4b7c90318495b755a8cca651",
     *     "550596d3aa29e66436673fdd4b12e90b",
     *     "80f70ce738a55c3a85c0ed2defb63845",
     *     "1b2c755e5a804c1decc86b0fc2f17bc3",
     *     "4521335a360750bb34abc3a633c08ad2",
     *     "b7c403f3ddf62cc25965e8ff69eee9c4",
     *     "497384593e1d7e469ebd7c6220419f50",
     *     "809b7e8881077933e846dc06a6b22359",
     *     "2efae3af6e3b8bb3118e5d49468c3b15",
     *     "f29b2d7d5554892edb79590ae0de2999",
     *     "7abc1a233092fc104c7af72a89c0829c",
     *     "d02b9a00c3e3e946f6d1704a2e09fe13",
     *     "88d654d41cf692e98e6cb8c68f0642af",
     *     "a1eda56dc13a7dcf186ca6895c5f5422",
     *     "1282faaaab2ab95c41052afbc71de28e",
     *     "6fcdc090caeade09d0efd6253932b6f5",
     *   ],
     *   flag::STD_PROP_LIST: false,
     *   flag::ARRAY_AS_PROPS: false,
     *   iteratorClass: "ArrayIterator",
     * }
     *
     * @param mixed $object object or property to operate on
     * @param callable $callback the callback function
     * @return object function-mapped RecursiveArrayObject
     *
     * @see https://stackoverflow.com/a/39637749
     */
    public function map(mixed $object, callable $callback): RecursiveArrayObject
    {
        $object = $this->toArray($object);

        $function = function ($item) use (&$function, &$callback) {
            return is_array($item)
                ? array_map($function, $item)
                : call_user_func($callback, $item);
        };

        return new RecursiveArrayObject(
            array_map($function, $object)
        );
    }


    /**
     * sort
     *
     * Recursively sorts an object.
     *
     * @param mixed $object the object to sort
     * @param int $options the sort options
     * @param bool $descending whether to sort descending
     * @return RecursiveArrayObject the sorted object
     *
     * @see https://github.com/laravel/framework/blob/master/src/Illuminate/Collections/Arr.php
     */
    public function sort(mixed $object, $options = SORT_REGULAR, $descending = false): RecursiveArrayObject
    {
        $object = $this->toArray($object);

        foreach ($object as &$value) {
            if (is_array($value)) {
                $value = $this->sort($value, $options, $descending);
            }
        }

        if (!array_is_list($object)) {
            $descending
                ? krsort($object, $options)
                : ksort($object, $options);
        } else {
            $descending
                ? rsort($object, $options)
                : sort($object, $options);
        }

        return new RecursiveArrayObject($object);
    }


    /**
     * sortDescending
     *
     * Recursively sorts an object descending.
     */
    public function sortDescending(mixed $object, $options = SORT_REGULAR): RecursiveArrayObject
    {
        return $this->sort($object, $options, true);
    }
} # class


/** */


/**
 * RecursiveArrayObject
 *
 * @author: etconsilium@github
 * @license: BSDLv2
 *
 * @see https://github.com/etconsilium/php-recursive-array-object
 */

class RecursiveArrayObject extends ArrayObject
{
    /**
     * __functions
     */

    # __construct
    public function __construct(mixed $input = null, int $flags = self::ARRAY_AS_PROPS, string $iterator_class = "ArrayIterator")
    {
        foreach ($input as $key => $value) {
            $this->__set($key, $value);
        }

        return $this;
    }

    # __get
    public function __get(mixed $key)
    {
        if ($this->offsetExists($key)) {
            return $this->offsetGet($key);
        }

        if (array_key_exists($key, $this)) {
            return $this[$key];
        }

        throw new InvalidArgumentException("the instance doesn't have the property {$key}");
    }

    # __set
    public function __set(mixed $key, mixed $value)
    {
        if (is_array($value) || is_object($value)) {
            $this->offsetSet($key, (new self($value)));
        } else {
            $this->offsetSet($key, $value);
        }
    }

    # __isset
    public function __isset(mixed $key)
    {
        return array_key_exists($key, $this);
    }

    # __unset
    public function __unset(mixed $key)
    {
        unset($this[$key]);
    }


    /**
     * __call
     *
     * Enables the use of all PHP array functions,
     * e.g., $app->env->array_keys() and similar.
     *
     * @param string $callback the function to call
     * @param mixed $arguments the arguments to pass
     *
     * @see https://www.php.net/manual/en/class.arrayobject.php#107079
     */
    public function __call(string $callback, mixed $arguments = null)
    {
        if (!is_callable($callback) || !str_starts_with($callback, "array_")) {
            throw new BadMethodCallException(__CLASS__ . "->" . $callback);
        }

        return call_user_func_array(
            $callback,
            array_merge(
                [$this->getArrayCopy()],
                $arguments
            )
        );
    }


    /**
     * toArray
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }
} # class

<?php
declare(strict_types=1);

/**
 * ENV
 *
 * The PHP singleton is considered bad design for nebulous reasons,
 * but for securely loading a site config it does exactly what we need:
 *
 *  - Ensure that only one instance of itself can ever exist
 *  - Load the instance everywhere we need to do $ENV->VALUE
 *  - No memory penalty because of multiple $ENV instances
 *  - Static values in classes/config.php are immutable
 *  - Site configs don't exist in the constants table
 *  - Separate public and private config values
 *
 * @see https://stackoverflow.com/a/3724689
 * @see https://phpenthusiast.com/blog/the-singleton-design-pattern-in-php
 */

class ENV
{
    # Disinstantiates itself
    private static $ENV = null;

    # Config options receptacles
    private static $priv = []; # Passwords, app keys, database, etc.
    private static $pub = []; # Site meta, options, resources, etc.

    /**
     * __functions
     */

    # Prevents outside construction
    private function __construct()
    {
        # Would be expensive, e.g.,
        #   $ENV = new ENV();
        return;
    }

    # Prevents multiple instances
    private function __clone()
    {
        return trigger_error(
            'clone not allowed',
            E_USER_ERROR
        );
    }

    # Prevents unserializing
    public function __wakeup()
    {
        return trigger_error(
            'wakeup not allowed',
            E_USER_ERROR
        );
    }
    
    # $this->key returns public->key
    private function __get($key)
    {
        return isset(self::$pub[$key])
            ? self::$pub[$key]
            : false;
    }
    
    # isset
    private function __isset($key)
    {
        return isset(self::$pub[$key]);
    }
    

    /**
     * Gets n' Sets
     */

    # Calls its self's creation or returns itself
    public static function go(): ENV
    {
        return (self::$ENV === null)
            ? self::$ENV = new ENV()
            : self::$ENV;
    }

    # get
    public function getPriv($key)
    {
        return isset(self::$priv[$key])
            ? self::$priv[$key]
            : false;
    }

    public function getPub($key)
    {
        return isset(self::$pub[$key])
            ? self::$pub[$key]
            : false;
    }

    # set
    public static function setPriv($key, $value)
    {
        return self::$priv[$key] = $value;
    }

    public static function setPub($key, $value)
    {
        return self::$pub[$key] = $value;
    }


    /**
     * convert
     *
     * Take a mixed input and returns a RecursiveArrayObject.
     * This function is the sausage grinder, so to speak.
     */
    public function convert(array|object|string $obj): RecursiveArrayObject
    {
        switch (gettype($obj)) {
            case 'string':
                $out = json_decode($obj, true);
                return (json_last_error() === JSON_ERROR_NONE)
                    ? new RecursiveArrayObject($out)
                    : error('json_last_error_msg: ' . json_last_error_msg());
                break;
            
            case 'array':
            case 'object':
                return new RecursiveArrayObject($obj);
            
            default:
                return error('ENV->convert expects a JSON string, array, or object.');
                break;
        }
    }


    /**
     * toArray
     *
     * Takes an object and returns an array.
     * @param object|string $obj Thing to turn into an array
     * @return $new New recursive array with $obj contents
     * @see https://ben.lobaugh.net/blog/567/php-recursively-convert-an-object-to-an-array
     */
    public function toArray(object $obj): array
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }

        if (is_array($obj)) {
            $new = array();

            foreach ($obj as $key => $value) {
                $new[$key] = $this->toArray($value);
            }
        } else {
            $new = $obj;
        }
        return $new;
    }


    /**
     * dedupe
     *
     * Takes a collection (usually an array) of various jumbled $ENV slices.
     * Returns a once-deduplicated RecursiveArrayObject with original nesting intact.
     * Simple and handy if you need to populate a form with arbitrary collections of metadata.
     */
    public function dedupe(array|object $obj): RecursiveArrayObject
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }

        return new RecursiveArrayObject(
            array_unique($this->toArray($obj))
        );
    }


    /**
     * flatten
     *
     * Takes an $ENV node or array of them
     * and flattens out the multi-dimensionality.
     * It returns a flat array with keys intact.
     */
    public function flatten(array|object $array, int $level = null): array
    {
        if (!is_array($array) && !is_object($array)) {
            return error('ENV->flatten expects an array or object, got ' . gettype($array));
        }

        $new = array();

        foreach ($array as $k => $v) {
            /*
             if (is_object($v)) {
                $v = $this->toArray($v);
            }
            */
    
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
     * $Hashes = $ENV->map('md5', $ENV->CATS->{6});
     *
     * var_dump($Hashes);
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
     * var_dump($Hashes->Icon);
     * string(32) "52963afccc006d2bce3c890ad9e8f73a"
     *
     * @param string $fn Callback function
     * @param object|string $obj Object or property to operate on
     * @return object $RAO Mapped RecursiveArrayObject
     */
    public function map(string $fn = '', object|string $obj = null): RecursiveArrayObject
    {
        # Set a default function if desired
        if (empty($fn) && !is_object($fn)) {
            $fn = 'array_filter';
        }

        # Quick sanity check
        if ($fn === 'array_map') {
            error("ENV->map can't invoke the function it wraps.");
        }
        
        /**
         * $fn not a closure
         *
         * var_dump(
         *   gettype(
         *     (function() { return; })
         * ));
         * string(6) "object"
         */
        if (is_string($fn) && !is_object($fn)) {
            $fn = trim(strtok($fn, ' '));
        }

        # Map the sanitized function name
        # to a mapped array conversion
        return new RecursiveArrayObject(
            array_map(
                $fn,
                array_map(
                    $fn,
                    $this->toArray($obj)
                )
            )
        );
    }
}


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
            throw new InvalidArgumentException("The instance doesn't have the property {$name}");
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
}

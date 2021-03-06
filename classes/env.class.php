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
    private static $Priv = []; # Passwords, app keys, database, etc.
    private static $Pub = []; # Site meta, options, resources, etc.


    /**
     * __functions()
     */

    # Prevents outside construction
    private function __construct()
    {
        # Would be expensive, e.g.,
        #   $ENV = new ENV();
        return;
    }

    # Prevents multiple instances
    public function __clone()
    {
        return trigger_error(
            'clone() not allowed',
            E_USER_ERROR
        );
    }

    # $this->key returns public->key
    public function __get($key)
    {
        return isset(self::$Pub[$key])
            ? self::$Pub[$key]
            : false;
    }
    
    # isset()
    public function __isset($key)
    {
        return isset(self::$Pub[$key]);
    }


    /**
     * Gets n' Sets
     */

    # Calls its self's creation or returns itself
    public static function go()
    {
        return (self::$ENV === null)
            ? self::$ENV = new ENV()
            : self::$ENV;
    }

    # get
    public static function getPriv($key)
    {
        return isset(self::$Priv[$key])
            ? self::$Priv[$key]
            : false;
    }

    public static function getPub($key)
    {
        return isset(self::$Pub[$key])
            ? self::$Pub[$key]
            : false;
    }

    # set
    public static function setPriv($key, $value)
    {
        return self::$Priv[$key] = $value;
    }

    public static function setPub($key, $value)
    {
        return self::$Pub[$key] = $value;
    }


    /**
     * toArray
     * @see https://ben.lobaugh.net/blog/567/php-recursively-convert-an-object-to-an-array
     */
    public function toArray($obj)
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
     * fromJson
     *
     * @param string $JSON Valid JavaScript object string
     * @return RecursiveArrayObject Not stdClass as in json_decode()
     */

    public function fromJson($str)
    {
        if (!is_string($str) || is_empty($str)) {
            error('$ENV->fromJson() expects a string.');
        }

        # Decode to array and construct RAO
        return $RAO = new RecursiveArrayObject(
            json_decode($str, true)
        );
    }


    /**
     * dedupe
     *
     * Takes a collection (usually an array) of various jumbled $ENV slices.
     * Returns a once-deduplicated RecursiveArrayObject with original nesting intact.
     * Simple and handy if you need to populate a form with arbitrary collections of metadata.
     */
    public function dedupe($obj)
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }

        return $RAO = new RecursiveArrayObject(
            array_unique($this->toArray($obj))
        );
    }


    /**
     * map
     *
     * Simple array_map() object wrapper.
     * Maps a callback (or default) to an object.
     *
     * Example output:
     * $Hashes = $ENV->map('md5', $ENV->CATS->SEQ);
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
     * @param object $obj Object to operate on
     * @return object $RAO Mapped RecursiveArrayObject
     */
    public function map($fn = '', $obj = null)
    {
        # Set a default function if desired
        if (empty($fn) && !is_object($fn)) {
            $fn = 'array_filter';
        }

        # Quick sanity check
        if ($fn === 'array_map') {
            error("map() can't invoke the function it wraps.");
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
        return $RAO = new RecursiveArrayObject(
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

class RecursiveArrayObject extends \ArrayObject
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
            throw new \InvalidArgumentException(sprintf('$this have not prop `%s`', $name));
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

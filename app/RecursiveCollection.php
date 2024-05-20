<?php

declare(strict_types=1);


/**
 * RecursiveCollection
 *
 * Laravel Collections that support recursive access.
 *
 * @see https://laravel.com/docs/master/collections
 * @see https://github.com/spatie/laravel-collection-macros
 *
 *
 * The underlying objects used to be native ArrayObject instances.
 *
 * @author: etconsilium@github
 * @license: BSDLv2
 *
 * @see https://github.com/etconsilium/php-recursive-array-object
 */

namespace Gazelle;

class RecursiveCollection extends \Illuminate\Support\Collection
{
    /**
     * __construct
     *
     * @param mixed $input the input to construct
     *
     * @see https://laravel.com/docs/master/collections#creating-collections
     */
    public function __construct(mixed $input = [])
    {
        if (!is_iterable($input)) {
            $input = [$input];
        }

        foreach ($input as $key => $value) {
            $this->$key = $value;
        }
    }


    /**
     * __get
     *
     * @param mixed $key the key to get
     * @return mixed the value of the key
     *
     * @see https://laravel.com/docs/master/collections#method-get
     */
    public function __get(mixed $key): mixed
    {
        return $this->get($key);
    }


    /**
     * __set
     *
     * @param mixed $key the key to set
     * @param mixed $value the value to set
     * @return void
     *
     * @see https://laravel.com/docs/master/collections#method-put
     */
    public function __set(mixed $key, mixed $value): void
    {
        if (is_iterable($value)) {
            $this->put($key, new self($value));
        } else {
            $this->put($key, $value);
        }
    }


    /**
     * __isset
     *
     * @param mixed $key the key to check
     * @return bool whether the key is set
     *
     * @see https://laravel.com/docs/master/collections#method-has
     */
    public function __isset(mixed $key): bool
    {
        return $this->has($key);
    }


    /**
     * __unset
     *
     * @param mixed $key the key to unset
     * @return void
     *
     * @see https://laravel.com/docs/master/collections#method-forget
     */
    public function __unset(mixed $key): void
    {
        $this->forget($key);
    }


    /**
     * toArray
     *
     * Recursively convert the collection to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }

        return $array;

        /*
        # the old standy, just in case
        return json_decode($this->toJson(), true);
        */
    }
} # class

<?php

declare(strict_types=1);


/**
 * RecursiveCollection
 *
 * Laravel Collections that support recursive access.
 * This used to be the RecursiveArrayObject class.
 *
 * @author: etconsilium@github
 * @license: BSDLv2
 *
 * @see https://github.com/etconsilium/php-recursive-array-object
 */

class RecursiveCollection extends Illuminate\Support\Collection
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
        foreach ($input as $key => $value) {
            $this->$key = $value;
        }

        return $this;
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
        return $this->get($key) ?? null;
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
} # class

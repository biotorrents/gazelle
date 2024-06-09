<?php

declare(strict_types=1);


/**
 * LazyCollection
 *
 * Laravel LazyCollection wrapper intended for site objects.
 * This makes all objects immutable, something to remember.
 *
 * @see https://laravel.com/docs/master/collections
 * @see https://github.com/spatie/laravel-collection-macros
 */

namespace Gazelle;

class LazyCollection extends \Illuminate\Support\LazyCollection
{
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
        $app = App::go();

        # native laravel function
        $value = $this->get($key);

        # do we need to express a canonical id as a uri?
        if ($app->executionContext === "api" && $key === "id") {
            return "https://{$app->env->siteDomain}/{$value}";
        }

        # try to decode any json fields that might be present
        # (because custom reading is tedious and no longer works)
        if (is_string($value)) {
            $good = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $good;
            }
        }

        return $value;
    }


    /**
     * raw
     *
     * @param mixed $key the key to get
     * @return mixed the value of the key
     *
     * @see https://laravel.com/docs/master/collections#method-get
     */
    public function raw(mixed $key): mixed
    {
        return $this->get($key);
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

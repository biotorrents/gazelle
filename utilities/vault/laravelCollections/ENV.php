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

class ENV extends Illuminate\Support\Collection
{
    # disinstantiates itself
    private static $instance = null;

    # config option receptacles
    public Illuminate\Support\Collection $public; # site meta, options, resources, etc.
    private Illuminate\Support\Collection $private; # passwords, app keys, database, etc.


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
     *
     * @see https://laravel.com/docs/master/collections#method-get
     */
    public function __get(mixed $key): mixed
    {
        return $this->public->get($key);
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
            $this->public->put($key, collect($value));
        } else {
            $this->public->put($key, $value);
        }
    }


    /**
     * __isset
     *
     * @param mixed $key the key to check
     * @return bool whether the key exists
     *
     * @see https://laravel.com/docs/master/collections#method-has
     */
    public function __isset(mixed $key): bool
    {
        return $this->public->has($key);
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
        $this->public->forget($key);
    }


    /** */


    /**
     * go
     *
     * Calls its self's creation or returns itself.
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
        # https://gist.github.com/brunogaspar/154fb2f99a7f83003ef35fd4b5655935
        parent::macro("recursive", function () {
            return $this->map(function ($value) {
                if (is_iterable($value)) {
                    return collect($value)->recursive();
                }

                return $value;
            });
        });

        /*
        # https://laravel.com/docs/master/collections#extending-collections
        foreach ($this->macros() as $macro => $class) {
            parent::macro($macro, function () use ($class) {
                return $this->map(function ($value) use ($class) {
                    return new $class($value);
                });
            });
        }
        */

        # https://laravel.com/docs/master/collections#creating-collections
        $this->public = new parent();
        $this->private = new parent();
    }


    /**
     * private
     *
     * Sets a private key if $value !== null.
     * Otherwise, returns the value of $key.
     *
     * @param mixed $key the key to set or get
     * @param mixed $value the value to set
     * @return mixed the value of the key
     */

    public function private(mixed $key, mixed $value = null): mixed
    {
        if (is_iterable($value)) {
            return $this->private->getOrPut($key, collect($value));
        } else {
            return $this->private->getOrPut($key, $value);
        }
    }


    /** */


    /**
     * macros
     *
     * @see https://github.com/spatie/laravel-collection-macros/blob/main/src/CollectionMacroServiceProvider.php
     */
    private function macros(): array
    {
        return [
            'after' => \Spatie\CollectionMacros\Macros\After::class,
            'at' => \Spatie\CollectionMacros\Macros\At::class,
            'before' => \Spatie\CollectionMacros\Macros\Before::class,
            'chunkBy' => \Spatie\CollectionMacros\Macros\ChunkBy::class,
            'collectBy' => \Spatie\CollectionMacros\Macros\CollectBy::class,
            'containsAll' => \Spatie\CollectionMacros\Macros\ContainsAll::class,
            'containsAny' => \Spatie\CollectionMacros\Macros\ContainsAny::class,
            'eachCons' => \Spatie\CollectionMacros\Macros\EachCons::class,
            'eighth' => \Spatie\CollectionMacros\Macros\Eighth::class,
            'extract' => \Spatie\CollectionMacros\Macros\Extract::class,
            'fifth' => \Spatie\CollectionMacros\Macros\Fifth::class,
            'filterMap' => \Spatie\CollectionMacros\Macros\FilterMap::class,
            'firstOrFail' => \Spatie\CollectionMacros\Macros\FirstOrFail::class,
            'firstOrPush' => \Spatie\CollectionMacros\Macros\FirstOrPush::class,
            'fourth' => \Spatie\CollectionMacros\Macros\Fourth::class,
            'fromPairs' => \Spatie\CollectionMacros\Macros\FromPairs::class,
            'getCaseInsensitive' => \Spatie\CollectionMacros\Macros\GetCaseInsensitive::class,
            'getNth' => \Spatie\CollectionMacros\Macros\GetNth::class,
            'glob' => \Spatie\CollectionMacros\Macros\Glob::class,
            'groupByModel' => \Spatie\CollectionMacros\Macros\GroupByModel::class,
            'hasCaseInsensitive' => \Spatie\CollectionMacros\Macros\HasCaseInsensitive::class,
            'head' => \Spatie\CollectionMacros\Macros\Head::class,
            'if' => \Spatie\CollectionMacros\Macros\IfMacro::class,
            'ifAny' => \Spatie\CollectionMacros\Macros\IfAny::class,
            'ifEmpty' => \Spatie\CollectionMacros\Macros\IfEmpty::class,
            'insertAfter' => \Spatie\CollectionMacros\Macros\InsertAfter::class,
            'insertAfterKey' => \Spatie\CollectionMacros\Macros\InsertAfterKey::class,
            'insertAt' => \Spatie\CollectionMacros\Macros\InsertAt::class,
            'insertBefore' => \Spatie\CollectionMacros\Macros\InsertBefore::class,
            'insertBeforeKey' => \Spatie\CollectionMacros\Macros\InsertBeforeKey::class,
            'ninth' => \Spatie\CollectionMacros\Macros\Ninth::class,
            'none' => \Spatie\CollectionMacros\Macros\None::class,
            'paginate' => \Spatie\CollectionMacros\Macros\Paginate::class,
            'parallelMap' => \Spatie\CollectionMacros\Macros\ParallelMap::class,
            'path' => \Spatie\CollectionMacros\Macros\Path::class,
            'pluckMany' => \Spatie\CollectionMacros\Macros\PluckMany::class,
            'pluckManyValues' => \Spatie\CollectionMacros\Macros\PluckManyValues::class,
            'pluckToArray' => \Spatie\CollectionMacros\Macros\PluckToArray::class,
            'prioritize' => \Spatie\CollectionMacros\Macros\Prioritize::class,
            'recursive' => \Spatie\CollectionMacros\Macros\Recursive::class,
            'rotate' => \Spatie\CollectionMacros\Macros\Rotate::class,
            'second' => \Spatie\CollectionMacros\Macros\Second::class,
            'sectionBy' => \Spatie\CollectionMacros\Macros\SectionBy::class,
            'seventh' => \Spatie\CollectionMacros\Macros\Seventh::class,
            'simplePaginate' => \Spatie\CollectionMacros\Macros\SimplePaginate::class,
            'sixth' => \Spatie\CollectionMacros\Macros\Sixth::class,
            'sliceBefore' => \Spatie\CollectionMacros\Macros\SliceBefore::class,
            'tail' => \Spatie\CollectionMacros\Macros\Tail::class,
            'tenth' => \Spatie\CollectionMacros\Macros\Tenth::class,
            'third' => \Spatie\CollectionMacros\Macros\Third::class,
            'toPairs' => \Spatie\CollectionMacros\Macros\ToPairs::class,
            'transpose' => \Spatie\CollectionMacros\Macros\Transpose::class,
            'try' => \Spatie\CollectionMacros\Macros\TryCatch::class,
            'validate' => \Spatie\CollectionMacros\Macros\Validate::class,
            'weightedRandom' => \Spatie\CollectionMacros\Macros\WeightedRandom::class,
            'withSize' => \Spatie\CollectionMacros\Macros\WithSize::class,
        ];
    }
} # class

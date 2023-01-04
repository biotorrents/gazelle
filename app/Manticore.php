<?php

declare(strict_types=1);


/**
 * Gazelle\Manticore
 *
 * Uses Sphinx as the backend for now.
 * Plans to replace with the Manticore fork.
 *
 * Deprecates these legacy classes:
 *  - Sphinxql
 *  - SphinxqlQuery
 *  - SphinxqlResult
 *  - TorrentSearch
 *
 * @see https://github.com/FoolCode/SphinxQL-Query-Builder
 * @see https://manual.manticoresearch.com/Introduction
 * @see https://github.com/biotorrents/gazelle/issues/41
 */

namespace Gazelle;

class Manticore
{
    # library tools
    private $connection = null;
    private $queryLanguage = null;
    private $helper = null;
    private $percolate = null;

    # hash algo for cache keys
    private $algorithm = "sha3-512";

    # cache settings
    private $cachePrefix = "manticore_";
    private $cacheDuration = 60; # one minute

    # raw search terms
    private $rawSearchTerms = [];

    # the queryLanguage object
    private $query = null;

    # debug metadata
    private $debug = null;

    /** */

    # indices to search
    private $indices = [
        "torrents" => ["torrents_base", "torrents_delta"],
        "requests" => ["requests_base", "requests_delta"],
        "collections" => ["collections_base", "collections_delta"],
    ];

    # map of search form fields => index fields
    private $searchFields = [
        # torrents search
        "simpleSearch" => "*",
        "complexSearch" => "*",

        "numbers" => ["identifier", "version"],
        "year" => ["year"],

        "location" => ["workgroup", "location"],
        "creator" => "creatorList", # todo!

        "description" => ["torrentDescription", "groupDescription"],
        "fileList" => ["fileList", "infoHash"],

        "platforms" => "platform",
        "formats" => "format",
        "archives" => "archive",

        "scope" => "scope",
        "alignment" => "alignment",
        "leechStatus" => "leechStatus",
        "license" => "license",
        "sizeMin" => null,
        "sizeMax" => null,
        "sizeUnit" => "size",

        "tagList" => "tagList",
        "tagsType" => "", # todo

        "categories" => "categoryId",
        "orderBy" => null,
        "orderWay" => null,
        "groupResults" => null,

        # requests search
        # todo

        # collections search
        # todo
    ];

    # map of sort mode => index field for sorting
    private $sortOrders = [
        #"identifier" => "cataloguenumber", # todo?
        "leechers" => "leechers",
        "random" => "rand()",
        "seeders" => "seeders",
        "size" => "size",
        "snatched" => "snatches",
        "timeAdded" => "id",
        "year" => "year",
    ];


    /**
     * __construct
     */
    public function __construct()
    {
        $app = \App::go();

        try {
            # https://github.com/FoolCode/SphinxQL-Query-Builder#connection
            $this->connection = new \Foolz\SphinxQL\Drivers\Pdo\Connection();
            $this->connection->setParams([
                "host" => $app->env->getPriv("manticoreHost"),
                "port" => $app->env->getPriv("manticorePort"),
            ]);

            # https://github.com/FoolCode/SphinxQL-Query-Builder#sphinxql
            $this->queryLanguage = new \Foolz\SphinxQL\SphinxQL($this->connection);

            # https://github.com/FoolCode/SphinxQL-Query-Builder#helper
            $this->helper = new \Foolz\SphinxQL\Helper($this->connection);

            # https://github.com/FoolCode/SphinxQL-Query-Builder#percolate
            $this->percolate = new \Foolz\SphinxQL\Percolate($this->connection);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * search
     *
     * Search an index.
     * Example usage:
     *
     * $query = (new SphinxQL($conn))->select('column_one', 'colume_two')
     *   ->from('index_ancient', 'index_main', 'index_delta')
     *   ->match('comment', 'my opinion is superior to yours')
     *   ->where('banned', '=', 1);
     *
     * $result = $query->execute();
     *
     * @param string $what maps to an array of indices
     * @param array $data typically a post request
     * @return array if you're on the dot
     */
    public function search(string $what, array $data = []): array
    {
        $app = \App::go();

        # temporary
        if ($what === "collections") {
            throw new \Exception("not implemented");
        }

        # return cached if available
        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode($data));
        $cacheHit = $app->cacheOld->get_value($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # sanity check
        $allowedIndices = array_keys($this->indices);
        if (!in_array($what, $allowedIndices)) {
            throw new \Exception("expected one of " . implode(", ", $allowedIndices) . ", got {$what}");
        }

        # raw search terms
        $this->rawSearchTerms = $data;
        #!d($this->rawSearchTerms);

        # start the query
        $this->query = $this->queryLanguage
            ->select("*")
            ->from($this->indices[$what]);

        # orderBy and orderWay
        $orderBy = $data["orderBy"] ??= "timeAdded";
        $orderWay = $data["orderWay"] ??= "desc";

        unset($data["orderBy"]);
        unset($data["orderWay"]);

        # random order fix
        if ($orderBy === "random") {
            $orderWay = null;
        }

        $this->sortOrders[$orderBy] ??= null;
        if ($this->sortOrders[$orderBy]) {
            $this->query->orderBy($this->sortOrders[$orderBy], $orderWay);
        }

        # groupBy
        $groupBy = $data["groupResults"] ??= true;
        unset($data["groupResults"]);

        # random order fix
        if ($groupBy && $orderBy !== "random") {
            $this->query->groupBy("groupid");
        }

        /** */

        # does the heavy lifting of adding clauses
        # THIS IS THE ESSENTIAL QUERY FILTER FUNCTION
        $this->query = $this->processSearchTerms($data);

        /** */

        # debug
        if ($app->env->dev) {
            $this->debug = $this->query->enqueue(
                $this->helper->showMeta()
            );
            #!d($this->debug);
        }

        try {
            # execute the statement
            $resultSet = $this->query->execute();
            $results = $resultSet->fetchAllAssoc();

            $app->cacheOld->cache_value($cacheKey, $results, $this->cacheDuration);
            return $results;
        } catch (\Exception $e) {
            #$app->debug["sphinx"] = $e->getMessage();
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * processSearchTerms
     *
     * Look at the search terms and see what to do with them.
     *
     * @param array $data array with search terms
     * @return $this->queryLanguage
     */
    private function processSearchTerms(array $data = []): \Foolz\SphinxQL\SphinxQL
    {
        foreach ($data as $key => $value) {
            $this->searchFields[$key] ??= null;
            if ($this->searchFields[$key]) {
                $this->query = $this->processSearchTerm($key, $value);
            }
        }

        #$this->post_process();
        return $this->query;
    }


    /**
     * processSearchTerm
     *
     * Look at a search term and see what to do with it.
     *
     * @param string $key name of the search field
     * @param string|array $value search expression for the field
     * @return $this->queryLanguage
     */
    private function processSearchTerm(string $key, string|array $value): \Foolz\SphinxQL\SphinxQL
    {
        if (!is_array($value)) {
            $value = trim(strval($value));
        }

        # empty
        if (empty($value)) {
            return $this->query;
        }

        /** */

        /**
         * alignment
         */
        if ($key === "alignment") {
            $this->query->where("aligned", intval($value));

            return $this->query;
        }

        /**
         * categories
         */
        if ($key === "categories") {
            # do nothing
            if (!is_array($value)) {
                return $this->query;
            }

            $this->query->where("categoryId", "in", array_keys($value));

            return $this->query;
        } # if ($key === "categories")

        /**
         * fileList: phrase boundary limits partial hits
         */
        if ($key === "fileList") {
            #$value = "{$value}~20";
            $this->query->match("fileList", $value);

            return $this->query;
        }

        /**
         * leechStatus
         */
        if ($key === "leechStatus") {
            $value = intval($value);

            # freeLeech
            if ($value === 1) {
                $this->query->where("leechStatus", 1);

                return $this->query;
            }

            # neutralLeech
            if ($value === 2) {
                $this->query->where("leechStatus", 2);

                return $this->query;
            }

            # either
            if ($value === 3) {
                $this->query->where("leechStatus", "in", [1, 2]);

                return $this->query;
            }

            /*
            if ($value >= 0 && $value < 3) {
                $this->query->where("leechStatus", $value);

                return $this->query;
            }
            */
        } # if ($key === "leechStatus")

        /**
         * sizeUnit
         */
        if ($key === "sizeUnit") {
            $sizeMin = intval(($this->rawSearchTerms["sizeMin"] ?? 0) * (1024 ** $value));
            $sizeMax = intval(min(PHP_INT_MAX, ($this->rawSearchTerms["sizeMax"] ?? INF) * (1024 ** $value)));
            #!d($sizeMin, $sizeMax);

            $this->query->where("size", "between", [$sizeMin, $sizeMax]);

            return $this->query;
        } # if ($key === "sizeUnit")

        /**
         * tagList
         */
        if ($key === "tagList") {
            $value = str_replace($value, ".", "_");
            $this->query->match("taglist", $value);

            return $this->query;
        }

        /**
         * year
         */
        if ($key === "year") {
            $range = explode("-", $value);

            # exact year
            if (count($range) === 1) {
                $this->query->where("year", intval($range[0]));

                return $this->query;
            }

            # e.g., null - 2005
            if (empty($range[0]) && !empty($range[1])) {
                $this->query->where("year", "<=", intval($range[1]));

                return $this->query;
            }

            # e.g., 2005 - null
            if (!empty($range[0]) && empty($range[1])) {
                $this->query->where("year", ">=", intval($range[0]));

                return $this->query;
            }

            # e.g., 2005 - 2009
            $this->query->where("year", "between", [ intval($range[0]), intval($range[1]) ]);

            return $this->query;
        } # if ($key === "year")

        /**
         * platforms
         */
        if ($key === "platforms") {
            $this->query->where("platform", "in", $value);

            return $this->query;
        }

        /**
         * formats
         */
        if ($key === "formats") {
            $this->query->where("format", "in", $value);

            return $this->query;
        }

        /**
         * archives
         */
        if ($key === "archives") {
            $this->query->where("archive", "in", $value);

            return $this->query;
        }

        /**
         * normal
         */
        $this->searchFields[$key] ??= null;
        if ($this->searchFields[$key]) {
            $this->query->match($this->searchFields[$key], $value);

            return $this->query;
        } # if ($this->searchFields[$key])
    } # processSearchTerm
} # class

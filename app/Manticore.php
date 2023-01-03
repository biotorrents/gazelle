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
        "torrents" => ["torrents", "delta"],
        "requests" => ["requests", "requests_delta"],
        "log" => ["log", "log_delta"],
    ];

    # map of search form fields => index fields
    private $searchFields = [
        # main torrent search
        "simpleSearch" => "*",
        "complexSearch" => "*",

        "numbers" => ["cataloguenumber", "version"],
        "year" => ["year"],

        "location" => ["series", "studio"],
        "creator" => "artistname",

        "description" => "description",
        "fileList" => "filelist",

        "sequencePlatforms" => "media",
        "graphPlatforms" => "media",
        "imagePlatforms" => "media",
        "documentPlatforms" => "media",

        "nucleoSeqFormats" => "container",
        "protSeqFormats" => "container",
        "xmlFormats" => "container",
        "rasterFormats" => "container",
        "vectorFormats" => "container",
        "otherFormats" => "container",

        "scope" => "resolution",
        "alignment" => "censored",
        "leechStatus" => "freetorrent",
        "license" => "codec",
        "sizeMin" => null,
        "sizeMax" => null,
        "sizeUnit" => "size",

        "tagList" => "taglist",
        "tagsType" => "", # todo

        "categories" => "categoryid",
        "orderBy" => null,
        "orderWay" => null,
        "groupResults" => null,

        # request search
        # todo

        # log search
        # todo
    ];

    # map of sort mode => index field for sorting
    private $sortOrders = [
        #"identifier" => "cataloguenumber", # todo?
        "leechers" => "leechers",
        "random" => "rand()",
        "seeders" => "seeders",
        "size" => "size",
        "snatched" => "snatched",
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
                "host" => $app->env->manticoreHost,
                "port" => $app->env->manticorePort,
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

        # return cached if available
        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode($data));
        $cacheHit = $app->cacheOld->get_value($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
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
            $this->query->where("censored", intval($value));

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

            $this->query->where("categoryid", "in", array_keys($value));

            return $this->query;
        } # if ($key === "categories")

        /**
         * fileList: phrase boundary limits partial hits
         */
        if ($key === "fileList") {
            $value = "{$value}~20";
            $this->query->match("filelist", $value);

            return $this->query;
        }

        /**
         * leechStatus
         */
        if ($key === "leechStatus") {
            $value = intval($value);

            if ($value === 3) {
                $this->query->where("freetorrent", 0);

                return $this->query;
            }

            if ($value >= 0 && $value < 3) {
                $this->query->where("freetorrent", $value);

                return $this->query;
            }
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
         * normal
         */
        $this->searchFields[$key] ??= null;
        if ($this->searchFields[$key]) {
            $this->query->match($this->searchFields[$key], $value);

            return $this->query;
        } # if ($this->searchFields[$key])
    } # processSearchTerm
} # class

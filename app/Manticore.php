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
    public $connection = null;
    public $queryLanguage = null;
    public $helper = null;
    public $percolate = null;

    # indices to search
    public $indices = [
        "torrents" => ["torrents", "delta"],
        "requests" => ["requests", "requests_delta"],
        "log" => ["log", "log_delta"],
    ];

    # map of search form fields => index fields
    public $searchFields = [
        "simpleSearch" => "*",
        "complexSearch" => "*",

        "numbers" => ["cataloguenumber", "version"],
        "year" => ["year"],

        "location" => ["series", "studio"],
        "creator" => "artistname",

        "description" => "description",
        "fileList" => "filelist",

        "sequencePlatform" => "media",
        "graphPlatform" => "media",
        "imagePlatform" => "media",
        "documentPlatform" => "media",

        "nucleoSeqFormat" => "container",
        "protSeqFormat" => "container",
        "xmlFormat" => "container",
        "rasterFormat" => "container",
        "vectorFormat" => "container",
        "otherFormat" => "container",

        "scope" => "resolution",
        "alignment" => "censored",
        "leechStatus" => "freetorrent",
        "license" => "codec",
        "sizeMin" => "", # todo
        "sizeMax" => "", # todo
        "sizeUnit" => "", # todo

        "tagList" => "taglist",
        "tagsType" => "", # todo

        "categories" => "categoryid",
        "orderBy" => "", # todo
        "orderWay" => "", # todo
        "groupResults" => "", # todo
    ];

    # attribute fields
    public $attributeFields = [
        "categories" => null,
        "censored" => null,
        "leechStatus" => null,
        "sizeUnit" => null,
        "year" => null,
    ];

    # map of sort mode => attribute name for ungrouped torrent page
    public $sortOrders = [
        "identifier" => "cataloguenumber",
        "leechers" => "leechers",
        "random" => true,
        "seeders" => "seeders",
        "size" => "size",
        "snatched" => "snatched",
        "timeAdded" => "id",
        "year" => "year",
    ];

    # raw search terms
    public $rawSearchTerms = [];


    /**
     * __construct
     */
    public function __construct(array $data = [])
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

            # raw search terms
            $this->rawSearchTerms = $data;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * searchTorrents
     *
     * Search the torrents indices.
     */
    public function searchTorrents(array $terms = [])
    {
        /*
        # example usage
        $query = (new SphinxQL($conn))->select('column_one', 'colume_two')
            ->from('index_ancient', 'index_main', 'index_delta')
            ->match('comment', 'my opinion is superior to yours')
            ->where('banned', '=', 1);

        $result = $query->execute();
        */

        $query = $this->queryLanguage
            ->select("*")
            ->from($this->indices["torrents"]);

        # does the heavy lifting of adding clauses
        $query = $this->processSearchTerms($query, $terms);

        $resultSet = $query->execute();
        $results = $resultSet->fetchAllAssoc();

        return $results;
    }


    /**
     * searchRequests
     *
     * Search the requests indices.
     */
    public function searchRequests(array $terms = [])
    {
        $indices = $this->indices["requests"];
        throw new \Exception("not implemented");
    }


    /**
     * searchLog
     *
     * Search the log indices.
     */
    public function searchLog(array $terms = [])
    {
        $indices = $this->indices["log"];
        throw new \Exception("not implemented");
    }


    /** private methods */


    /**
     * processSearchTerms
     *
     * Look at each search term and figure out what to do with it.
     *
     * @param array $terms array with search terms
     */
    private function processSearchTerms($query, array $terms = [])
    {
        foreach ($terms as $key => $value) {
            # search field
            $this->searchFields[$key] ??= null;
            if ($this->searchFields[$key]) {
                $query = $this->processSearchField($query, $key, $value);
            }

            # attribute field
            $this->attributeFields[$key] ??= null;
            if ($this->attributeFields[$key]) {
                $query = $this->processAttributeField($query, $key, $value);
            }
        }

        #$this->post_process();
        return $query;
    }


    /**
     * processSearchField
     *
     * Look at a fulltext search term and figure out if it needs special treatment
     *
     * @param string $key name of the search field
     * @param string $value search expression for the field
     */
    private function processSearchField($query, string $key, string $value)
    {
        $value = trim(strval($value));

        # empty
        if (empty($value)) {
            return $query;
        }

        # fileList: phrase boundary limits partial hits
        if ($key === "fileList") {
            $value = "{$value}~20";
            $query->match("filelist", $value);

            return $query;
        }

        # tagList: prepare tag searches
        if ($key === "tagList") {
            $value = str_replace($value, ".", "_");
            $query->match("taglist", $value);

            return $query;
        }

        # normal
        $this->searchFields[$key] ??= null;
        if ($this->searchFields[$key]) {
            $query->match($this->searchFields[$key], $value);

            return $query;
        }
    }


    /**
     * processAttributeField
     *
     * Process attribute filters and store them in case we need to post-process grouped results.
     *
     * @param string $attribute name of the attribute to filter against
     * @param mixed $value the filter's condition for a match
     */
    private function processAttributeField($query, string $key, string $value)
    {
        $value = trim(strval($value));

        # empty
        if (empty($value)) {
            return $query;
        }

        # year
        if ($key === "year") {
            $range = explode("-", $value);

            # exact year
            if (count($range) === 1) {
                $query->where("year", $range[0]);

                return $query;
            }

            # e.g., null - 2005
            if (empty($range[0]) && !empty($range[1])) {
                $query->where("year", "<=", $range[1]);

                return $query;
            }

            # e.g., 2005 - null
            if (!empty($range[0]) && empty($range[1])) {
                $query->where("year", ">=", $range[0]);

                return $query;
            }

            # e.g., 2005 - 2009
            $query->where("year", "between", [ $range[0], $range[1] ]);

            return $query;
        } # if ($key === "year")

        # sizeUnit
        if ($key === "sizeUnit") {
            $sizeMin = intval(($this->rawSearchTerms["size_min"] ?? 0) * (1024 ** $value));
            $sizeMax = intval(min(PHP_INT_MAX, ($this->rawSearchTerms["sizeMax"] ?? INF) * (1024 ** $value)));

            $query->where("size", "between", [$sizeMin, $sizeMax ]);

            return $query;
        } # if ($key === "sizeUnit")

        # leechStatus
        if ($key === "leechStatus") {
            $value = intval($value);

            if ($value === 3) {
                $query->where("freetorrent", 0);

                return $query;
            }

            if ($value >= 0 && $value < 3) {
                $query->where("freetorrent", $value);

                return $query;
            }
        } # if ($key === "leechStatus")

        # categories
        if ($key === "categories") {
            if (!is_array($value)) {
                $value = array_fill_keys(explode("|", $value), 1);
            }

            $categoryFilter = [];
            foreach (array_keys($value) as $categoryId) {
                $categoryFilter[] = $categoryId;
            }

            $query->where("categoryid", $categoryFilter);

            return $query;
        } # if ($key === "categories")

        # check if the value is valid
        $this->attributeFields[$key] ??= null;
        if ($this->attributeFields[$key]) {
            $query->where($key, $value);

            return $query;
        } # if ($this->attributeFields[$key])
    } # processAttributeField
} # class

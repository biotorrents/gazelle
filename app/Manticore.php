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
        "sizeMin" => null,
        "sizeMax" => null,
        "sizeUnit" => "size",

        "tagList" => "taglist",
        "tagsType" => "", # todo

        "categories" => "categoryid",
        "orderBy" => "", # todo
        "orderWay" => "", # todo
        "groupResults" => "", # todo
    ];

    # attribute fields
    public $attributeFields = [
        "categoryid" => null,
        "censored" => null,
        "freetorrent" => null,
        #"sizeUnit" => null,
        #"year" => null,
    ];

    # map of sort mode => attribute name for ungrouped torrent page
    public $sortOrders = [
        #"identifier" => "cataloguenumber", # todo?
        "leechers" => "leechers",
        "random" => "rand()",
        "seeders" => "seeders",
        "size" => "size",
        "snatched" => "snatched",
        "timeAdded" => "id",
        "year" => "year",
    ];

    # raw search terms
    public $rawSearchTerms = [];

    # the queryLanguage object
    public $query = null;

    # debug metadata
    public $debug = null;


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
     */
    public function search(string $what, array $terms = [])
    {
        $app = \App::go();

        # sanity check
        $allowedIndices = array_keys($this->indices);
        if (!in_array($what, $allowedIndices)) {
            throw new \Exception("expected one of " . implode(", ", $allowedIndices) . ", got {$what}");
        }

        # raw search terms
        $this->rawSearchTerms = $terms;
        !d($this->rawSearchTerms);

        # start the query
        $this->query = $this->queryLanguage
            ->select("*")
            ->from($this->indices[$what]);
        #!d($this->query);exit;


        # orderBy and orderWay
        $orderBy = $terms["orderBy"] ??= "timeAdded";
        $orderWay = $terms["orderWay"] ??= "desc";
        #!d($orderBy, $orderWay);exit;

        unset($terms["orderBy"]);
        unset($terms["orderWay"]);

        # random order fix
        if ($orderBy === "random") {
            $orderWay = null;
        }

        $this->sortOrders[$orderBy] ??= null;
        if ($this->sortOrders[$orderBy]) {
            $this->query->orderBy($this->sortOrders[$orderBy], $orderWay);
        }

        # does the heavy lifting of adding clauses
        $this->query = $this->processSearchTerms($terms);

        # debug
        if ($app->env->dev) {
            $this->debug = $this->query->enqueue(
                $this->helper->showMeta()
            );
            #!d($this->debug);
        }

        $resultSet = $this->query->execute();
        $results = $resultSet->fetchAllAssoc();

        return $results;
    }


    /** private methods */


    /**
     * processSearchTerms
     *
     * Look at each search term and figure out what to do with it.
     *
     * @param array $terms array with search terms
     */
    private function processSearchTerms(array $terms = []): \Foolz\SphinxQL\SphinxQL
    {
        foreach ($terms as $key => $value) {
            # search field
            $this->searchFields[$key] ??= null;
            if ($this->searchFields[$key]) {
                $this->query = $this->processSearchField($key, $value);
            }

            # attribute field
            $this->attributeFields[$key] ??= null;
            if ($this->attributeFields[$key]) {
                $this->query = $this->processAttributeField($key, $value);
            }
        }

        #$this->post_process();
        return $this->query;
    }


    /**
     * processSearchField
     *
     * Look at a fulltext search term and figure out if it needs special treatment
     *
     * @param string $key name of the search field
     * @param string $value search expression for the field
     */
    private function processSearchField(string $key, string $value): \Foolz\SphinxQL\SphinxQL
    {
        $value = trim(strval($value));

        # empty
        if (empty($value)) {
            return $this->query;
        }


        # fileList: phrase boundary limits partial hits
        if ($key === "fileList") {
            $value = "{$value}~20";
            $this->query->match("filelist", $value);

            return $this->query;
        }


        # tagList: prepare tag searches
        if ($key === "tagList") {
            $value = str_replace($value, ".", "_");
            $this->query->match("taglist", $value);

            return $this->query;
        }


        # year: not an attribute
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


        # alignment: not an attribute
        if ($key === "alignment") {
            $this->query->where("censored", intval($value));

            return $this->query;
        }


        # leechStatus: not an attribute
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


        # sizeUnit
        if ($key === "sizeUnit") {
            $sizeMin = intval(($this->rawSearchTerms["sizeMin"] ?? 0) * (1024 ** $value));
            $sizeMax = intval(min(PHP_INT_MAX, ($this->rawSearchTerms["sizeMax"] ?? INF) * (1024 ** $value)));
            #!d($sizeMin, $sizeMax);

            $this->query->where("size", "between", [$sizeMin, $sizeMax]);

            return $this->query;
        } # if ($key === "sizeUnit")


        # normal
        $this->searchFields[$key] ??= null;
        if ($this->searchFields[$key]) {
            $this->query->match($this->searchFields[$key], $value);

            return $this->query;
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
    private function processAttributeField(string $key, string $value): \Foolz\SphinxQL\SphinxQL
    {
        $value = trim(strval($value));

        # empty
        if (empty($value)) {
            return $this->query;
        }


        # categories
        if ($key === "categories") {
            if (!is_array($value)) {
                $value = array_fill_keys(explode("|", $value), 1);
            }

            $categoryFilter = [];
            foreach (array_keys($value) as $categoryId) {
                $categoryFilter[] = $categoryId;
            }

            $this->query->where("categoryid", $categoryFilter);

            return $this->query;
        } # if ($key === "categories")

        # check if the value is valid
        $this->attributeFields[$key] ??= null;
        if ($this->attributeFields[$key]) {
            $this->query->where($key, $value);

            return $this->query;
        } # if ($this->attributeFields[$key])
    } # processAttributeField
} # class

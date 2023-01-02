<?php

declare(strict_types=1);


/**
 * Gazelle\Manticore
 *
 * Uses Sphinx as the backend for now.
 * Plans to replace with the Manticore fork.
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
        "nucleoSeqFormat" => "container",

        "scope" => "resolution",
        "alignment" => "censored",
        "leechStatus" => "freetorrent",
        "license" => "codec",
        "sizeMin" => "", # todo
        "sizeMax" => "", # todo
        "sizeUnit" => "", # todo

        "tagList" => "taglist",
        "tagsType" => "", # todo

        "categories" => "", # todo
        "orderBy" => "", # todo
        "orderWay" => "", # todo
        "groupResults" => "", # todo
    ];

    # map of sort mode => attribute name for ungrouped torrent page
    public $sortOrders = [
        "identifier" => "cataloguenumber",
        "leechers" => "leechers",
        "random" => 1,
        "seeders" => "seeders",
        "size" => "size",
        "snatched" => "snatched",
        "timeAdded" => "id",
        "year" => "year",
    ];

    /*
    # map of sort mode => aggregate expression required for some grouped sort orders
    private $aggregateExpressions = [
        "leechers" => "sum(leechers) as sumleechers",
        "seeders" => "sum(seeders) as sumseeders",
        "size" => "max(size) as maxsize",
        "snatched" => "sum(snatched) as sumsnatched",
    ];
    */

    /*
    # list of fields that can be used for fulltext searches
    private $groupFields = [
        "GroupTitle2" => 1, # organism
        "Groupnamejp" => 1, # strain
        "Version" => 1, # version
        "advgroupname" => 1,
        "archive" => 0, # todo
        "artistname" => 1, # author
        "cataloguenumber" => 1, # accession number
        "codec" => 1, # license
        "container" => 1, # format
        "description" => 1, # not group desc
        "filelist" => 1,
        "groupname" => 1, # title
        "location" => 1, # combined above
        "media" => 1, # platform
        "numbers" => 1, # combined above
        "resolution" => 1, # scope
        "search" => 1,
        "series" => 1, # location
        "studio" => 1, # department/lab
        "taglist" => 1
    ];
    */

    /*
    # list of torrent-specific fields that can be used for filtering
    private $torrentFields = [
        "censored" => 1,
        "description" => 1,
        "encoding" => 1,
        "filelist" => 1,
        "format" => 1,
        "media" => 1
    ];
    */



    /**
     * __construct
     */
    public function __construct(array $options = [])
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

        foreach ($terms as $key => $value) {
            $query->match($this->searchFields[$key], $value);
        }

        $resultSet = $query->execute();
        # !d($results);exit;

        $results = $resultSet->fetchAllAssoc();


        return $results;

        $indices = $this->indices["torrents"];
        $searchTerms = $this->processSearchTerms($terms);

        foreach ($terms as $key => $value) {
            $this->processSearchTerm($term);
        }
    }


    /**
     * searchRequests
     *
     * Search the requests indices.
     */
    public function searchRequests(array $terms = [])
    {
        $indices = $this->indices["requests"];
    }


    /**
     * searchLog
     *
     * Search the log indices.
     */
    public function searchLog(array $terms = [])
    {
        $indices = $this->indices["log"];
    }


    /** private methods */


    /**
     * processSearchTerms
     *
     * Look at each search term and figure out what to do with it.
     *
     * @param array $terms array with search terms
     */
    private function processSearchTerms(array $terms = [])
    {
        foreach ($terms as $key => $value) {
            # search field
            $this->searchFields[$key] ??= null;
            if ($this->searchFields[$key]) {
                $this->process_field($key, $value);
            }

            # search attribute
            $this->searchAttributes[$key] ??= null;
            if ($this->searchAttributes[$key]) {
                $this->process_attribute($key, $value);
            }

            $this->RawTerms[$key] = $value;
        }

        $this->post_process();
    }
} # class

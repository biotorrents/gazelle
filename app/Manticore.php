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

    # map of sort mode => attribute name for ungrouped torrent page
    public $sortOrders = [
        "cataloguenumber" => "cataloguenumber",
        "leechers" => "leechers",
        "random" => 1,
        "seeders" => "seeders",
        "size" => "size",
        "snatched" => "snatched",
        "time" => "id",
        "year" => "year",
    ];

    # map of sort mode => aggregate expression required for some grouped sort orders
    private $aggregateExpressions = [
        "leechers" => "sum(leechers) as sumleechers",
        "seeders" => "sum(seeders) as sumseeders",
        "size" => "max(size) as maxsize",
        "snatched" => "sum(snatched) as sumsnatched",
    ];

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

    # list of torrent-specific fields that can be used for filtering
    private $torrentFields = [
        "censored" => 1,
        "description" => 1,
        "encoding" => 1,
        "filelist" => 1,
        "format" => 1,
        "media" => 1
    ];

    # some form field names don't match the ones in the index
    private static $formsToFields = [
        # todo: keep testing the granularity of filter combos
        "advgroupname" => "*", # todo: fix this ;)
        "location" => "(studio,series)",
        "numbers" => "(cataloguenumber,version)",
        "search" => "*",
        #"search" => "(groupname,GroupTitle2,groupnamejp,artistname,studio,series,cataloguenumber,yearfulltext)",
        #"advgroupname" => "(groupname,GroupTitle2,groupnamejp)",
    ];


    /**
     * __construct
     */
    public function __construct(array $options = [])
    {
        $app = \App::go();

        # establish connection
        $this->connection = new \Foolz\SphinxQL\Drivers\Pdo\Connection();
        $this->connection->setParams([
            "host" => $app->env->manticoreHost,
            "port" => $app->env->manticorePort,
        ]);

        if (!$this->connection) {
            throw new \Exception("oops");
        }

        # query language
        $this->queryLanguage = new \Foolz\SphinxQL\SphinxQL($this->connection);

        # helper
        $this->helper = new \Foolz\SphinxQL\Helper($this->connection);

        # percolate
        $this->percolate = new \Foolz\SphinxQL\Percolate($this->connection);

        /*
        $query = (new SphinxQL($conn))->select('column_one', 'colume_two')
            ->from('index_ancient', 'index_main', 'index_delta')
            ->match('comment', 'my opinion is superior to yours')
            ->where('banned', '=', 1);

        $result = $query->execute();
        */
    }
} # classs

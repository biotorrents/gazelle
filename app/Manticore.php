<?php

declare(strict_types=1);


/**
 * Gazelle\Manticore
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

    # cache settings
    private $cachePrefix = "manticore:";
    private $cacheDuration = "1 minute";

    # raw search terms
    private $rawSearchTerms = [];

    # the queryLanguage object
    private $query = null;

    # debug metadata
    private $debug = null;

    /** */

    # indices to search
    private $indices = [
        "torrents" => ["torrents_main", "torrents_delta"],
        "requests" => ["requests_main", "requests_delta"],
        "collections" => ["collections_main", "collections_delta"],
    ];

    # map of search form fields => index fields
    private $searchFields = [
        # torrents search
        "simpleSearch" => "*",
        "complexSearch" => ["title", "subject", "object"],

        "numbers" => ["identifier", "version"],
        "year" => ["year"],

        "location" => ["workgroup", "location"],
        "creator" => "creatorList",

        "description" => ["torrentDescription", "groupDescription", "openaiDescription"],
        "fileList" => ["fileList", "infoHash"],

        "platforms" => "platform",
        "formats" => "format",
        "archives" => "archive",

        "scopes" => "scope",
        "alignment" => "alignment",
        "leechStatus" => "leechStatus",
        "licenses" => "license",
        "sizeMin" => null,
        "sizeMax" => null,
        "sizeUnit" => "size",

        "categories" => "categoryId",
        "tagList" => "tagList",
        "tagsType" => null,

        "orderBy" => null,
        "orderWay" => null,
        "groupResults" => null,

        "openaiContent" => null,

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
        $app = \Gazelle\App::go();

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
        } catch (\Throwable $e) {
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
        $app = \Gazelle\App::go();

        # start debug
        $app->debug["time"]->startMeasure("manticore", "manticore search");

        # return cached if available
        $cacheKey = $this->cachePrefix . hash($app->env->cacheAlgorithm, json_encode($data));
        $cacheHit = $app->cache->get($cacheKey);

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

        # disable openai
        $data["openaiContent"] ??= null;
        if (!$data["openaiContent"]) {
            $this->searchFields["description"] = ["torrentDescription", "groupDescription"];
        }

        # start the query
        $this->query = $this->queryLanguage
            ->select(["id"])
            #->select("*") # debug
            ->from($this->indices[$what]);

        /*
        # pagination
        $data["page"] ??= 1;
        $limit = $app->user->extra["siteOptions"]["searchPagination"] ?? 20;
        $offset = ($data["page"] - 1) * $limit;
        */

        # just get all results and paginate in the controllers
        $offset = 0;
        $this->query->limit(
            $offset,
            $app->env->getPriv("manticoreMaxMatches")
        );

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
            $this->query->groupBy("id");
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

            # end debug
            $app->debug["time"]->stopMeasure("manticore", "manticore search");

            $app->cache->set($cacheKey, $results, $this->cacheDuration);
            return $results;
        } catch (\Throwable $e) {
            $app->debug["messages"]->addMessage("Gazelle\Manticore->search(): " . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * processSearchTerms
     *
     * Look at the search terms and see what to do with them.
     *
     * @param array $data array with search terms
     * @return $this->query
     */
    private function processSearchTerms(array $data = []): \Foolz\SphinxQL\SphinxQL
    {
        foreach ($data as $key => $value) {
            $this->searchFields[$key] ??= null;
            if ($this->searchFields[$key] && !empty($value)) {
                $this->query = $this->processSearchTerm($key, $value);
            }
        }

        return $this->query;
    }


    /**
     * processSearchTerm
     *
     * Look at a search term and see what to do with it.
     *
     * @param string $key name of the search field
     * @param string|array $value search expression for the field
     * @return $this->query
     */
    private function processSearchTerm(string $key, string|array $value): \Foolz\SphinxQL\SphinxQL
    {
        /**
         * alignment
         */
        if ($key === "alignment") {
            $this->query->where("alignment", intval($value));
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
         * leechStatus
         * todo: is this accurate?
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
            # none
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

            $this->query->where("size", "between", [$sizeMin, $sizeMax]);
            return $this->query;
        } # if ($key === "sizeUnit")

        /**
         * tagList: lazy af
         */
        if ($key === "tagList") {
            # include all tags
            $this->rawSearchTerms["tagsType"] ??= "includeTags";
            if ($this->rawSearchTerms["tagsType"] === "includeTags") {
                $value = implode(" ", $value);
                $value = preg_replace("/\./", "_", $value);

                $this->query->match("tagList", $value);
                return $this->query;
            }

            # exclude any tag
            if ($this->rawSearchTerms["tagsType"] === "excludeTags") {
                foreach ($value as $k => $v) {
                    # raw expression passed below
                    $value[$k] = \Gazelle\Text::esc("-{$v}");
                }

                $value = implode(" or ", $value);
                $value = preg_replace("/\./", "_", $value);
                $value = "{$value} alwaysMatches";

                $this->query->match("tagList", \Foolz\SphinxQL\SphinxQL::expr($value));
                return $this->query;
            }
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
        } # if ($key === "platforms")

        /**
         * formats
         */
        if ($key === "formats") {
            $this->query->where("format", "in", $value);
            return $this->query;
        } # if ($key === "formats")

        /**
         * archives
         */
        if ($key === "archives") {
            $this->query->where("archive", "in", $value);
            return $this->query;
        } # if ($key === "archives")

        /**
         * scopes
         */
        if ($key === "scopes") {
            $this->query->where("scope", "in", $value);
            return $this->query;
        } # if ($key === "scopes")

        /**
         * licenses
         */
        if ($key === "licenses") {
            $this->query->where("license", "in", $value);
            return $this->query;
        } #if ($key === "licenses")


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

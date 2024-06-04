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
    private string $cachePrefix = "manticore:";
    private string $cacheDuration = "1 minute";

    # raw search terms
    private array $rawSearchTerms = [];

    # the queryLanguage object
    private $query = null;

    # debug metadata
    private $debug = null;

    /** */

    # the current search index
    public ?string $index = null;

    # indices to search
    private array $indices = [
        "universalSearch" => ["torrents_group_main", "requests_main", "collages_main", "creators_main", "literature_main", "organizations_main"],
        "torrentGroups" => ["torrents_group_main", "torrents_group_delta"],
        "requests" => ["requests_main", "requests_delta"],
        "collages" => ["collages_main", "collages_delta"],
        "creators" => ["creators_main", "creators_delta"],
        "literature" => ["literature_main", "literature_delta"],
        "organizations" => ["organizations_main", "organizations_delta"],
    ];

    # map of search form fields => index fields
    private array $searchFields = [
        /*
        # torrents search: legacy
        "simpleSearch" => "*",
        "complexSearch" => ["torrentGroups_title", "torrentGroups_subject", "torrentGroups_object"],
        "numbers" => ["torrentGroups_identifier", "torrents_version"],
        "year" => ["torrentGroups_year"],
        "location" => ["torrentGroups_workgroup", "torrentGroups_location"],
        "creator" => "creatorList",
        "description" => ["torrents_description", "torrentGroups_description"],
        "fileList" => ["torrents_fileList", "torrents_infoHash"],
        "platforms" => "torrents_platform",
        "formats" => "torrents_format",
        "archives" => "torrents_archive",
        "scopes" => "torrents_scope",
        "alignment" => "torrents_alignment",
        "leechStatus" => "torrents_freeleechStatus",
        "licenses" => "torrents_license",
        "sizeMin" => null,
        "sizeMax" => null,
        "sizeUnit" => "torrents_dataSize",
        "categories" => "torrentGroups_categoryId",
        "tagList" => "torrentGroups_tags",
        "tagsType" => null,
        "orderBy" => null,
        "orderWay" => null,
        "groupResults" => null,
        */

        /** */

        # universalSearch
        "universalSearch" => [
            "torrentGroups_id", "torrentGroups_identifier", "torrentGroups_title", "torrentGroups_slug", "torrentGroups_subject", "torrentGroups_object",
            "requests_id", "requests_identifier", "requests_title", "requests_slug", "requests_subject", "requests_object",
            "collages_id", "collages_title", "collages_slug",
            "creators_id", "creators_orcid", "creators_semanticScholarId", "creators_name", "creators_slug", "creators_aliases",
            "literature_id", "literature_doi", "literature_semanticScholarId", "literature_title",
            "organizations_id", "organizations_rorId", "organizations_grid", "organizations_name", "organizations_acronym", "organizations_reverseGeocode",
        ],

        # torrentGroups
        "torrentGroups" => [
            "simpleSearch" => ["id", "torrentGroups_categoryId", "torrentGroups_revisionId", "torrentGroups_identifier", "torrentGroups_title", "torrentGroups_slug", "torrentGroups_subject", "torrentGroups_object", "torrentGroups_workgroup", "torrentGroups_location", "torrentGroups_year", "torrentGroups_description", "torrentGroups_picture", "torrentGroups_tags", "torrentGroups_createdAt", "torrentGroups_updatedAt", "torrentGroups_deletedAt"],
            "complexSearch" => ["id", "torrentGroups_identifier", "torrentGroups_title", "torrentGroups_slug", "torrentGroups_subject", "torrentGroups_object"],
            "numbers" => ["id", "torrentGroups_identifier", "torrents_version"],
            "dates" => ["torrentGroups_year", "torrentGroups_createdAt", "torrentGroups_updatedAt", "torrentGroups_deletedAt", "literature_year", "literature_publicationDate", "organizations_established", "requests_filledAt", "requests_lastVote", "torrents_lastAction"],
            "locations" => ["torrentGroups_workgroup", "torrentGroups_location", "creators_affiliations", "organizations_id", "organizations_rorId", "organizations_grid", "organizations_name", "organizations_acronym", "organizations_latitude", "organizations_longitude", "organizations_reverseGeocode", "organizations_country", "organizations_state", "organizations_city", "organizations_postalCode"],
            "creators" => ["torrentGroups_workgroup", "creators_id", "creators_orcid", "creators_semanticScholarId", "creators_name", "creators_slug", "creators_description", "creators_aliases", "creators_affiliations"],
            "descriptions" => ["torrentGroups_description", "collages_description", "creators_description", "literature_abstract", "requests_description", "torrents_description"],
            "files" => ["torrents_id", "torrents_infoHash", "torrents_filePath", "torrents_fileList"],
            "platforms" => ["torrents_platform"],
            "formats" => ["torrents_format"],
            "archives" => ["torrents_archive"],
            "scopes" => ["torrents_scope"],
            "alignment" => ["torrents_alignment"],
            "leechStatus" => ["torrents_upMultiplier", "torrents_downMultiplier", "torrents_freeleechStatus", "torrents_freeleechType"],
            "licenses" => ["torrents_license"],
            "sizeMin" => ["torrents_dataSize"],
            "sizeMax" => ["torrents_dataSize"],
            "sizeUnit" => ["torrents_dataSize"],
            "categories" => ["torrentGroups_categoryId", "collages_categoryId", "requests_categoryId"],
            "tags" => ["torrentGroups_tags", "collages_tags", "tags_id", "tags_name"],
            "orderBy" => null,
            "orderWay" => null,
        ],

        # requests
        "requests" => [
            "simpleSearch" => ["id", "requests_categoryId", "requests_userId", "requests_groupId", "requests_torrentId", "requests_filledById", "requests_filledAt", "requests_lastVote", "requests_identifier", "requests_title", "requests_slug", "requests_subject", "requests_object", "requests_description", "requests_picture", "requests_createdAt", "requests_updatedAt", "requests_deletedAt"],
            "complexSearch" => ["id", "requests_identifier", "requests_title", "requests_slug", "requests_subject", "requests_object"],
        ],

        # collages
        "collages" => [
            "simpleSearch" => ["id", "collages_categoryId", "collages_userId", "collages_title", "collages_slug", "collages_description", "collages_tags", "collages_torrentCount", "collages_subscriberCount", "collages_maximumGroups", "collages_groupsPerUser", "collages_isFeatured", "collages_isLocked", "collages_createdAt", "collages_updatedAt", "collages_deletedAt"],
            "complexSearch" => ["id", "collages_title", "collages_slug"],
        ],

        # creators
        "creators" => [
            "simpleSearch" => ["id", "creators_orcid", "creators_semanticScholarId", "creators_name", "creators_slug", "creators_description", "creators_aliases", "creators_affiliations", "creators_homepage", "creators_picture", "creators_hIndex", "creators_paperCount", "creators_citationCount", "creators_failCount", "creators_degreesOfSeparation", "creators_createdAt", "creators_updatedAt", "creators_deletedAt"],
            "complexSearch" => ["id", "creators_orcid", "creators_semanticScholarId", "creators_name", "creators_slug", "creators_aliases"],
        ],

        # literature
        "literature" => [
            "simpleSearch" => ["id", "literature_userId", "literature_doi", "literature_semanticScholarId", "literature_title", "literature_venue", "literature_journal", "literature_year", "literature_publicationDate", "literature_abstract", "literature_tldr", "literature_bibtex", "literature_influentialCitationCount", "literature_citationCount", "literature_referenceCount", "literature_isOpenAccess", "literature_openAccessPdf", "literature_failCount", "literature_degreesOfSeparation", "literature_createdAt", "literature_updatedAt", "literature_deletedAt"],
            "complexSearch" => ["id", "literature_doi", "literature_semanticScholarId", "literature_title", "literature_bibtex"],
        ],

        # organizations
        "organizations" => [
            "simpleSearch" => ["*"],
            "complexSearch" => ["organizations_rorId", "organizations_grid", "organizations_name", "organizations_acronym"],

            /*
            "simpleSearch" => ["id", "organizations_rorId", "organizations_grid", "organizations_userId", "organizations_name", "organizations_acronym", "organizations_established", "organizations_status", "organizations_relationships", "organizations_latitude", "organizations_longitude", "organizations_reverseGeocode", "organizations_country", "organizations_state", "organizations_city", "organizations_postalCode", "organizations_type", "organizations_homepage", "organizations_wikipedia", "organizations_failCount", "organizations_degreesOfSeparation", "organizations_createdAt", "organizations_updatedAt", "organizations_deletedAt"],
            "complexSearch" => ["id", "organizations_rorId", "organizations_grid", "organizations_name", "organizations_acronym"],
            */
        ],
    ];

    # map of sort mode => index field for sorting
    private array $sortOrders = [
        "leecherCount" => "torrents_leecherCount",
        "random" => "rand()",
        "seederCount" => "torrents_seederCount",
        "dataSize" => "torrents_dataSize",
        "snatchCount" => "torrents_snatchCount",
        "createdAt" => "torrents_createdAt",
        "year" => "torrentGroups_year",
    ];


    /**
     * __construct
     */
    public function __construct(string $index)
    {
        $app = App::go();

        try {
            # sanity check
            $allowedIndices = array_keys($this->indices);
            if (!in_array($index, $allowedIndices)) {
                throw new Exception("expected one of " . implode(", ", $allowedIndices) . ", got {$index}");
            }

            # https://github.com/FoolCode/SphinxQL-Query-Builder#connection
            $this->connection = new \Foolz\SphinxQL\Drivers\Pdo\Connection();
            $this->connection->setParams([
                "host" => $app->env->private("manticoreHost"),
                "port" => $app->env->private("manticorePort"),
            ]);

            # https://github.com/FoolCode/SphinxQL-Query-Builder#sphinxql
            $this->queryLanguage = new \Foolz\SphinxQL\SphinxQL($this->connection);

            # https://github.com/FoolCode/SphinxQL-Query-Builder#helper
            $this->helper = new \Foolz\SphinxQL\Helper($this->connection);

            # https://github.com/FoolCode/SphinxQL-Query-Builder#percolate
            $this->percolate = new \Foolz\SphinxQL\Percolate($this->connection);

            # set the index
            $this->index = $index ?? null;
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage());
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
     * @param array $data typically a post request
     * @return array if you're on the dot
     */
    public function search(array $data): array
    {
        $app = App::go();

        # start debug
        $app->debug["time"]->startMeasure("manticore", "manticore search");

        # return cached if available
        $cacheKey = $this->cachePrefix . "{$this->index}:" . hash($app->env->cacheAlgorithm, json_encode($data));
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            #return $cacheHit;
        }

        # raw search terms
        $this->rawSearchTerms = $data;

        # start the query
        $this->query = $this->queryLanguage
            #->select(["id"])
            ->select("*") # debug
            ->from($this->indices[$this->index]);

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
            $app->env->private("manticoreMaxMatches")
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
            throw new Exception($e->getMessage());
        }
    }


    /** */


    /**
     * processSearchTerms
     *
     * Look at the search terms and see what to do with them.
     *
     * @param array $data array with search terms
     * @return $this->query
     */
    private function processSearchTerms(array $data): \Foolz\SphinxQL\SphinxQL
    {
        foreach ($data as $key => $value) {
            $this->searchFields[$this->index][$key] ??= null;
            if ($this->searchFields[$this->index][$key] && !empty($value)) {
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
                    $value[$k] = Text::esc("-{$v}");
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
        $this->searchFields[$this->index][$key] ??= null;
        if ($this->searchFields[$this->index][$key]) {
            $this->query->match($this->searchFields[$this->index][$key], $value);
            return $this->query;
        } # if ($this->searchFields[$key])
    } # processSearchTerm


    /**
     * raw
     *
     * @param string $query
     * @return array
     */
    public function raw(string $query): array
    {
        $app = App::go();

        # start debug
        $app->debug["time"]->startMeasure("manticore", "manticore raw");

        try {
            $resultSet = $this->queryLanguage->query($query)->execute();
            $results = $resultSet->fetchAllAssoc();

            # end debug
            $app->debug["time"]->stopMeasure("manticore", "manticore raw");

            return $results;
        } catch (\Throwable $e) {
            $app->debug["messages"]->addMessage("Gazelle\Manticore->raw(): " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    } # raw


    /**
     * autocomplete
     *
     * @see https://manticoresearch.com/blog/simple-autocomplete-with-manticore/
     */
    public function autocomplete(string $query): array
    {
        $app = App::go();

        # start debug
        #$app->debug["time"]->startMeasure("manticore", "manticore autocomplete");

        #$query = "select id, title FROM torrent_groups_main WHERE match('@torrentGroups_title {$query}*') order by weight() desc";
        #$query = "SELECT HIGHLIGHT() FROM {$index} WHERE MATCH('{$query}');";
        $query = "call keywords('*{$query}*', '{$this->index}', 1 as stats, 'docs' as sort_mode)";
        $results = $this->raw($query);

        # end debug
        #$app->debug["time"]->stopMeasure("manticore", "manticore autocomplete");

        return $results;
    }
} # class

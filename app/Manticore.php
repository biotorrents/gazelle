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

use Foolz\SphinxQL\SphinxQL;
use Foolz\SphinxQL\Drivers\Mysqli\Connection;

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

    # search properties
    public ?string $context = null;
    public ?array $fields = null;
    public array $terms = [];
    public $query = null;
    public $debug = null;

    /** */

    # map of, e.g., [ Gazelle\TorrentGroups::$type => ["manticore", "indexMaps"] ]
    private array $indexMaps = [
        "collages" => ["collages_main", "collages_delta"],
        "creators" => ["creators_main", "creators_delta"],
        "literature" => ["literature_main", "literature_delta"],
        "organizations" => ["organizations_main", "organizations_delta"],
        "publications" => ["publications_main", "publications_delta"],
        "requests" => ["requests_main", "requests_delta"],
        "torrentGroups" => ["torrents_group_main", "torrents_group_delta"],
    ];

    # map of search form fields => index fields
    private array $fieldMaps = [
        # collages
        "collages" => [
            "simpleSearch" => ["collages_categoryId", "collages_userId", "collages_title", "collages_slug", "collages_description", "collages_tags", "collages_torrentCount", "collages_subscriberCount", "collages_maximumGroups", "collages_groupsPerUser", "collages_isFeatured", "collages_isLocked", "collages_createdAt", "collages_updatedAt", "collages_deletedAt"],
            "complexSearch" => ["collages_title", "collages_slug", "collages_description"],
            "orderBy" => [
                "random" => "rand()",
                "createdAt" => "id",
                "alphabetical" => "collages_title",
                "category" => "collages_categoryId",
                "torrentCount" => "collages_torrentCount",
                "subscriberCount" => "collages_subscriberCount",
            ],
        ],

        # creators
        "creators" => [
            "simpleSearch" => ["creators_userId", "creators_openAlexId", "creators_orcid", "creators_scopusId", "creators_semanticScholarId", "creators_name", "creators_slug", "creators_biography", "creators_aliases", "creators_affiliations", "creators_homepage", "creators_picture", "creators_wikipedia", "creators_hIndex", "creators_paperCount", "creators_citationCount", "creators_summaryStats", "creators_affiliationsOverTime", "creators_topics", "creators_concepts", "creators_countsByYear", "creators_degreesOfSeparation", "creators_failCount", "creators_updatedById", "creators_createdAt", "creators_updatedAt", "creators_deletedAt"],
            "complexSearch" => ["creators_openAlexId", "creators_orcid", "creators_scopusId", "creators_semanticScholarId", "creators_name", "creators_slug", "creators_aliases"],
            "orderBy" => [
                "random" => "rand()",
                "createdAt" => "id",
                "alphabetical" => "creators_name",
                "hIndex" => "creators_hIndex",
                "paperCount" => "creators_paperCount",
                "citationCount" => "torrents_citationCount",
            ],
        ],

        # literature
        "literature" => [
            "simpleSearch" => ["literature_userId", "literature_doi", "literature_openAlexId", "literature_semanticScholarId", "literature_title", "literature_slug", "literature_language", "literature_type", "literature_journal", "literature_bibtex", "literature_publicationDate", "literature_license", "literature_abstract", "literature_tldr", "literature_picture", "literature_primaryTopic", "literature_concepts", "literature_countsByYear", "literature_influentialCitationCount", "literature_citationCount", "literature_referenceCount", "literature_isOpenAccess", "literature_openAccessPdf", "literature_isRetracted", "literature_degreesOfSeparation", "literature_failCount", "literature_updatedById", "literature_createdAt", "literature_updatedAt", "literature_deletedAt"],
            "complexSearch" => ["literature_doi", "literature_openAlexId", "literature_semanticScholarId", "literature_title", "literature_slug", "literature_bibtex", "literature_abstract"],
            "orderBy" => [
                "random" => "rand()",
                "createdAt" => "id",
                "alphabetical" => "literature_title",
                "publicationDate" => "literature_publicationDate",
                "influentialCitationCount" => "literature_influentialCitationCount",
                "citationCount" => "literature_citationCount",
                "referenceCount" => "literature_referenceCount",
            ],
        ],

        # organizations
        "organizations" => [
            "simpleSearch" => ["organizations_userId", "organizations_grid", "organizations_openAlexId", "organizations_rorId", "organizations_wikidataId", "organizations_name", "organizations_slug", "organizations_acronym", "organizations_established", "organizations_status", "organizations_relationships", "organizations_repositories", "organizations_latitude", "organizations_longitude", "organizations_reverseGeocode", "organizations_country", "organizations_state", "organizations_city", "organizations_postalCode", "organizations_type", "organizations_homepage", "organizations_picture", "organizations_wikipedia", "organizations_summaryStats", "organizations_countsByYear", "organizations_topics", "organizations_concepts", "organizations_degreesOfSeparation", "organizations_failCount", "organizations_updatedById", "organizations_createdAt", "organizations_updatedAt", "organizations_deletedAt"],
            "complexSearch" => ["organizations_grid", "organizations_openAlexId", "organizations_rorId", "organizations_wikidataId", "organizations_name", "organizations_slug", "organizations_acronym", "organizations_reverseGeocode"],
            "orderBy" => [
                "random" => "rand()",
                "createdAt" => "id",
                "alphabetical" => "organizations_name",
                "established" => "organizations_established",
                "status" => "organizations_status",
                "type" => "organizations_type",
           ],
        ],

        # publications
        "publications" => [
            "simpleSearch" => ["publications_userId", "publications_issn", "publications_openAlexId", "publications_wikidataId", "publications_title", "publications_slug", "publications_homepage", "publications_picture", "publications_isOpenAccess", "publications_currentDoiCount", "publications_backfileDoiCount", "publications_totalDoiCount", "publications_summaryStats", "publications_topics", "publications_concepts", "publications_coverage", "publications_flags", "publications_countsByYear", "publications_doisIssuedByYear", "publications_degreesOfSeparation", "publications_failCount", "publications_updatedById", "publications_createdAt", "publications_updatedAt", "publications_deletedAt"],
            "complexSearch" => ["publications_issn", "publications_openAlexId", "publications_wikidataId", "publications_title", "publications_slug"],
            "orderBy" => [
                "random" => "rand()",
                "createdAt" => "id",
                "alphabetical" => "organizations_name",
                "established" => "organizations_established",
                "status" => "organizations_status",
                "type" => "organizations_type",
            ],
        ],


        # requests
        "requests" => [
            "simpleSearch" => ["requests_categoryId", "requests_userId", "requests_groupId", "requests_torrentId", "requests_filledById", "requests_filledAt", "requests_lastVote", "requests_identifier", "requests_title", "requests_slug", "requests_subject", "requests_object", "requests_description", "requests_picture", "requests_createdAt", "requests_updatedAt", "requests_deletedAt"],
            "complexSearch" => ["requests_identifier", "requests_title", "requests_slug", "requests_subject", "requests_object", "requests_description"],
            "orderBy" => [
                "random" => "rand()",
                "createdAt" => "id",
                "alphabetical" => "requests_title",
                "bounty" => "requests_bounty",
                "voteCount" => "requests_voteCount",
                "lastVote" => "requests_lastVote",
                "filledAt" => "requests_filledAt",
            ],
        ],

        # torrentGroups
        "torrentGroups" => [
            "simpleSearch" => ["torrentGroups_id", "torrentGroups_categoryId", "torrentGroups_revisionId", "torrentGroups_identifier", "torrentGroups_title", "torrentGroups_slug", "torrentGroups_subject", "torrentGroups_object", "torrentGroups_workgroup", "torrentGroups_location", "torrentGroups_year", "torrentGroups_description", "torrentGroups_picture", "torrentGroups_tags", "torrentGroups_createdAt", "torrentGroups_updatedAt", "torrentGroups_deletedAt"],
            "complexSearch" => ["torrentGroups_identifier", "torrentGroups_title", "torrentGroups_slug", "torrentGroups_subject", "torrentGroups_object", "torrentGroups_description"],
            "orderBy" => [
                "random" => "rand()",
                "createdAt" => "id",
                "alphabetical" => "torrentGroups_title",
                "seederCount" => "torrents_seederCount",
                "leecherCount" => "torrents_leecherCount",
                "snatchCount" => "torrents_snatchCount",
                "dataSize" => "torrents_dataSize",
            ],
        ],

        # shared
        "shared" => [
            "creators" => ["creators_openAlexId", "creators_orcid", "creators_scopusId", "creators_semanticScholarId", "creators_name", "creators_slug", "creators_aliases"],
            "literature" => ["literature_doi", "literature_openAlexId", "literature_semanticScholarId", "literature_title", "literature_slug", "literature_bibtex", "literature_abstract"],

            "workgroups" => ["torrentGroups_workgroup", "creators_affiliations", "creators_affiliationsOverTime", "organizations_grid", "organizations_openAlexId", "organizations_rorId", "organizations_wikidataId", "organizations_name", "organizations_slug", "organizations_acronym", "organizations_reverseGeocode"],
            "locations" => ["torrentGroups_location", "organizations_latitude", "organizations_longitude", "organizations_reverseGeocode", "organizations_country", "organizations_state", "organizations_city", "organizations_postalCode"],

            "descriptions" => ["torrentGroups_description", "collages_description", "creators_description", "literature_abstract", "requests_description", "torrents_description"],
            "files" => ["torrents_infoHash", "torrents_filePath", "torrents_fileList"],
            "leechStatus" => ["torrents_upMultiplier", "torrents_downMultiplier", "torrents_freeleechStatus", "torrents_freeleechType"],

            "numbers" => ["torrentGroups_identifier", "torrents_version"],
            "dates" => ["torrentGroups_year", "torrentGroups_createdAt", "torrentGroups_updatedAt", "torrentGroups_deletedAt", "literature_year", "literature_publicationDate", "organizations_established", "requests_filledAt", "requests_lastVote", "torrents_lastAction"],

            "scopes" => ["torrents_scope"],
            "licenses" => ["torrents_license"],
            "isAnnotated" => ["torrents_isAnnotated"],

            "minimumSize" => ["torrents_dataSize"],
            "maximumSize" => ["torrents_dataSize"],
            "dataUnit" => ["torrents_dataSize"],

            "platforms" => ["torrents_platform"],
            "formats" => ["torrents_format"],
            "archives" => ["torrents_archive"],

            "categories" => ["torrentGroups_categoryId", "collages_categoryId", "requests_categoryId"],
            "tags" => ["torrentGroups_tags", "collages_tags", "tags_id", "tags_name"],
            "tagsType" => null,

            "torrentGroups" => ["torrentGroups_identifier", "torrentGroups_title", "torrentGroups_slug", "torrentGroups_subject", "torrentGroups_object"],
            "collages" => ["collages_title", "collages_slug", "collages_description"],
            "requests" => ["requests_identifier", "requests_title", "requests_slug", "requests_subject", "requests_object"],

            "orderWay" => null,
            "page" => null,
        ],
    ];


    /** */


    /**
     * __construct
     */
    public function __construct(string $context = "torrentGroups")
    {
        $app = App::go();

        try {
            # sanity checks
            $this->context = $context ?? null;
            $allowedContexts = array_keys($this->indexMaps);
            if (!in_array($this->context, $allowedContexts)) {
                throw new Exception("expected one of " . implode(", ", $allowedContexts) . ", got {$this->context}");
            }

            $this->fieldMaps[$this->context] ??= null;
            if (!$this->fieldMaps[$this->context]) {
                throw new Exception("no field map found for {$this->context}");
            }

            # set a convenience property for the fields
            $this->fields = array_merge($this->fieldMaps[$this->context], $this->fieldMaps["shared"]);

            # initialize empty search terms
            $this->terms = $this->processRequest([]);

            /** */

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
        } catch (\Throwable $e) {
            throw $e;
        }
    }


    /**
     * setContext
     *
     * @param string $context
     * @return $this
     */
    public function setContext(string $context): self
    {
        /*
        if (!in_array($context, array_keys($this->indexMaps))) {
            throw new Exception("expected one of " . implode(", ", array_keys($this->indexMaps)) . ", got {$context}");
        }
        */

        $this->context = $context;
        return $this;
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
        $cacheKey = $this->cachePrefix . "{$this->context}:" . hash($app->env->cacheAlgorithm, json_encode($data));
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # validate the search terms
        $this->terms = $this->processRequest($data);

        # start the query
        $this->query = $this->queryLanguage
            ->select("id")
            #->select("*") # debug
            ->from($this->indexMaps[$this->context]);

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
        $data["orderBy"] ??= "id";
        $data["orderWay"] ??= "desc";

        # random order fix
        if ($data["orderBy"] === "random") {
            $data["orderWay"] = null;
        }

        $this->fields["orderBy"][ $data["orderBy"] ] ??= null;
        if ($this->fields["orderBy"][ $data["orderBy"] ]) {
            $this->query->orderBy($this->fields["orderBy"][ $data["orderBy"] ], $data["orderWay"]);
        } else {
            $this->query->orderBy($data["orderBy"], $data["orderWay"]);
        }

        /** */

        # does the heavy lifting of adding clauses
        # THIS IS THE ESSENTIAL QUERY FILTER FUNCTION
        $this->query = $this->processSearchTerms($data);
        $this->query->groupBy("id");

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
            $key ??= null;
            $value ??= null;

            if (!$key || !$value) {
                continue;
            }

            $this->query = $this->processSearchTerm($key, $value);
        }

        return $this->query;
    }


    /**
     * processSearchTerm
     *
     * Look at a search term and see what to do with it.
     *
     * @param string $key name of the search field
     * @param mixed $value search expression for the field
     * @return $this->query
     */
    private function processSearchTerm(string $key, mixed $value): \Foolz\SphinxQL\SphinxQL
    {
        return match ($key) {
            "leechStatus" => match (intval($value)) {
                1 => $this->query->where($this->fields["leechStatus"], 1),
                2 => $this->query->where($this->fields["leechStatus"], 2),
                3 => $this->query->where($this->fields["leechStatus"], "in", [1, 2]),
            },

            "dates" => match (count($range = explode("-", $value))) {
                1 => $this->query->where($this->fields["dates"], intval($range[0])),
                2 => match (empty($range[0])) {
                    true => $this->query->where($this->fields["dates"], "<=", intval($range[1])),
                    false => match (empty($range[1])) {
                        true => $this->query->where($this->fields["dates"], ">=", intval($range[0])),
                        false => $this->query->where($this->fields["dates"], "between", [intval($range[0]), intval($range[1])]),
                    },
                },
            },

            "scopes" => $this->query->where($this->fields["scopes"], "in", $value),
            "licenses" => $this->query->where($this->fields["licenses"], "in", $value),
            "isAnnotated" => $this->query->where($this->fields["isAnnotated"], intval($value)),

            "dataUnit" => $this->query->where($this->fields["dataUnit"], "between", [
                intval(($this->terms["minimumSize"] ?? 0) * (1024 ** $value)),
                intval(min(PHP_INT_MAX, ($this->terms["maximumSize"] ?? INF) * (1024 ** $value))),
            ]),

            "platforms" => $this->query->where($this->fields["platforms"], "in", $value),
            "formats" => $this->query->where($this->fields["formats"], "in", $value),
            "archives" => $this->query->where($this->fields["archives"], "in", $value),

            "categories" => $this->query->where($this->fields["categories"], "in", array_keys($value)),
            "tags" => match ($this->terms["tagsType"]) {
                "includeTags" => $this->query->match($this->fields["tags"], implode(" ", $value)),
                "excludeTags" => $this->query->match($this->fields["tags"], \Foolz\SphinxQL\SphinxQL::expr(implode(" or ", array_map(fn ($v) => Text::esc("-{$v}"), $value)))),
            },

            # don't match these
            "orderBy" => $this->query,
            "orderWay" => $this->query,

            default => $this->query->match($this->fields[$key], $value),
        };
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
    public function autocomplete(string $query)
    {
        $app = App::go();

        /*
        # return cached if available
        $cacheKey = $this->cachePrefix . "{$this->context}:" . hash($app->env->cacheAlgorithm, json_encode($data));
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }
        */

        # set the properties to match
        $properties = match ($this->context) {
            "collages" => ["collages_title", "collages_deletedAt"],
            "creators" => ["creators_name", "creators_openAlexId"],
            "literature" => ["literature_title", "literature_openAlexId"],
            "organizations" => ["organizations_name", "organizations_openAlexId"],
            "publications" => ["publications_title", "publications_openAlexId"],
            "requests" => ["requests_title", "requests_deletedAt"],
            "torrentGroups" => ["torrentGroups_title", "torrentGroups_deletedAt"],
            default => throw new Exception("bad context"),
        };

        # start the query
        $fields = array_merge(["id"], $properties);
        $this->query = $this->queryLanguage
            ->select($fields)
            ->from($this->indexMaps[$this->context])
            ->match($properties, $query);

        # execute the statement
        $resultSet = $this->query->execute();
        $results = $resultSet->fetchAllAssoc();

        $reindexedResults = [];
        foreach ($results as $result) {
            # one of the few times list() actually makes sense
            list($id, $text, $openAlexId) = array_values($result);

            $reindexedResults[] = [
                "id" => $id ?? null,
                "text" => $text ?? null,
                "openAlexId" => $openAlexId ?? null,
                "isLocal" => true,
            ];
        }

        #$app->cache->set($cacheKey, $reindexedResults, $this->cacheDuration);
        return $reindexedResults;
    }


    /**
     * processRequest
     *
     * Validates a request and returns an array of search terms.
     *
     * @param array $data, e.g., Gazelle\Http::get()
     * @return array
     */
    public function processRequest(array $data): array
    {
        $keys  = array_keys($this->fields);

        foreach ($keys as $key) {
            $data[$key] ??= null;
        }

        # remove $data keys not in $this->fields
        $data = array_intersect_key($data, $this->fields);

        return $data;
    }


    /**
     * paginate
     *
     * @param array $data array of items
     * @return array pagination data
     */
    public function paginate(array $data): array
    {
        $app = App::go();

        $pagination = [];

        # resultCount and pageSize
        $pagination["resultCount"] = count($data);
        $pagination["pageSize"] = $app->user->extra["siteOptions"]["searchPagination"] ?? 20;

        # current page
        $pagination["currentPage"] = intval($this->terms["page"] ?? 1);
        if (empty($pagination["currentPage"]) || $pagination["currentPage"] !== abs($pagination["currentPage"])) {
            $pagination["currentPage"] = 1;
        }

        # last page
        $pagination["lastPage"] = ceil($pagination["resultCount"] / $pagination["pageSize"]);
        if ($pagination["currentPage"] > $pagination["lastPage"]) {
            $pagination["currentPage"] = $pagination["lastPage"];
        }

        # previous page
        $pagination["previousPage"] = $pagination["currentPage"] - 1;
        if (empty($pagination["previousPage"]) || abs($pagination["previousPage"]) !== $pagination["previousPage"]) {
            $pagination["previousPage"] = 1;
        }

        # next page
        $pagination["nextPage"] = $pagination["currentPage"] + 1;

        # first page
        $pagination["firstPage"] = 1;

        # offset and limit
        $pagination["offset"] = intval(($pagination["currentPage"] - 1) * $pagination["pageSize"]);
        $pagination["limit"] = $pagination["offset"] + $pagination["pageSize"];

        if ($pagination["limit"] > $pagination["resultCount"]) {
            $pagination["limit"] = $pagination["resultCount"];
        }

        return $pagination;
    }
} # class

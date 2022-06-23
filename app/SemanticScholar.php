<?php
declare(strict_types = 1);


/**
 * SemanticScholar
 *
 * Helper class for the Academic Graph API and others.
 *
 * @see https://api.semanticscholar.org/api-docs/graph
 * @see https://api.semanticscholar.org/api-docs/recommendations
 * @see https://api.semanticscholar.org/api-docs/datasets
 */

class SemanticScholar
{
    # base uri's for api endpoints
    private $academicGraphUri = "https://api.semanticscholar.org/graph/v1";
    private $recommendationsUri = "https://api.semanticscholar.org/recommendations/v1";
    private $datasetsUri = "https://api.semanticscholar.org/datasets/v1";

    # fallback api result limit
    # 50 = half of SS's default
    private $limit = 50;

    # construct params
    private $params = [
        "paperId" => null,
        "authorId" => null,
        "releaseId" => null,
    ];

    # cache settings
    private $cachePrefix = "semanticScholar_";
    private $cacheDuration = 86400; # one day

    # hash algo for cache keys
    private $algorithm = "sha3-512";


    /**
     * __construct
     *
     * e.g.,
     * $semanticScholar = new SemanticScholar([
     *   "paperId" => null,
     *   "authorId" => null,
     *   "releaseId" => null,
     * ]);
     */
    public function __construct(array $params)
    {
        $allowedKeys = array_keys($this->params);

        # not necessarily trusted input
        # e.g., accidentally unescaped form
        foreach ($params as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $this->params[$key] = Text::esc($value);
            }
        }

        return $this;
    }


    /**
     * curl
     */
    private function curl(string $uri, array $fields, string $search = "")
    {
        $app = App::go();

        # return cached if available
        $cacheKey = $this->cachePrefix . hash($this->algorithm, json_encode([
            "uri" => $uri, "fields" => $fields, "search" => $search
        ]));

        if ($app->cacheOld->get_value($cacheKey)) {
            #return $app->cacheOld->get_value($cacheKey);
        }

        # fields
        $query = "?fields=";
        foreach ($fields as $key => $field) {
            if (!is_array($field)) {
                $query .= "{$field},";
            } else {
                foreach ($field as $value) {
                    $query .= "{$key}.{$value},";
                    #!d($key, $value);
                }
            }
        }

        # trailing comma is significant
        $query = rtrim($query, ",");

        # free-text paper/author search
        if (!empty($search)) {
            $query .= "&query={$search}";
        }
        $query .= "&limit={$this->limit}";


        # okay
        $uri = "{$uri}/{$query}";

        # https://www.php.net/manual/en/curl.examples-basic.php
        $ch = curl_init("{$uri}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * scrape
     *
     * Get everything related to a basic object.
     * Mostly used for packaging into MySQL format:
     *
     * CREATE TABLE `semanticScholar` (
     *   `id` VARCHAR(255) NOT NULL,
     *   `torrentId` INT,
     *   `artistId` INT,
     *   `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
     *   `updated` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     *   `object` VARCHAR(25) NOT NULL,
     *   `json` TEXT,
     *   KEY `id` (`id`,`torrentId`) USING BTREE,
     *   PRIMARY KEY (`id`,`torrentId`,`object`)
     * ) ENGINE=InnoDB;
     */
    public function scrape()
    {
        $endpoints = [];

        foreach ($this->params as $key => $value) {
            if (!$value) {
                continue;
            }

            switch ($key) {
                case "paperId":
                    array_push(
                        $endpoints,
                        "paperDetails",
                        #"paperAuthors",
                        #"paperCitations",
                        #"paperReferences",
                        "singlePositiveRecommends"
                    );
                    break;

                case "authorId":
                    array_push(
                        $endpoints,
                        "authorDetails",
                        #"authorPapers"
                    );
                    break;
                
                case "releaseId":
                    array_push(
                        $endpoints,
                        "listReleaseDatasets"
                    );
                    break;
            }
        }

        $data ??= [];
        foreach ($endpoints as $endpoint) {
            try {
                $data[$endpoint] ??= [];
                $data[$endpoint] = call_user_func([$this, $endpoint]);
            } catch (Exception $e) {
                return $e->getMesage();
            }
        }

        return $data;
    }


    /** ACADEMIC GRAPH API */


    /**
     * paperSearch
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper_search
     */
    public function paperSearch(string $query)
    {
        $fields = [
            "externalIds",
            "url",
            "title",
            "abstract",
            "venue",
            "year",
            "referenceCount",
            "citationCount",
            "influentialCitationCount",
            "isOpenAccess",
            "fieldsOfStudy",
            "s2FieldsOfStudy",
            "publicationTypes",
            "publicationDate",
            "journal",
            "authors",
        ];
       
        $uri = "{$this->academicGraphUri}/paper/search";
        $response = $this->curl($uri, $fields, $query);

        return $response;
    }


    /**
     * paperDetails
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper
     */
    public function paperDetails()
    {
        $fields = [
            "externalIds",
            "url",
            "title",
            "abstract",
            "venue",
            "year",
            "referenceCount",
            "citationCount",
            "influentialCitationCount",
            "isOpenAccess",
            "fieldsOfStudy",
            "s2FieldsOfStudy",
            "publicationTypes",
            "publicationDate",
            "journal",

            "authors" => [
                "externalIds",
                "url",
                "name",
                "aliases",
                "affiliations",
                "homepage",
                "paperCount",
                "citationCount",
                "hIndex",


            ],

            "citations" => [
                "corpusId",
                "externalIds",
                "url",
                "title",
                "abstract",
                "venue",
                "year",
                "referenceCount",
                "citationCount",
                "influentialCitationCount",
                "isOpenAccess",
                "fieldsOfStudy",
                "s2FieldsOfStudy",
                "publicationTypes",
                "publicationDate",
                "journal",
                "authors",
            ],

            "references" => [
                "externalIds",
                "url",
                "title",
                "abstract",
                "venue",
                "year",
                "referenceCount",
                "citationCount",
                "influentialCitationCount",
                "isOpenAccess",
                "fieldsOfStudy",
                "s2FieldsOfStudy",
                "authors",
                "publicationTypes",
                "publicationDate",
                "journal",


            ],
            
            #"embedding",
            "tldr",
        ];

        $uri = "{$this->academicGraphUri}/paper/{$this->params["paperId"]}";
        $response = $this->curl($uri, $fields);

        return $response;
    }


    /**
     * paperAuthors
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper_authors
     */
    public function paperAuthors()
    {
        $fields = [
            "externalIds",
            "url",
            "name",
            "aliases",
            "affiliations",
            "homepage",
            "paperCount",
            "citationCount",
            "hIndex",

            "papers" => [
                "externalIds",
                "url",
                "title",
                "abstract",
                "venue",
                "year",
                "referenceCount",
                "citationCount",
                "influentialCitationCount",
                "isOpenAccess",
                "fieldsOfStudy",
                "s2FieldsOfStudy",
                "publicationTypes",
                "publicationDate",
                "journal",
                "authors",
            ],
        ];

        $uri = "{$this->academicGraphUri}/paper/{$this->params["paperId"]}/authors";
        $response = $this->curl($uri, $fields);

        return $response;
    }


    /**
     * paperCitations
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper_citations
     */
    public function paperCitations()
    {
        $fields = [
            "contexts",
            "intents",
            "isInfluential",
            "corpusId",
            "externalIds",
            "url",
            "title",
            "abstract",
            "venue",
            "year",
            "referenceCount",
            "citationCount",
            "influentialCitationCount",
            "isOpenAccess",
            "fieldsOfStudy",
            "s2FieldsOfStudy",
            "publicationTypes",
            "publicationDate",
            "journal",
            "authors",
        ];

        $uri = "{$this->academicGraphUri}/paper/{$this->params["paperId"]}/citations";
        $response = $this->curl($uri, $fields);

        return $response;
    }


    /**
     * paperReferences
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper_references
     */
    public function paperReferences()
    {
        $fields = [
            "contexts",
            "intents",
            "isInfluential",
            "corpusId",
            "externalIds",
            "url",
            "title",
            "abstract",
            "venue",
            "year",
            "referenceCount",
            "citationCount",
            "influentialCitationCount",
            "isOpenAccess",
            "fieldsOfStudy",
            "s2FieldsOfStudy",
            "publicationTypes",
            "publicationDate",
            "journal",
            "authors",
        ];

        $uri = "{$this->academicGraphUri}/paper/{$this->params["paperId"]}/references";
        $response = $this->curl($uri, $fields);

        return $response;
    }


    /**
     * authorSearch
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_author_search
     */
    public function authorSearch(string $query)
    {
        $fields = [
            "externalIds",
            "url",
            "name",
            "aliases",
            "affiliations",
            "homepage",
            "paperCount",
            "citationCount",
            "hIndex",

            "papers" => [
                "externalIds",
                "url",
                "title",
                "abstract",
                "venue",
                "year",
                "referenceCount",
                "citationCount",
                "influentialCitationCount",
                "isOpenAccess",
                "fieldsOfStudy",
                "s2FieldsOfStudy",
                "publicationTypes",
                "publicationDate",
                "journal",
                "authors",
            ],
        ];

        $uri = "{$this->academicGraphUri}/author/search";
        $response = $this->curl($uri, $fields, $query);

        return $response;
    }


    /**
     * authorDetails
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_author
     */
    public function authorDetails()
    {
        $fields = [
            "externalIds",
            "url",
            "name",
            "aliases",
            "affiliations",
            "homepage",
            "paperCount",
            "citationCount",
            "hIndex",

            "papers" => [
                "externalIds",
                "url",
                "title",
                "abstract",
                "venue",
                "year",
                "referenceCount",
                "citationCount",
                "influentialCitationCount",
                "isOpenAccess",
                "fieldsOfStudy",
                "s2FieldsOfStudy",
                "publicationTypes",
                "publicationDate",
                "journal",
                "authors",
            ],
        ];

        $uri = "{$this->academicGraphUri}/author/{$this->params["authorId"]}";
        $response = $this->curl($uri, $fields);

        return $response;
    }


    /**
     * authorPapers
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_author_papers
     */
    public function authorPapers()
    {
        $fields = [

            "externalIds",
            "url",
            "title",
            "abstract",
            "venue",
            "year",
            "referenceCount",
            "citationCount",
            "influentialCitationCount",
            "isOpenAccess",
            "fieldsOfStudy",
            "s2FieldsOfStudy",
            "publicationTypes",
            "publicationDate",
            "journal",
            "authors",

            "citations" => [
                "corpusId",
                "url",
                "title",
                "venue",
                "year",
                "authors",
            ],

            "references" => [
                "corpusId",
                "url",
                "title",
                "venue",
                "year",
                "authors",
            ],
        ];

        $uri = "{$this->academicGraphUri}/author/{$this->params["authorId"]}/papers";
        $response = $this->curl($uri, $fields);

        return $response;
    }


    /** RECOMMENDATIONS API */


    /**
     * singlePositiveRecommends
     *
     * @see https://api.semanticscholar.org/api-docs/recommendations#operation/get_papers_for_paper
     */
    public function singlePositiveRecommends()
    {
        $fields = [
            "externalIds",
            "url",
            "title",
            "abstract",
            "venue",
            "year",
            "referenceCount",
            "citationCount",
            "influentialCitationCount",
            "isOpenAccess",
            "fieldsOfStudy",
            "s2FieldsOfStudy",
            "authors",
        ];

        $uri = "{$this->recommendationsUri}/papers/forpaper/{$this->params["paperId"]}";
        $response = $this->curl($uri, $fields);

        return $response;
    }


    /** DATASETS API */


    /**
     * listReleases
     *
     * @see https://api.semanticscholar.org/api-docs/datasets#operation/get_releases
     */
    public function listReleases()
    {
        $uri = "{$this->datasetsUri}/release";
        $response = $this->curl($uri);

        return $response;
    }


    /**
     * listReleaseDatasets
     *
     * @see https://api.semanticscholar.org/api-docs/datasets#operation/get_release
     */
    public function listReleaseDatasets()
    {
        $uri = "{$this->datasetsUri}/release/{$this->params["releaseId"]}";
        $response = $this->curl($uri);

        return $response;
    }


    /**
     * datasetDownloadLinks
     */
    public function datasetDownloadLinks(string $datasetName)
    {
        $uri = "{$this->datasetsUri}/release/{$this->params["releaseId"]}/dataset/$datasetName}";
        $response = $this->curl($uri);

        return $response;
    }
} # class

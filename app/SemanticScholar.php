<?php
declare(strict_types = 1);


/**
 * SemanticScholar
 *
 * Helper class for the Academic Graph API and others.
 * The scrape function is pretty much what you want.
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

    # api result limit
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
                # not recursive
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

        # okay
        $query .= "&limit={$this->limit}";
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
     * Get everything related to the available objects.
     * Mostly used for packaging into MySQL format.
     *
     * @param bool $save true to write json to db
     */
    public function scrape(bool $save = false)
    {
        # determined by $this
        $endpoints = [];

        foreach ($this->params as $key => $value) {
            if (!$value) {
                continue;
            }

            # fn names
            switch ($key) {
                case "paperId":
                    $endpoints[$value] = array_merge($endpoints, ["paper", "recommendations"]);
                    break;

                case "authorId":
                    $endpoints[$value] = array_merge($endpoints, ["author"]);
                    break;
                
                case "releaseId":
                    $endpoints[$value] = array_merge($endpoints, ["datasets"]);
                    break;
            }
        }

        # populate data
        $data ??= [];
        foreach ($endpoints as $id => $info) {
            foreach ($info as $endpoint) {
                try {
                    $data[$id][$endpoint] ??= [];
                    $data[$id][$endpoint] = call_user_func([$this, $endpoint]);
                } catch (Exception $e) {
                    return $e->getMesage();
                }
            }
        }

        # try to get dataset download links
        $datasets = array_column($data, "datasets");
        if (!empty($this->releaseId) && !empty($datasets)) {
            foreach ($datasets as $dataset) {
                try {
                    $data[$this->releaseId]["downloadLinks"] ??= [];
                    $data[$this->releaseId]["downloadLinks"][$dataset["name"]]
                        = $this->downloadLinks($dataset["name"])
                        ?? [];
                } catch (Exception $e) {
                    return $e->getMesage();
                }
            }
        }

        # upsert?
        if ($save) {
            try {
                $this->upsert($data);
            } catch (Exception $e) {
                return $e->getMesage();
            }
        }

        return $data;
    }


    /**
     * upsert
     *
     * CREATE TABLE `semanticScholar` (
     *   `id` VARCHAR(100) NOT NULL,
     *   `externalIds` VARCHAR(255) NOT NULL,
     *   `torrentGroupId` INT,
     *   `artistIds` INT,
     *   `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
     *   `updated` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     *   `json` TEXT,
     *   KEY `id` (`id`,`torrentId`) USING BTREE,
     *   PRIMARY KEY (`id`,`torrentId`)
     * ) ENGINE=InnoDB;

     */
    private function upsert($data)
    {
        $app = App::go();

        foreach ($data as $id => $data) {
            $json = json_encode($data);
            $query = "
                insert into semanticScholar
                (id, externalIds, torrentGroupId, artistIds, created, updated, json)
                values (:id, :externalIds, :torrentGroupId, :artistIds, :created, :updated, :json)
                on duplicate key update json = :json
            ";

            $vars = [
                "id" => $id,
                "externalIds" => null, # todo
                "torrentGroupId" => null, # todo
                "artistIds" => null, # todo
                "json" => $json,
            ];

            $app->dbNew->do($query, $vars);
        }
    }


    /** ACADEMIC GRAPH API */


    /**
     * search
     *
     * Search the Academic Graph API for papers and authors.
     *
     * @param string $query get results related to this
     * @param string $what one of ["papers", "authors"]
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper_search
     */
    public function search(string $query, string $what = "papers")
    {
        if (!in_array($what, ["papers", "authors"])) {
            throw new Exception("expected [\"papers\", \"authors\"], got {$what}");
        }

        # it doesn't ignore invalid
        $paperFields = [
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
       
        $authorFields = [
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

        # api calls
        if ($what === "papers") {
            $uri = "{$this->academicGraphUri}/paper/search";
            $response = $this->curl($uri, $paperFields, $query);

            return $response;
        }

        if ($what === "authors") {
            $uri = "{$this->academicGraphUri}/author/search";
            $response = $this->curl($uri, $authorFields, $query);

            return $response;
        }
    }


    /**
     * paper
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper
     */
    public function paper()
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
     * author
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_author
     */
    public function author()
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


    /** RECOMMENDATIONS API */


    /**
     * recommendations
     *
     * @see https://api.semanticscholar.org/api-docs/recommendations#operation/get_papers_for_paper
     */
    public function recommendations()
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
     * releases
     *
     * @see https://api.semanticscholar.org/api-docs/datasets#operation/get_releases
     */
    public function releases()
    {
        $uri = "{$this->datasetsUri}/release";
        $response = $this->curl($uri);

        return $response;
    }


    /**
     * datasets
     *
     * @see https://api.semanticscholar.org/api-docs/datasets#operation/get_release
     */
    public function datasets()
    {
        $uri = "{$this->datasetsUri}/release/{$this->params["releaseId"]}";
        $response = $this->curl($uri);

        return $response;
    }


    /**
     * downloadLinks
     */
    public function downloadLinks(string $datasetName)
    {
        $uri = "{$this->datasetsUri}/release/{$this->params["releaseId"]}/dataset/{$datasetName}";
        $response = $this->curl($uri);

        return $response;
    }
} # class

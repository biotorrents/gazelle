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

    # default paper fields from "details" endpoints
    private $paperFields = [
        "paperId", # Always included
        "externalIds",
        "url",
        "title", # Included if no fields are specified
        "abstract",
        "venue",
        "year",
        "referenceCount",
        "citationCount",
        "influentialCitationCount",
        "isOpenAccess",
        "fieldsOfStudy",
        "s2FieldsOfStudy",
        "publicationTypes", # Journal Article, Conference, Review, etc.
        "publicationDate", # YYYY-MM-DD, if available
        "journal", # Journal name, volume, and pages, if available

        "authors" => [ # Up to 500 will be returned
            "authorId", # Always included
            "externalIds",
            "url",
            "name", # Included if no fields are specified
            "aliases",
            "affiliations",
            "homepage",
            "paperCount",
            "citationCount",
            "hIndex",
        ],

        "citations" => [ # Up to 1000 will be returned
            "paperId", # Always included
            "corpusId",
            "externalIds",
            "url",
            "title", # Included if no fields are specified
            "abstract",
            "venue",
            "year",
            "referenceCount",
            "citationCount",
            "influentialCitationCount",
            "isOpenAccess",
            "fieldsOfStudy",
            "s2FieldsOfStudy",
            "publicationTypes", # Journal Article, Conference, Review, etc.
            "publicationDate", # YYYY-MM-DD, if available
            "journal", # Journal name, volume, and pages, if available
            "authors", # Will include: authorId & name
        ],

        "references" => [ # Up to 1000 will be returned
            "paperId", # Always included
            "externalIds",
            "url",
            "title", # Included if no fields are specified
            "abstract",
            "venue",
            "year",
            "referenceCount",
            "citationCount",
            "influentialCitationCount",
            "isOpenAccess",
            "fieldsOfStudy",
            "s2FieldsOfStudy",
            "authors", # Will include: authorId & name
            "publicationTypes", # Journal Article, Conference, Review, etc.
            "publicationDate", # YYYY-MM-DD, if available
            "journal", # Journal name, volume, and pages, if available
        ],

        "embedding", # Vector embedding of paper content from the SPECTER model
        "tldr", # Auto-generated short summary of the paper from the SciTLDR model
    ];

    # default author fields from "details" endpoints
    private $authorFields = [
        "authorId", # Always included
        "externalIds",
        "url",
        "name", # Included if no fields are specified
        "aliases",
        "affiliations",
        "homepage",
        "paperCount",
        "citationCount",
        "hIndex",

        "papers" => [
            "paperId", # Always included
            "externalIds",
            "url",
            "title", # Included if no fields are specified
            "abstract",
            "venue",
            "year",
            "referenceCount",
            "citationCount",
            "influentialCitationCount",
            "isOpenAccess",
            "fieldsOfStudy",
            "s2FieldsOfStudy",
            "publicationTypes", # Journal Article, Conference, Review, etc.
            "publicationDate", # YYYY-MM-DD, if available
            "journal", # Journal name, volume, and pages, if available

            "authors" => [ # Up to 500 will be returned
                "authorId", # Always included
                "name", # Always included
            ],
        ],
    ];



    /**
     * curl
     */
    public function curl(string $uri, array $fields)
    {
    }


    /** ACADEMIC GRAPH API */


    /**
     * paperSearch
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper_search
     */
    public function paperSearch(string $query)
    {
        $uri = "{$this->academicGraphUri}/paper/search";
    }


    /**
     * paperDetails
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper
     */
    public function paperDetails(string $paperId)
    {
        $uri = "{$this->academicGraphUri}/paper/{$paperId}";
    }


    /**
     * paperAuthors
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper_authors
     */
    public function paperAuthors(string $paperId)
    {
        $uri = "{$this->academicGraphUri}/paper/{$paperId}/authors";
    }


    /**
     * paperCitations
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper_citations
     */
    public function paperCitations(string $paperId)
    {
        $uri = "{$this->academicGraphUri}/paper/{$paperId}/citations";
    }


    /**
     * paperReferences
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_paper_references
     */
    public function paperReferences(string $paperId)
    {
        $uri = "{$this->academicGraphUri}/paper/{$paperId}/references";
    }


    /**
     * authorSearch
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_author_search
     */
    public function authorSearch(string $query)
    {
        $uri = "{$this->academicGraphUri}/author/search";
    }


    /**
     * authorDetails
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_author
     */
    public function authorDetails(string $authorId)
    {
        $uri = "{$this->academicGraphUri}/author/{$authorId}";
    }


    /**
     * authorPapers
     *
     * @see https://api.semanticscholar.org/api-docs/graph#operation/get_graph_get_author_papers
     */
    public function authorPapers(string $authorId)
    {
        $uri = "{$this->academicGraphUri}/author/{$authorId}/papers";
    }


    /** RECOMMENDATIONS API */


    /**
     * posititiveNegativeRecommends
     *
     * @see https://api.semanticscholar.org/api-docs/recommendations#operation/post_papers
     */
    public function posititiveNegativeRecommends(array $positivePaperIds, array $negativePaperIds)
    {
        $uri = "{$this->recommendationsUri}/papers";
    }


    /**
     * singlePositiveRecommends
     *
     * @see https://api.semanticscholar.org/api-docs/recommendations#operation/get_papers_for_paper
     */
    public function singlePositiveRecommends(string $paperId)
    {
        $uri = "{$this->recommendationsUri}/papers/forpaper/{$paperId}";
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
    }


    /**
     * listReleaseDatasets
     *
     * @see https://api.semanticscholar.org/api-docs/datasets#operation/get_release
     */
    public function listReleaseDatasets(string $releaseId)
    {
        $uri = "{$this->datasetsUri}/release/{$releaseId}";
    }


    /**
     * datasetDownloadLinks
     */
    public function datasetDownloadLinks(string $datasetName, string $releaseId)
    {
        $uri = "{$this->datasetsUri}/release/{$releaseId}/dataset/$datasetName}";
    }
} # class

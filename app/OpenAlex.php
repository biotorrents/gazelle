<?php

declare(strict_types=1);


/**
 * Gazelle\OpenAlex
 *
 * @see https://docs.openalex.org
 */

namespace Gazelle;

class OpenAlex
{
    # guzzle client
    private \GuzzleHttp\Client $client;
    private string $baseUri = "https://api.openalex.org";

    # cache settings
    private string $cachePrefix = "openAlex:";
    private string $cacheDuration = "1 hour";
    private string $cacheAlgorithm = "sha3-512";


    /**
     * __construct
     */
    public function __construct()
    {
        # https://docs.guzzlephp.org/en/stable/quickstart.html
        $this->client = new \GuzzleHttp\Client([
            "base_uri" => $this->baseUri,
            "timeout" => 2.0,
        ]);
    }


    /**
     * request
     *
     * @param string $item, e.g., "works"
     * @param string $id
     * @return array
     */
    private function request(string $item, string $id): array
    {
        $app = App::go();

        $cacheKey = hash($this->cacheAlgorithm, $this->cachePrefix . __FUNCTION__ . json_encode(func_get_args()));
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $prefix = match ($item) {
            "works" => "doi",
            "authors" => "orcid",
            "sources" => "issn",
            "institutions" => "ror",
            "publishers" => "ror",
            "funders" => "ror",
            "concepts" => "wikidata",
            default => throw new Exception("not implemented"),
        };

        # https://api.openalex.org/works/doi:10.7717/peerj.4375
        $response = $this->client->get("{$item}/{$prefix}:{$id}", ["query" => ["mailto" => $app->env->openAlexEmail]]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new Exception("http status code {$statusCode}");
        }

        # decode the api output to an array
        $body = json_decode($response->getBody()->getContents() ?? "[]", true);

        $app->cache->set($cacheKey, $body, $this->cacheDuration);
        return $body;
    }


    /**
     * works
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/works/get-a-single-work
     */
    public function works(int|string $id): array
    {
        return $this->request("works", $id);
    }


    /**
     * authors
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/authors/get-a-single-author
     */
    public function authors(int|string $id): array
    {
        return $this->request("authors", $id);
    }


    /**
     * sources
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/sources/get-a-single-source
     */
    public function sources(int|string $id): array
    {
        return $this->request("sources", $id);
    }


    /**
     * institutions
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/institutions/get-a-single-institution
     */
    public function institutions(int|string $id): array
    {
        return $this->request("institutions", $id);
    }


    /**
     * topics
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/topics/get-a-single-topic
     */
    public function topics(int|string $id): array
    {
        throw new Exception("not implemented");
    }


    /**
     * keywords
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/keywords#get-a-single-keyword
     */
    public function keywords(int|string $id): array
    {
        throw new Exception("not implemented");
    }


    /**
     * publishers
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/publishers/get-a-single-publisher
     */
    public function publishers(int|string $id): array
    {
        return $this->request("publishers", $id);
    }


    /**
     * funders
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/funders/get-a-single-funder
     */
    public function funders(int|string $id): array
    {
        return $this->request("funders", $id);
    }


    /**
     * geo
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/geo
     */
    public function geo(int|string $id): array
    {
        throw new Exception("not implemented");
    }


    /**
     * concepts
     *
     * @param int|string $id
     * @return array
     *
     * @see https://docs.openalex.org/api-entities/concepts/get-a-single-concept
     */
    public function concepts(int|string $id): array
    {
        return $this->request("concepts", $id);
    }
} # class

<?php

declare(strict_types=1);


/**
 * Gazelle\Crossref
 *
 * @see https://api.crossref.org/swagger-ui/index.html
 */

namespace Gazelle;

class Crossref
{
    # guzzle client
    private \GuzzleHttp\Client $client;
    private string $baseUri = "https://api.crossref.org";

    # cache settings
    private string $cachePrefix = "crossref:";
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
     * @param string $item, e.g., "journals"
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
            "journals" => "issn",
            "works" => "doi",
            default => throw new Exception("not implemented"),
        };

        # https://api.crossref.org/journals/0028-4793
        $response = $this->client->get("{$item}/{$id}", ["query" => ["mailto" => $app->env->crossrefEmail]]);

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
     * journals
     *
     * @param int|string $issn
     * @return array
     *
     * @see https://api.crossref.org/swagger-ui/index.html#/Journals/get_journals__issn_
     */
    public function journals($issn): array
    {
        return $this->request("journals", $issn);
    }


    /**
     * works
     *
     * @param int|string $doi
     * @return array
     *
     * @see https://api.crossref.org/swagger-ui/index.html#/Works/get_works__doi_
     */
    public function works($doi): array
    {
        return $this->request("works", $doi);
    }
} # class

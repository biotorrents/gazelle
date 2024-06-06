<?php

declare(strict_types=1);


/**
 * Gazelle\Ncbi
 *
 * @see https://www.ncbi.nlm.nih.gov/datasets/docs/v2/reference-docs/rest-api/
 */

namespace Gazelle;

class Ncbi
{
    # guzzle client
    private \GuzzleHttp\Client $client;
    private string $baseUri = "https://api.ncbi.nlm.nih.gov";

    # cache settings
    private string $cachePrefix = "ncbi:";
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
} # class

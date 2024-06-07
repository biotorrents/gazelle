<?php

declare(strict_types=1);


/**
 * Gazelle\Autocomplete
 */

namespace Gazelle;

class Autocomplete
{
    # guzzle client
    private \GuzzleHttp\Client $client;
    private string $baseUri = "";

    # cache settings
    private string $cachePrefix = "autocomplete:";
    private string $cacheDuration = "1 hour";
    private string $cacheAlgorithm = "sha3-512";


    /**
     * __construct
     */
    public function __construct()
    {
        # https://docs.guzzlephp.org/en/stable/quickstart.html
        $this->client = new \GuzzleHttp\Client([
            #"base_uri" => $this->baseUri,
            "timeout" => 2.0,
        ]);
    }
} # class

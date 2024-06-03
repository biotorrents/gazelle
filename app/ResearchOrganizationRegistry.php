<?php

declare(strict_types=1);


/**
 * Gazelle\ResearchOrganizationRegistry
 *
 * A minimal ROR API client that gets details, interactively searches, and matches text.
 *
 * @see https://ror.readme.io/v2/docs/rest-api
 */

namespace Gazelle;

class ResearchOrganizationRegistry
{
    # guzzle client
    private \GuzzleHttp\Client $client;
    private string $baseUri = "https://api.ror.org/v2";

    # cache settings
    private string $cachePrefix = "ror:";
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
     * details
     *
     * Get details for a specific organization.
     *
     * @param int|string $id
     * @return array
     *
     * @see https://ror.readme.io/v2/docs/api-single
     */
    public function details(int|string $id): array
    {
        $app = App::go();

        $cacheKey = hash($this->cacheAlgorithm, $this->cachePrefix . __FUNCTION__ . json_encode(func_get_args()));
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $response = $this->client->get("organizations/{$id}");
        $body = $this->validateAndParseResponse($response);

        $app->cache->set($cacheKey, $body, $this->cacheDuration);
        return $body;
    }


    /**
     * search
     *
     * Interactively search for organizations.
     *
     * @param string $query
     * @return array
     *
     * @see https://ror.readme.io/v2/docs/api-query
     */
    public function search(string $query): array
    {
        $app = App::go();

        $cacheKey = hash($this->cacheAlgorithm, $this->cachePrefix . __FUNCTION__ . json_encode(func_get_args()));
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $response = $this->client->get("organizations", [ "query" => ["query" => $query] ]);
        $body = $this->validateAndParseResponse($response);

        $body["items"] ??= [];
        $app->cache->set($cacheKey, $body["items"], $this->cacheDuration);
        return $body["items"];
    }


    /**
     * match
     *
     * Attempts to match an organization to a given string.
     *
     * @param string $query
     * @return array
     *
     * @see https://ror.readme.io/v2/docs/api-affiliation
     */
    public function match(string $query): array
    {
        $app = App::go();

        $cacheKey = hash($this->cacheAlgorithm, $this->cachePrefix . __FUNCTION__ . json_encode(func_get_args()));
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $response = $this->client->get("organizations", [ "query" => ["affiliation" => $query] ]);
        $body = $this->validateAndParseResponse($response);

        $body["items"] ??= [];
        foreach ($body["items"] as $item) {
            if ($item["chosen"]) {
                $app->cache->set($cacheKey, $item, $this->cacheDuration);
                return $item;
            }
        }

        return [];
    }


    /**
     * validateAndParseResponse
     *
     * Validate and parse the response from the API.
     *
     * @param $response
     * @return array
     */
    private function validateAndParseResponse($response): array
    {
        # is it all good?
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new Exception("http status code {$statusCode}");
        }

        return json_decode($response->getBody()->getContents() ?? "{}", true);
    }


    /**
     * reverseGeocode
     *
     * Reverse geocode a latitude and longitude to a formatted address.
     *
     * @param float $latitude
     * @param float $longitude
     * @return ?string
     *
     * @see https://developers.google.com/maps/documentation/geocoding/requests-reverse-geocoding
     */
    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        $app = App::go();

        $cacheKey = hash($this->cacheAlgorithm, $this->cachePrefix . __FUNCTION__ . json_encode(func_get_args()));
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        $response = $this->client->get("https://maps.googleapis.com/maps/api/geocode/json", [
            "query" => [
                "latlng" => "{$latitude},{$longitude}",
                "key" => $app->env->private("googleMapsApiKey"),
            ],
        ]);

        $body = $this->validateAndParseResponse($response);
        $previousLength = 0;
        $formattedAddress = null;

        foreach ($body["results"] as $result) {
            # it's shorter than the last address
            if (strlen($result["formatted_address"]) < $previousLength) {
                continue;
            }

            # set the return string to the current longest address
            $formattedAddress = $result["formatted_address"];
            $previousLength = strlen($formattedAddress);
        }

        $app->cache->set($cacheKey, $formattedAddress, $this->cacheDuration);
        return $formattedAddress;
    }
} # class

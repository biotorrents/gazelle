<?php

declare(strict_types=1);


/**
 * Gazelle\Autocomplete
 */

namespace Gazelle;

class Autocomplete
{
    # libraries
    private Manticore $manticore;
    private OpenAlex $openAlex;

    # cache settings
    private string $cachePrefix = "autocomplete:";
    private string $cacheDuration = "1 hour";
    private string $cacheAlgorithm = "sha3-512";


    /**
     * __construct
     */
    public function __construct()
    {
        $this->manticore = new Manticore();
        $this->openAlex = new OpenAlex();
    }


    /**
     * callManticore
     *
     * Base Manticore call to get local search results.
     *
     * @param string $context
     * @param string $query
     * @return array
     *
     * @see https://manticoresearch.com/blog/simple-autocomplete-with-manticore/
     */
    private function callManticore(string $context, string $query): array
    {
        $this->manticore->setContext($context);
        return $this->manticore->autocomplete($query);
    }


    /**
     * formatOpenAlexResponse
     *
     * @param array $response
     * @return array
     */
    private function formatOpenAlexResponse(array $response): array
    {
        $response["results"] ??= [];
        if (empty($response["results"])) {
            return [];
        }

        $data = [];
        foreach ($response["results"] as $item) {
            $data[] = [
                "id" => null,
                "openAlexId" => $item["id"],
                "text" => $item["display_name"],
                "isLocal" => false,
            ];
        }

        return array_slice($data, 0, 10);
    }


    /** */


    /**
     * fetch
     *
     * @param string $query
     * @param string $context
     * @param bool $fetchRemote
     * @return array
     */
    public function fetch(string $query, string $context, bool $fetchRemote = true): array
    {
        return $this->$context($query, $fetchRemote);
    }


    /**
     * collages
     *
     * @param string $query
     * @param bool $fetchRemote
     * @return array
     */
    public function collages(string $query, bool $fetchRemote = true): array
    {
        return $this->callManticore("collages", $query);
    }


    /**
     * creators
     *
     * @param string $query
     * @param bool $fetchRemote
     * @return array
     */
    public function creators(string $query, bool $fetchRemote = true): array
    {
        $localResults = $this->callManticore("creators", $query);
        if (!$fetchRemote) {
            return $localResults;
        }

        $remoteResults = $this->openAlex->search("authors", $query);
        $formattedResults = $this->formatOpenAlexResponse($remoteResults);

        return array_merge($localResults, $formattedResults);
    }


    /**
     * literature
     *
     * @param string $query
     * @param bool $fetchRemote
     * @return array
     */
    public function literature(string $query, bool $fetchRemote = true): array
    {
        $localResults = $this->callManticore("literature", $query);
        if (!$fetchRemote) {
            return $localResults;
        }

        $remoteResults = $this->openAlex->search("works", $query);
        $formattedResults = $this->formatOpenAlexResponse($remoteResults);

        return array_merge($localResults, $formattedResults);
    }


    /**
     * organizations
     *
     * @param string $query
     * @param bool $fetchRemote
     * @return array
     */
    public function organizations(string $query, bool $fetchRemote = true): array
    {
        $localResults = $this->callManticore("organizations", $query);
        if (!$fetchRemote) {
            return $localResults;
        }

        $remoteResults = $this->openAlex->search("institutions", $query);
        $formattedResults = $this->formatOpenAlexResponse($remoteResults);

        return array_merge($localResults, $formattedResults);
    }


    /**
     * requests
     *
     * @param string $query
     * @param bool $fetchRemote
     * @return array
     */
    public function requests(string $query, bool $fetchRemote = true): array
    {
        return $this->callManticore("requests", $query);
    }


    /**
     * torrentGroups
     *
     * @param string $query
     * @param bool $fetchRemote
     * @return array
     */
    public function torrentGroups(string $query, bool $fetchRemote = true): array
    {
        return $this->callManticore("torrentGroups", $query);
    }
} # class

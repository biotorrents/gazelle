<?php

declare(strict_types=1);


/**
 * Gazelle\OpenAI
 *
 * Client for the OpenAI API.
 * Example request for a common use case:
 *
 * curl -X POST https://api.openai.com/v1/completions \
 *   -H 'Authorization: Bearer {secretKey}' \
 *   -H 'OpenAI-Organization: {organizationId}' \
 *   -H 'Content-Type: application/vnd.api+json' \
 *   -d '{
 *     "model": "text-davinci-003",
 *     "prompt": "Summarize in 100 words: {torrent group description}",
 *     "max_tokens": 1000
 *   }'
 *
 * @see https://beta.openai.com/docs/introduction
 * @see https://github.com/openai-php/client
 *
 * ========================================
 *
 * Database table schema:
 *
CREATE TABLE `openai` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `jobId` VARCHAR(128) NOT NULL,
    `groupId` INT NOT NULL,
    `object` VARCHAR(32),
    `created` DATETIME DEFAULT NOW(),
    `updated` DATETIME DEFAULT NOW() ON UPDATE CURRENT_TIMESTAMP,
    `model` VARCHAR(32),
    `text` TEXT,
    `index` TINYINT,
    `logprobs` TINYINT,
    `finishReason` VARCHAR(16),
    `promptTokens` SMALLINT,
    `completionTokens` SMALLINT,
    `totalTokens` SMALLINT,
    `failCount` TINYINT DEFAULT 0,
    `json` JSON,
    `type` VARCHAR(16),
    PRIMARY KEY (`id`,`jobId`,`groupId`)
);
 */

namespace Gazelle;

class OpenAI
{
    # client and params
    public $client = null;
    private $maxTokens = 2500; # $0.05
    private $model = "text-davinci-003";

    # cache settings
    private $cachePrefix = "openai:";
    private $cacheDuration = "1 day";


    /**
     * __construct
     *
     * Load the OpenAI client.
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        $app = \Gazelle\App::go();

        if (!$app->env->enableOpenAi) {
            throw new \Exception("OpenAI support is disabled in the app config");
        }

        $openAiApi = $app->env->getPriv("openAiApi");
        $this->client = \OpenAI::client($openAiApi["secretKey"]);
    }


    /**
     * test
     *
     * Test the OpenAI API.
     *
     * @param string $prompt
     * @return OpenAI\Responses\Completions\CreateResponse
     */
    public function test(string $prompt = "hello"): OpenAI\Responses\Completions\CreateResponse
    {
        $response = $this->client->completions()->create([
            "model" => $this->model,
            "prompt" => $prompt,
            "max_tokens" => $this->maxTokens,
        ]);

        return $response;
    }


    /**
     * summarize
     *
     * Generate a summary of a torrent group description.
     * Store the summary in the database for later use.
     *
     * @see https://beta.openai.com/docs/api-reference/completions
     *
     * @param int $groupId
     * @return array
     */
    public function summarize(int $groupId): array
    {
        $app = \Gazelle\App::go();

        $app->debug["time"]->startMeasure("summarize", "openai: summarize groupId {$groupId}");

        # return cached if available
        $cacheKey = "{$this->cachePrefix}_summary_{$groupId}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # get the torrent group description
        $query = "select description from torrents_group where id = ?";
        $description = $app->dbNew->single($query, [$groupId]);

        if (!$description || empty($description)) {
            throw new \Exception("groupId {$groupId} not found or description empty");
        }

        # process the description
        $description = $this->processDescription($description);

        # query the openai api
        try {
            $response = $this->client->completions()->create([
                "model" => $this->model,
                "prompt" => "Summarize in 100 words: {$description}",
                "max_tokens" => $this->maxTokens,
            ]);
            !d($response);

            # cast to an array and save to the database
            $response = $response->toArray();
            $this->insertResponse($groupId, "summary", $response);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        $app->cache->set($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * keywords
     *
     * Generate a list of keywords from a summary (good) or a torrent group description (bad).
     * Store the keywords in the database for later use and add them to `torrents_tags`.
     *
     * @see https://beta.openai.com/docs/api-reference/completions
     *
     * @param int $groupId
     * @return array
     */
    public function keywords(int $groupId): array
    {
        $app = \Gazelle\App::go();

        $app->debug["time"]->startMeasure("keywords", "openai: keywords for groupId {$groupId}");

        # return cached if available
        $cacheKey = "{$this->cachePrefix}_keywords_{$groupId}";
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # try to get a tl;dr summary
        $query = "select text from openai where groupId = ? and type = ?";
        $description = $app->dbNew->single($query, [$groupId, "summary"]);

        # get a description if no summary exists
        if (!$description || empty($description)) {
            $query = "select description from torrents_group where id = ?";
            $description = $app->dbNew->single($query, [$groupId]);
        }

        if (!$description || empty($description)) {
            throw new \Exception("groupId {$groupId} not found or description empty");
        }

        # process the description
        $description = $this->processDescription($description);

        # query the openai api
        try {
            $response = $this->client->completions()->create([
                "model" => $this->model,
                "prompt" => "List 10 keywords in the format [\"one\", \"two\", \"three\"]: {$description}",
                "max_tokens" => $this->maxTokens,
            ]);
            !d($response);

            # cast to an array and save to the database
            $response = $response->toArray();
            $this->insertResponse($groupId, "keywords", $response);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        # process response into an array
        $keywords = json_decode(\Gazelle\Text::oneLine($response["choices"][0]["text"]), true);
        if (!$keywords || !is_array($keywords)) {
            throw new \Exception("openai fucked up jobId {$response["id"]}");
        }

        # convert to gazelle tags
        $keywords = array_unique($keywords);
        foreach ($keywords as $key => $value) {
            $value = \Illuminate\Support\Str::slug($value, ".");
            $keywords[$key] = $value;
        }

        # insert into the database
        # sections/upload/upload_handle.php
        foreach ($keywords as $keyword) {
            $keyword = \Misc::get_alias_tag($keyword);

            # inset into tags
            $query = "
                insert into tags (name, tagType, userId) values (?, ?, ?)
                on duplicate key update uses = uses + 1
            ";
            $app->dbNew->do($query, [$keyword, "openai", 0]);

            # get tagId
            $tagId = $app->dbNew->source->lastInsertId();

            # insert into torrents_tags
            $query = "
                insert into torrents_tags (tagId, groupId, userId) values (?, ?, ?)
                on duplicate key update tagId = tagId
            ";
            $app->dbNew->do($query, [$tagId, $groupId, 0]);
        }

        $app->cache->set($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * processDescription
     *
     * Remove garbage from a text prompt.
     *
     * @param string $description
     * @return string
     */
    private function processDescription(string $description): string
    {
        $description = \Gazelle\Text::parse($description);
        $description = strip_tags($description);
        $description = \Gazelle\Text::oneLine($description);

        return $description;
    }


    /**
     * insertResponse
     *
     * Write an OpenAI API response to the database.
     *
     * @param int $groupId
     * @param string $type
     * @param array $response
     * @return void
     */
    private function insertResponse(int $groupId, string $type, array $response): void
    {
        $app = \Gazelle\App::go();

        $allowedTypes = ["summary", "keywords"];
        if (!in_array($type, $allowedTypes)) {
            throw new \Exception("type must be one of " . implode(", ", $allowedTypes) . ", {$type} given");
        }

        # format the data
        $data = [
            "jobId" => $response["id"],
            "groupId" => $groupId,
            "object" => $response["object"],
            "created" => \Carbon\Carbon::createFromTimestamp($response["created"])->toDateTimeString(),
            "model" => $response["model"],
            "text" => \Gazelle\Text::oneLine($response["choices"][0]["text"]),
            "index" => $response["choices"][0]["index"],
            "logprobs" => $response["choices"][0]["logprobs"],
            "finishReason" => $response["choices"][0]["finish_reason"],
            "promptTokens" => $response["usage"]["prompt_tokens"],
            "completionTokens" => $response["usage"]["completion_tokens"],
            "totalTokens" => $response["usage"]["total_tokens"],
            "json" => json_encode($response),
            "type" => $type,
        ];

        # get the failCount
        $query = "select failCount from openai where groupId = ? and type = ?";
        $failCount = $app->dbNew->single($query, [$groupId, $type]) ?? 0;
        $data["failCount"] = $failCount;

        # increment on an error
        if (empty($data["text"]) || $data["finishReason"] !== "stop") {
            $data["failCount"]++;
        }

        # debug
        !d($data);

        # the query itself
        $query = "
            insert into openai (
                jobId, groupId,
                object, created, model,
                text, `index`, logprobs, finishReason,
                promptTokens, completionTokens, totalTokens,
                failCount, json, type
            )

            values (
                :jobId, :groupId,
                :object, :created, :model,
                :text, :index, :logprobs, :finishReason,
                :promptTokens, :completionTokens, :totalTokens,
                :failCount, :json, :type
            )
        ";

        # do it
        $app->dbNew->do($query, $data);
    }
} # class

<?php

declare(strict_types=1);

namespace Gazelle;


/**
 * OpenAI
 *
 * Client for the OpenAI API.
 * Example request for a common use case:
 * 
 * curl -X POST https://api.openai.com/v1/completions \
 *   -H 'Authorization: Bearer {secretKey}' \
 *   -H 'OpenAI-Organization: {organizationId}' \
 *   -H 'Content-Type: application/json' \
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
	`finishReason` VARCHAR(32),
	`promptTokens` SMALLINT,
	`completionTokens` SMALLINT,
	`totalTokens` SMALLINT,
    `failCount` TINYINT,
	`json` JSON,
    `type` VARCHAR(32),
	PRIMARY KEY (`id`,`jobId`,`groupId`)
);
 * 
 * todo: just namespace the app already
 */

class OpenAI
{
    # client and params
    public $client = null;
    private $maxTokens = 1000;
    private $model = "text-davinci-003";

    # cache settings
    private $cachePrefix = "openai_";
    private $cacheDuration = 3600; # one hour


    /**
     * __construct
     */
    function __construct(array $options = []) {
        $app = \App::go();

        if (!$app->env->enableOpenAi) {
            throw new \Exception("OpenAI support is disabled in the app config");
        }

        $openAiApi = $app->env->getPriv("openAiApi");
        $this->client = \OpenAI::client( $openAiApi["secretKey"] );

        return $this;
    }


    /**
     * test
     */
    function test(string $prompt = "hello"): OpenAI\Responses\Completions\CreateResponse
    {
        $response = $this->client->completions()->create([
            "model" => $this->model,
            "prompt" => $prompt,
            "max_tokens" => $this->maxTokens,
            "temperature" => 0,
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
     */
    function summarize(int $groupId): array
    {
        $app = \App::go();

        $app->debug["time"]->startMeasure("summarize", "openai: summarize torrent group description");

        # return cached if available
        $cacheKey = "{$this->cachePrefix}_summary_{$groupId}";
        $cacheHit = $app->cacheOld->get_value($cacheKey);
                
        if ($cacheHit) {
            return $cacheHit;
        }

        # get the torrent group description
        $description = $app->dbNew->single("select description from torrents_group where id = ?", [$groupId]);
        if (!$description) {
            throw new \Exception("groupId {$groupId} not found");
        }

        # process the description
        $description = \Text::parse($description);
        $description = strip_tags($description);
        $description = \Text::oneLine($description);
        #!d($description);exit;

        # query the openai api
        $response = $this->client->completions()->create([
            "model" => $this->model,
            "prompt" => "Summarize in 100 words: {$description}",
            "max_tokens" => $this->maxTokens,
            "temperature" => 0,
        ]);
        !d($response);

        # cast to an array and save to the database
        $response = $response->toArray();
        $this->insertResponse($groupId, "summary", $response);

        $app->cacheOld->cache_value($cacheKey, $response, $this->cacheDuration);
        return $response;
    }


    /**
     * keywords
     * 
     * Generate a list of keywords from a summary (good) or a torrent group description (bad).
     * Store the summary in the database for later use.
     * 
     * @see https://beta.openai.com/docs/api-reference/completions
     */
    function keywords(int $groupId): array
    {
        $app = \App::go();

        $app->debug["time"]->startMeasure("keywords", "openai: keywords from summary or torrent group description");

        # return cached if available
        $cacheKey = "{$this->cachePrefix}_keywords_{$groupId}";
        $cacheHit = $app->cacheOld->get_value($cacheKey);
                        
        if ($cacheHit) {
            return $cacheHit;
        }
        

    }


    /**
     * insertResponse
     * 
     * Write an OpenAI API response to the database.
     */
    private function insertResponse(int $groupId, string $type, array $response) {
        $app = \App::go();

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
            "text" => \Text::oneLine($response["choices"][0]["text"]),
            "index" => $response["choices"][0]["index"],
            "logprobs" => $response["choices"][0]["logprobs"],
            "finishReason" => $response["choices"][0]["finish_reason"],
            "promptTokens" => $response["usage"]["prompt_tokens"],
            "completionTokens" => $response["usage"]["completion_tokens"],
            "totalTokens" => $response["usage"]["total_tokens"],
            "json" => json_encode($response),
            "type" => $type,

            /*
            # You must include a unique parameter marker for each value you wish to pass in to the statement when you call PDOStatement::execute().
            # You cannot use a named parameter marker of the same name more than once in a prepared statement, unless emulation mode is on.
            # https://www.php.net/manual/en/pdo.prepare.php
            "jobIdUpdate" => $response["id"],
            "objectUpdate" => $response["object"],
            "modelUpdate" => $response["model"],
            "textUpdate" => \Text::oneLine($response["choices"][0]["text"]),
            "indexUpdate" => $response["choices"][0]["index"],
            "logprobsUpdate" => $response["choices"][0]["logprobs"],
            "finishReasonUpdate" => $response["choices"][0]["finish_reason"],
            "jsonUpdate" => json_encode($response),
            */
        ];

        # get the failCount
        $query = "select failCount from openai where groupId = ?";
        $failCount = $app->dbNew->single($query, [$groupId]) ?? 0;
        $data["failCount"] = $failCount;

        # increment on an error
        if (empty($data["text"]) || $data["finishReason"] !== "stop") {
            $data["failCount"] = $failCount++;
            #$data["failCountUpdate"] = $failCount++;
        }

        /*
        # get the tokens used
        $query = "select promptTokens, completionTokens, totalTokens from openai where groupId = ?";
        $row = $app->dbNew->row($query, [$groupId]);

        if ($row) {
            $data["promptTokensUpdate"] = $row["promptTokens"] + $response["usage"]["prompt_tokens"];
            $data["completionTokensUpdate"] = $row["completionTokens"] + $response["usage"]["completion_tokens"];
            $data["totalTokensUpdate"] = $row["totalTokens"] + $response["usage"]["total_tokens"];
        }
        */

        # debug
        #!d($data);exit;

        # the upsert query
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

        /*
        $query = "
        insert into openai (
            jobId, groupId,
            object, created, model,
            text, `index`, logprobs, finishReason,
            promptTokens, completionTokens, totalTokens,
            failCount, json
        )

        values (
            :jobId, :groupId,
            :object, :created, :model,
            :text, :index, :logprobs, :finishReason,
            :promptTokens, :completionTokens, :totalTokens,
            :failCount, :json
        )

        on duplicate key update
            jobId = :jobIdUpdate,
            object = :objectUpdate, model = :modelUpdate,
            text = :textUpdate, `index` = :indexUpdate, logprobs = :logprobsUpdate, finishReason = :finishReasonUpdate,
            promptTokens = :promptTokensUpdate, completionTokens = :completionTokensUpdate, totalTokens = :totalTokensUpdate,
            failCount = :failCountUpdate, json = :jsonUpdate
        ";
        */

        #!d($query);exit;

        # do it
        $app->dbNew->do($query, $data);
    }
} # class

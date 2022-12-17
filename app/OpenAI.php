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
 *     "max_tokens": 500
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
	`torrentGroupId` INT NOT NULL,
	`object` VARCHAR(32),
	`created` TIMESTAMP,
	`updated` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`model` VARCHAR(32),
	`text` TEXT,
	`index` INT,
	`logprobs` INT,
	`finishReason` VARCHAR(32),
	`promptTokens` INT,
	`completionTokens` INT,
	`totalTokens` INT,
	`json` JSON,
	KEY `torrentGroupId` (`torrentGroupId`,`text`) USING BTREE,
	PRIMARY KEY (`id`,`jobId`,`torrentGroupId`)
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

    # cache
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
    function test(string $prompt = "hello") {
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
     * Generates a summary of a torrent group description.
     * Stores the summary in the database for later use.
     * 
     * @see https://beta.openai.com/docs/api-reference/completions
     */
    function summarize(int $groupId) {
        $app = \App::go();

        $description = $app->dbNew->single("select description from torrents_group where id = ?", [$groupId]);
        if (!$description) {
            throw new \Exception("groupId {$groupId} not found");
        }

        $description = \Text::parse($description);
        $description = strip_tags($description);
        #!d($description);

        /*
        $response = $this->client->completions()->create([
            "model" => $this->model,
            "prompt" => "Summarize in 100 words: {$description}",
            "max_tokens" => $this->maxTokens,
            "temperature" => 0,
        ]);

        return $response;
        **/
    }
} # class

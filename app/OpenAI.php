<?php

declare(strict_types=1);


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
 * todo: just namespace the app already
 */

class OpenAI
{
    private $baseUri = "https://api.openai.com/v1/";
    private $client = null;
    private $maxTokens = 500;
    private $model = "text-davinci-003";


    /**
     * __construct
     */
    function __construct(array $options = []) {
        $app = App::go();

        if (!$app->enableOpenAi) {
            throw new Exception("OpenAI support is disabled in the app config");
        }

        $openAiApi = $app->env->getPriv("openAiApi");
        $this->client = OpenAI::client( $openAiApi["secretKey"] );

        return $this;
    }


    /**
     * test
     */
    function test() {
        $response = $this->client->completions()->create([
            "model" => $this->model,
            "prompt" => "say hello",
            "max_tokens" => 6,
            "temperature" => 0,
        ]);

        return $response;
    }
} # class

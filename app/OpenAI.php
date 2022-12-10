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
 * todo: just namespace the app already
 */

class OpenAI
{
    # client and params
    private $client = null;
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
    function test() {
        $response = $this->client->completions()->create([
            "model" => $this->model,
            "prompt" => "say hello",
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

        /*
        # strip_bbcode
        # https://github.com/phpbb/phpbb/blob/master/phpBB/includes/functions_content.php
        $text = $description;

        $uid = '[0-9a-z]{5,}';
        $text = preg_replace("#\[\/?[a-z0-9\*\+\-]+(?:=(?:&quot;.*&quot;|[^\]]*))?(?::[a-z])?(\:$uid)\]#", ' ', $text);

        # get_preg_expression
        # https://github.com/phpbb/phpbb/blob/master/phpBB/includes/functions.php
        $match = array(
				'#<!\-\- e \-\-><a href="mailto:(.*?)">.*?</a><!\-\- e \-\->#',
				'#<!\-\- l \-\-><a (?:class="[\w-]+" )?href="(.*?)(?:(&amp;|\?)sid=[0-9a-f]{32})?">.*?</a><!\-\- l \-\->#',
				'#<!\-\- ([mw]) \-\-><a (?:class="[\w-]+" )?href="http://(.*?)">\2</a><!\-\- \1 \-\->#',
				'#<!\-\- ([mw]) \-\-><a (?:class="[\w-]+" )?href="(.*?)">.*?</a><!\-\- \1 \-\->#',
				'#<!\-\- s(.*?) \-\-><img src="\{SMILIES_PATH\}\/.*? \/><!\-\- s\1 \-\->#',
				'#<!\-\- .*? \-\->#s',
				'#<.*?>#s',
		);

		$replace = array('\1', '\1', '\2', '\1', '', '');
		$text = preg_replace($match, $replace, $text);

        !d($text);exit;
        */

        $response = $this->client->completions()->create([
            "model" => $this->model,
            "prompt" => "Summarize in 100 words: {$description}",
            "max_tokens" => $this->maxTokens,
            "temperature" => 0,
        ]);

        return $response;
    }
} # class

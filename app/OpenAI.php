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
 *     "max_tokens": 1024
 *   }'
 * 
 * @see https://beta.openai.com/docs/introduction
 */

class OpenAI
{
    /**
     * __construct
     */
    function __construct(array $options = []) {

    }
} # class

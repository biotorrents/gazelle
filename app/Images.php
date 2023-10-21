<?php

declare(strict_types=1);


/**
 * Gazelle\Images
 *
 * Thumbnail aide, mostly.
 *
 * @see https://github.com/biotorrents/image-host
 */

namespace Gazelle;

class Images
{
    # hmac hash algorithm to use with the image proxy
    private static $algorithm = "sha3-512";


    /**
     * process
     *
     * Determine the image URL.
     *
     * @param string $uri
     * @param string $thumbnail image proxy scale profile to use
     * @return string
     */
    public static function process(string|array $uri, $thumbnail = false): string
    {
        $app = App::go();

        $presharedKey = $app->env->private("imagePsk");

        if (empty($uri)) {
            return "";
        }

        if (preg_match("/^https:\/\/({$app->env->siteDomain}|{$app->env->imageDomain})\//", $uri) || $uri[0] === "/") {
            if (strpos($uri, "?") === false) {
                $uri .= "?";
            }

            return $uri;
        }

        return "https://"
            . $app->env->imageDomain
            . ($thumbnail ? "/{$thumbnail}/" : "/")
            . "?h="
            . rawurlencode(base64_encode(hash_hmac(self::$algorithm, $uri, $presharedKey, true)))
            . "&i="
            . urlencode($uri);
    }
} # class

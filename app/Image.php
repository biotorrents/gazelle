<?php

declare(strict_types=1);


/**
 * Image
 *
 * Thumbnail aide, mostly.
 */

namespace Gazelle;

class Image
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
        $app = \Gazelle\App::go();

        $presharedKey = $app->env->getPriv("imagePsk");

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

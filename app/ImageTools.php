<?php

declare(strict_types=1);


/**
 * ImageTools
 *
 * Thumbnail aide, mostly.
 */

class ImageTools
{
    /**
     * process
     *
     * Determine the image URL.
     * This takes care of the image proxy and thumbnailing.
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
            . rawurlencode(base64_encode(hash_hmac("sha256", $uri, $presharedKey, true)))
            . "&i="
            . urlencode($uri);
    }


    /**
     * blacklisted
     *
     * Checks if a link's host is (not) good.
     *
     * @param string $uri link to an image
     * @return boolean
     */
    public static function blacklisted(string $uri, bool $showError = true): bool|string
    {
        $blacklist = ["tinypic.com"];

        foreach ($blacklist as $item) {
            if (stripos($uri, $item)) {
                # show an error page
                if ($showError) {
                    error("{$item} isn't an allowed image host. Please use a different host.");
                }

                # it IS blacklisted
                return true;
            }
        } # foreach

        # it's NOT blacklisted
        return false;
    }
}

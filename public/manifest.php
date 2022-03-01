<?php
declare(strict_types=1);

require_once __DIR__.'/../config/app.php';

# For non-AJAX calls
if ($_SERVER['PHP_SELF'] === '/manifest') {
    manifest();
}

/**
 * manifest
 */
function manifest()
{
    $ENV = ENV::go();

    # https://developer.mozilla.org/en-US/docs/Web/Manifest
    $manifest = json_encode(
        [
            '$schema' => 'https://json.schemastore.org/web-manifest-combined.json',
            'name' => $ENV->SITE_NAME,
            'short_name' => $ENV->SITE_NAME,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#0288d1',
            'description' => $ENV->DESCRIPTION,
            'icons' => [
                [
                    'src' => '/images/liquidrop-bookish-1k.png',
                    'sizes' => '1024x1024',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/images/liquidrop-postmod-1k.png',
                    'sizes' => '1024x1024',
                    'type' => 'image/png',
                ],
            ],
            /*
            'related_applications' => [
                [
                    'platform' => 'play',
                    'url' => 'https://play.google.com/store/apps/details?id=cheeaun.hackerweb',
                ],
            ],
            */
        ],
        JSON_UNESCAPED_SLASHES
    );

    # Print header and $manifest for remote addresses
    # Return JSON for localhost (API manifest endpoint):
    #   api.php?action=manifest
    if ($_SERVER['REMOTE_ADDR'] !== "127.0.0.1") {
        header('Content-Type: application/json; charset=utf-8');
        echo $manifest;
        return;
    } else {
        return json_decode($manifest);
    }
}

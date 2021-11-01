<?php
declare(strict_types=1);

require_once __DIR__.'/classes/config.php';

# For non-AJAX calls
if ($_SERVER['PHP_SELF'] === '/manifest.php') {
    manifest();
}

function manifest()
{
    $ENV = ENV::go();

    $manifest = <<<JSON
{
  "name": "$ENV->SITE_NAME",
  "short_name": "$ENV->SITE_NAME",
  "description": "$ENV->DESCRIPTION",
  "start_url": "index.php",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#0288d1",
  "icons": [{
    "src": "$ENV->STATIC_SERVER/common/favicon-1k.png",
    "sizes": "1024x1024",
    "type": "image/png"
  }]
}
JSON;

    # Print header and $manifest for remote addresses
    # Return JSON for localhost (API manifest endpoint):
    #   api.php?action=manifest
    if ($_SERVER['REMOTE_ADDR'] !== "127.0.0.1") {
        header('Content-type: application/json; charset=utf-8');
        echo $manifest;
        return;
    } else {
        return json_decode($manifest);
    }
}

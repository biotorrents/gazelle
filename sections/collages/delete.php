<?php

declare(strict_types=1);


/**
 * delete a collage
 */

$app = Gazelle\App::go();

$identifier ??= null;
if (!$identifier) {
    $app->error(404);
}

try {
    $collage = new Gazelle\Collages($identifier);
    $collage->delete();
} catch (Throwable $e) {
    $app->error(404);
}

# redirect to collages index
Gazelle\Http::redirect("/collages");

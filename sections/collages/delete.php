<?php

declare(strict_types=1);


/**
 * delete a collage
 */

$app = Gazelle\App::go();

$id ??= null;
if (!$id) {
    $app->error(404);
}

try {
    $collage = new Gazelle\Collages($id);
    $collage->delete();
} catch (Throwable $e) {
    $app->error(404);
}

# redirect to collages index
Gazelle\Http::redirect("/collages");

<?php

declare(strict_types=1);


/**
 * wiki
 */

# article
Flight::route("/wiki(/@identifier)", function ($identifier = null) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/wiki/article.php";
});

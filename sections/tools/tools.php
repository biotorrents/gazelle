<?php

declare(strict_types=1);


/**
 * main tools page
 */

$app = Gazelle\App::go();

if (!check_perms("users_mod")) {
    #$app->error(403);
}

$app->twig->display("admin/tools.twig", [
    "title" => "Admin tools",
    "sidebar" => true,
]);

<?php

declare(strict_types=1);


/**
 * list of user roles
 */

$app = Gazelle\App::go();

$role = new Gazelle\Roles();
$allRoles = $role->getAllRoles();

$app->twig->display("admin/roles/listAll.twig", [
    "title" => "User roles",
    "roles" => $allRoles,
]);

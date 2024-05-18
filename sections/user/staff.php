<?php

declare(strict_types=1);


/**
 * staff list
 */

$app = Gazelle\App::go();

$staff = Gazelle\Roles::getAllStaff();

$app->twig->display("user/staff.twig", [
    "title" => "Staff",
    "staff" => $staff ?? [],
]);

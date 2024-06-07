<?php

declare(strict_types=1);


/**
 * update organizations with remote data
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

# ror
$query = "
    select id from organizations
    where failCount < ? and degreesOfSeparation < ?
    and rorId is null and updatedById is null and deleted_at is null
";
$ref = $app->dbNew->column($query, [$app->env->failCount, $app->env->degreesOfSeparation]);

foreach ($ref as $row) {
    try {
        ~d("processing organization id {$row}");
        $organization = new Gazelle\Organizations($row);
        $data = $organization->hydrateFromRor();
        !d($data);
    } catch (Exception $e) {
        ~d("{$e->getMessage()} for organization id {$row}");
        continue;
    }

    # don't get rate limited
    echo "\n\n sleeping 5s \n\n";
    sleep(5);
}


# openalex
$query = "
    select id from organizations
    where failCount < ? and degreesOfSeparation < ?
    and rorId is not null and openAlexId is null and updatedById is null and deleted_at is null
";
$ref = $app->dbNew->column($query, [$app->env->failCount, $app->env->degreesOfSeparation]);

foreach ($ref as $row) {
    try {
        ~d("processing organization id {$row}");
        $organization = new Gazelle\Organizations($row);
        $data = $organization->supplementFromOpenAlex();
        !d($data);
    } catch (Exception $e) {
        ~d("{$e->getMessage()} for organization id {$row}");
        continue;
    }

    # don't get rate limited
    echo "\n\n sleeping 5s \n\n";
    sleep(5);
}

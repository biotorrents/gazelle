<?php

declare(strict_types=1);


/**
 * update creators with remote data
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

# openalex
$query = "
    select id from creators
    where failCount < ? and degreesOfSeparation < ?
    and openAlexId is null and updatedById is null and deleted_at is null
";
$ref = $app->dbNew->column($query, [$app->env->failCount, $app->env->degreesOfSeparation]);

foreach ($ref as $row) {
    try {
        ~d("processing creator id {$row}");
        $creator = new Gazelle\Creators($row);
        $data = $creator->hydrateFromOpenAlex();
        !d($data);
    } catch (Exception $e) {
        ~d("{$e->getMessage()} for creator id {$row}");
        continue;
    }

    # don't get rate limited
    echo "\n\n sleeping 5s \n\n";
    sleep(5);
}

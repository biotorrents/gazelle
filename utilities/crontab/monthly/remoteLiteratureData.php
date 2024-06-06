<?php

declare(strict_types=1);


/**
 * update literature table with remote data
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

# semantic scholar
$query = "select id from literature where semanticScholarId is null and failCount < 3 and updated_at < date_sub(now(), interval 1 month)";
$ref = $app->dbNew->column($query);

foreach ($ref as $row) {
    try {
        ~d("literature id {$row}");
        $literature = new Gazelle\Literature($row);
        $data = $literature->hydrateFromSemanticScholar();
        ~d($data);
    } catch (Exception $e) {
        ~d("error: {$e->getMessage()} on literature {$row}");
        continue;
    }

    # don't get rate limited
    echo "\n\n sleeping 5s \n\n";
    sleep(5);
}

# openalex
$query = "select id from literature where openAlexId is null and failCount < 3 and updated_at < date_sub(now(), interval 1 month)";
$ref = $app->dbNew->column($query);

foreach ($ref as $row) {
    try {
        ~d("literature id {$row}");
        $literature = new Gazelle\Literature($row);
        $data = $literature->supplementFromOpenAlex();
        !d($data);
    } catch (Exception $e) {
        ~d("error: {$e->getMessage()} on literature {$row}");
        continue;
    }

    # don't get rate limited
    echo "\n\n sleeping 5s \n\n";
    sleep(5);
}

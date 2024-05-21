<?php

declare(strict_types=1);


/**
 * update the creators table with semantic scholar data
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

# get all creator id's without a semanticScholarId or haven't been updated in a month
$query = "select id from creators where semanticScholarId is null and failCount < 3 or updatedAt < date_sub(now(), interval 1 month)";
$ref = $app->dbNew->multi($query);

# loop through
foreach ($ref as $row) {
    # announce
    Gazelle\Text::figlet("creator {$row["id"]}");

    try {
        $creator = new Gazelle\Creators($row["id"]);
        $data = $creator->hydrateFromSemanticScholar();
        !d($data);
    } catch (Exception $e) {
        ~d("error: {$e->getMessage()} on creator {$row["id"]}");
        continue;
    }

    # don't get rate limited
    echo "\n\n sleeping 5s \n\n";
    sleep(5);
}

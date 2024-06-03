<?php

declare(strict_types=1);


/**
 * update organizations table with ROR data
 */

require_once __DIR__ . "/../../../bootstrap/cli.php";

$app = Gazelle\App::go();

# get all organization id's without a rorId or haven't been updated in a month
$query = "select id from organizations where rorId is null";
#$query = "select id from organizations where rorId is null and failCount < 3 and updated_at < date_sub(now(), interval 1 month)";
$ref = $app->dbNew->column($query);

# loop through
foreach ($ref as $row) {
    # announce
    Gazelle\Text::figlet("organization");
    !d($row);

    try {
        $organization = new Gazelle\Organizations($row);
        $data = $organization->hydrateFromRor();
        !d($data);
    } catch (Exception $e) {
        ~d("error: {$e->getMessage()} on organization {$row}");
        continue;
    }

    # don't get rate limited
    echo "\n\n sleeping 5s \n\n";
    sleep(5);
}

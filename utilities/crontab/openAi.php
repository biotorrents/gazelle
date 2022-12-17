<?php
declare(strict_types=1);


/**
 * Generate OpenAI stuff for torrent groups missing it.
 * First do a summary, then do keywords to keep token use down.
 */

# cli bootstrap
require_once __DIR__."/../../bootstrap/cli.php";

# load up an openai instance
$openai = new Gazelle\OpenAI();

# select all groupId's without an ai gf
$query = "
    select distinct torrents_group.id from torrents_group
    left join openai on torrents_group.id = openai.groupId
    where openai.groupId is null
";
$ref = $app->dbNew->multi($query, []);
#!d($ref);exit;

foreach ($ref as $row) {
    # summary
    try {
        Text::figlet("summary: groupId {$row["id"]}", "green");
        $openai->summarize($row["id"]);

        echo "\n\n sleeping 5s \n\n";
        sleep(5);
    } catch (Exception $e) {
        Text::figlet("error", "red");
        ~d($e->getMessage());

        # update failCount
        $query = "update openai set failCount = failCount + 1 where groupId = ? and type = 'summary'";
        $app->dbNew->do($query, [ $row["id"] ]);

        continue;
    }

    # keywords
    try {
        Text::figlet("keywords: groupId {$row["id"]}", "green");
        $openai->keywords($row["id"]);

        echo "\n\n sleeping 5s \n\n";
        sleep(5);
    } catch (Exception $e) {
        Text::figlet("error", "red");
        ~d($e->getMessage());

        # update failCount
        $query = "update openai set failCount = failCount + 1 where groupId = ? and type = 'keywords'";
        $app->dbNew->do($query, [ $row["id"] ]);

        continue;
    }
}

# clean up the stragglers
$query = "select jobId, text from openai";
$ref = $app->dbNew->do($query, []);

foreach ($ref as $row) {
    if (empty($row["text"])) {
        Text::figlet("deleting empty {$row["jobId"]}", "blue");
        $query = "delete from openai where jobId = ?";
        $app->dbNew->do($query, [ $row["jobId"] ]);
    }
}

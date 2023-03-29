<?php

declare(strict_types=1);


/**
 * Generate OpenAI stuff for torrent groups missing it.
 * First do a summary, then do keywords to keep token use down.
 */

# cli bootstrap
require_once __DIR__."/../../bootstrap/cli.php";

# ensure only one is running
$currentWorkers = intval(system("ps ax | grep openAi.php | grep -v grep | wc -l"));
if ($currentWorkers > 1) {
    Text::figlet("too many workers", "red");
    exit;
}

# load up an openai instance
$openai = new Gazelle\OpenAI();

/** */

# select all groupId's without an ai gf
$query = "
    select torrents_group.id from torrents_group
    left join openai on torrents_group.id = openai.groupId
    where openai.groupId is null and openai.failCount < 3
";

$ref = $app->dbNew->multi($query, []);
#!d($ref);exit;

# loop through each groupId
foreach ($ref as $row) {
    # summary
    $failCount = 0;
    while ($failCount < 3) {
        try {
            Text::figlet("summary: groupId {$row["id"]}", "green");
            $openai->summarize($row["id"]);

            echo "\n\n sleeping 10s \n\n";
            sleep(10);

            break;
        } catch (Throwable $e) {
            Text::figlet("error", "red");
            ~d($e->getMessage());

            # update failCount
            $query = "update openai set failCount = failCount + 1 where groupId = ? and type = ?";
            $app->dbNew->do($query, [ $row["id"], "summary" ]);

            $failCount++;
        }
    } # while

    # keywords
    $failCount = 0;
    while ($failCount < 3) {
        try {
            Text::figlet("keywords: groupId {$row["id"]}", "green");
            $openai->keywords($row["id"]);

            echo "\n\n sleeping 10s \n\n";
            sleep(10);

            break;
        } catch (Throwable $e) {
            Text::figlet("error", "red");
            ~d($e->getMessage());

            # update failCount
            $query = "update openai set failCount = failCount + 1 where groupId = ? and type = ?";
            $app->dbNew->do($query, [ $row["id"], "keywords" ]);

            $failCount++;
        }
    } # while
} # foreach

/** */

# clean up the stragglers
$query = "select jobId, text, finishReason from openai";
$ref = $app->dbNew->do($query, []);

foreach ($ref as $row) {
    if (empty($row["text"]) || $row["finishReason"] !== "stop") {
        Text::figlet("deleting empty row", "blue");
        !d($row["jobId"]);

        $query = "delete from openai where jobId = ?";
        $app->dbNew->do($query, [ $row["jobId"] ]);
    }
} # foreach

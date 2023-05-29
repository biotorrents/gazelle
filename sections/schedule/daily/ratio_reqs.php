<?php

declare(strict_types=1);


/**
 * ratio requirements
 */

$app = \Gazelle\App::go();

# clear the old seed time history
$query = "
    delete from users_torrent_history
    where date < date(now() - interval 7 day) + 0
";
$app->dbNew->do($query, []);

# store the total seeded time for each user in a temp table
$query = "truncate table users_torrent_history_temp";
$app->dbNew->do($query, []);

$query = "
    insert into users_torrent_history_temp (userId, sumTime)
    select userId, sum(time) from users_torrent_history
    group by userId
";
$app->dbNew->do($query, []);

# insert a new row with `numTorrents` = 0 and `time` being the seconds short of 72 hours
# this is where we penalize torrents seeded for less than 72 hours
$query = "
    insert into users_torrent_history (userId, numTorrents, date, time)
    select userId, ?, utc_date() + 0, 259200 - sumTime from users_torrent_history_temp
    where sumTime < 259200
";
$app->dbNew->do($query, [0]);

# set the `weight` to the time seeding `numTorrents`
$query = "update users_torrent_history set weight = numTorrents * time";
$app->dbNew->do($query, []);

# calculate the average time spent seeding each of the currently active torrents
# this rounds the results to the nearest integer because seedingAvg is an int column
$query = "truncate table users_torrent_history_temp";
$app->dbNew->do($query, []);

$query = "
    insert into users_torrent_history_temp (userId, seedingAvg)
    select userId, sum(weight) / sum(time) from users_torrent_history
    group by userId
";
$app->dbNew->do($query, []);

# remove dummy entries for torrents seeded less than 72 hours
$query = "delete from users_torrent_history where numTorrents = ?";
$app->dbNew->do($query, [0]);

# gt each user's amount of snatches of existing torrents
$query = "truncate table users_torrent_history_snatch";
$app->dbNew->do($query, []);

$query = "
    insert into users_torrent_history_snatch (userId, numSnatches)
    select transfer_history.uid, count(distinct transfer_history.fid) from transfer_history
    join torrents on torrents.id = transfer_history.fid
    group by transfer_history.uid
";
$app->dbNew->do($query, []);

# get the fraction of snatched torrents seeded for at least 72 hours this week
# take the total number of hours seeded this week and divide that by 72 hours * `numSnatches`
$query = "
    update users_main
    join users_torrent_history_temp on users_torrent_history_temp.userId = users_main.userId
    join users_torrent_history_snatch on users_torrent_history_snatch.userId = users_main.userId
    set users_main.requiredRatioWork = (1 - (users_torrent_history_temp.seedingAvg / users_torrent_history_snatch.numSnatches))
    where users_torrent_history_snatch.numSnatches > 0
";
$app->dbNew->do($query, []);

# done: change from PHP_INT_MAX to INF when we get prepared statements working (because apparently that works)
$downloadBarrier = INF;
foreach ($app->env->ratioRequirements as $requirement) {
    list($downloadAmount, $unseededRatio, $fullySeededRatio) = $requirement;

    $query = "
        update users_main set requiredRatio = requiredRatioWork * ?
        where downloaded >= ? and downloaded < ?
    ";
    $app->dbNew->do($query, [$unseededRatio, $downloadAmount, $downloadBarrier]);

    $query = "
        update users_main set requiredRatio = ?
        where downloaded >= ? and downloaded < ? and requiredRatio < ?
    ";
    $app->dbNew->do($query, [$fullySeededRatio, $downloadAmount, $downloadBarrier, $fullySeededRatio]);

    # update the download barrier for the next iteration
    $downloadBarrier = $downloadAmount;
}

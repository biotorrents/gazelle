<?php

declare(strict_types=1);


/**
 * record seeding
 */

$app = \Gazelle\App::go();

# record who's seeding how much, for ratio watch
$query = "truncate table users_torrent_history_temp";
$app->dbNew->do($query, []);

# find seeders that have announced within the last hour
$query = "
    insert into users_torrent_history_temp (userId, numTorrents)
    select uid, count(distinct fid) from transfer_history
    where last_announce > unix_timestamp(now() - interval 1 hour) and remaining = ?
    group by uid
";
$app->dbNew->do($query, [0]);

# mark new records as "checked" and set the current time as the time the user started seeding `numTorrents`
# finished = 1 means that the user hasn't been seeding exactly `numTorrents` earlier today
# this query will only do something if the next one inserted new rows last hour
$query = "
    update users_torrent_history
    join users_torrent_history_temp on users_torrent_history_temp.userId = users_torrent_history.userId
        and users_torrent_history_temp.numTorrents = users_torrent_history.numTorrents
    set users_torrent_history.finished = ?, users_torrent_history.lastTime = unix_timestamp(now())
    where finished = ? and date = utc_date() + 0
";
$app->dbNew->do($query, [0, 1]);

# insert new rows for users who haven't been seeding exactly `numTorrents` earlier today,
# and update the time spent seeding `numTorrents` for the others
# primary table index: [userId, numTorrents, date]
$query = "
    insert into users_torrent_history (userId, numTorrents, date)
    select userId, numTorrents, utc_date() + 0 from users_torrent_history_temp
    on duplicate key update
        time = time + unix_timestamp(now()) - lastTime,
        lastTime = unix_timestamp(now())
";
$app->dbNew->do($query, []);

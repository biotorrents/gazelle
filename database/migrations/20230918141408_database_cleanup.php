<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DatabaseCleanup extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $app = \Gazelle\App::go();

        # featured_albums
        $query = "drop table if exists featured_albums";
        $app->dbNew->do($query);

        # last_sent_email
        $query = "drop table if exists last_sent_email";
        $app->dbNew->do($query);

        # new_info_hashes
        $query = "drop table if exists new_info_hashes";
        $app->dbNew->do($query);

        # ocelot_query_times
        $query = "drop table if exists ocelot_query_times";
        $app->dbNew->do($query);

        # top10_history
        $query = "drop table if exists top10_history";
        $app->dbNew->do($query);

        # top10_history_torrents
        $query = "drop table if exists top10_history_torrents";
        $app->dbNew->do($query);

        # torrents_logs_new
        $query = "drop table if exists torrents_logs_new";
        $app->dbNew->do($query);

        # torrents_recommended
        $query = "drop table if exists torrents_recommended";
        $app->dbNew->do($query);

        # u2f
        $query = "drop table if exists u2f";
        $app->dbNew->do($query);

        # users_points
        $query = "drop table if exists users_points";
        $app->dbNew->do($query);

        # users_points_requests
        $query = "drop table if exists users_points_requests";
        $app->dbNew->do($query);
    }
}

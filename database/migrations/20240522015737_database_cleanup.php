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
        $app = Gazelle\App::go();

        # artists_group
        $query = "drop table if exists artists_group";
        $app->dbNew->do($query, []);

        # artists_tags
        $query = "drop table if exists artists_tags";
        $app->dbNew->do($query, []);

        # collages_artists
        $query = "drop table if exists collages_artists";
        $app->dbNew->do($query, []);

        # requests_artists
        $query = "drop table if exists requests_artists";
        $app->dbNew->do($query, []);

        # torrents_artists
        $query = "drop table if exists torrents_artists";
        $app->dbNew->do($query, []);

        # users_donor_ranks
        $query = "drop table if exists users_donor_ranks";
        $app->dbNew->do($query, []);
    }
}

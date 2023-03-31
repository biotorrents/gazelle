<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateTorrentVarcharColumns extends AbstractMigration
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

        # torrents_group
        $query = "
            alter table torrents_group
            modify column workgroup varchar(128),
            modify column location varchar(128),
            modify column identifier varchar(64),
            modify column tag_list varchar(512)
        ";
        $app->dbNew->do($query, []);

        # torrents
        $query = "
            alter table torrents
            modify column version varchar(32),
            modify column archive varchar(32),
            modify column media varchar(32),
            modify column container varchar(32),
            modify column codec varchar(32),
            modify column resolution varchar(32)
        ";
        $app->dbNew->do($query, []);

        # users_main
        $query = "
            alter table users_main
            modify column username varchar(32)
        ";
        $app->dbNew->do($query, []);
    }
}

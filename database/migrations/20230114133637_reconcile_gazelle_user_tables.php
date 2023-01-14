<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ReconcileGazelleUserTables extends AbstractMigration
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
    public function change()
    {
        $app = App::go();

        # todo: move everything into a users_extra table later
        # the existing schema has significance for ocelot
        $query = "alter table users_main add column userId int";
        $app->dbNew->do($query, []);
    }
}

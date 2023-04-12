<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveSphinxTables extends AbstractMigration
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

        $query = "
        drop table if exists
            sphinx_a,
            sphinx_delta,
            sphinx_index_last_pos,
            sphinx_requests,
            sphinx_requests_delta,
            sphinx_t,
            sphinx_tg
        ";
        $app->dbNew->do($query, []);
    }
}

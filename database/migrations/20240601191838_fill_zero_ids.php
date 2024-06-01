<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FillZeroIds extends AbstractMigration
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

        # select all tables
        $query = "show tables";
        $tables = $app->dbNew->column($query, []);

        foreach ($tables as $table) {
            $query = "show columns from {$table} like 'id'";
            $good = $app->dbNew->single($query, []);

            if (!$good) {
                continue;
            }

            $query = "update {$table} set id = ? where id = 0";
            $app->dbNew->do($query, [ $app->dbNew->shortUuid() ]);
        }
    }
}

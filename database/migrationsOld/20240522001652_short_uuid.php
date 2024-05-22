<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ShortUuid extends AbstractMigration
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

        $query = "select table_name from information_schema.tables where table_schema = 'gazelle_staging'";
        $ref = $app->dbNew->column($query, []);

        foreach ($ref as $row) {
            $query = "alter table {$row} modify column id bigint unsigned not null default uuid_short()";
            $app->dbNew->do($query, []);
        }
    }
}

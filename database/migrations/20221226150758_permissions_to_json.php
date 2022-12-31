<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class PermissionsToJson extends AbstractMigration
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

        $query = "select id, `values` from permissions";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $values = unserialize($row["values"]);
            $values = array_keys($values);
            $values = json_encode($values);

            $query = "update permissions set `values` = ? where id = ?";
            $app->dbNew->do($query, [ $values, $row["id"] ]);
        }
    }
}

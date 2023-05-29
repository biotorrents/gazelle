<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MigrateAllowedClients extends AbstractMigration
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

        $query = "select * from xbt_client_whitelist";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert into approved_clients (peer_id, title) values (?, ?)";
            $app->dbNew->do($query, [ $row["peer_id"], $row["vstring"] ]);
        }
    }
}

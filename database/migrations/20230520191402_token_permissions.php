<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TokenPermissions extends AbstractMigration
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

        $query = "update api_tokens set permissions = ? where deleted_at is null";
        $app->dbNew->do($query, [ json_encode(["create", "read", "update", "delete"]) ]);
    }
}

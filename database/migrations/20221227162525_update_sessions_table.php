<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateSessionsTable extends AbstractMigration
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

        $query = "drop table users_sessions";
        $app->dbNew->do($query, []);

        $query = "
            CREATE TABLE `users_sessions` (
                `userId` INT NOT NULL,
                `sessionId` VARCHAR(128) NOT NULL,
                `expires` DATETIME NOT NULL,
                `ipAddress` VARCHAR(128),
                `userAgent` TEXT,
                PRIMARY KEY (`userId`,`sessionId`)
            );
        ";
        $app->dbNew->do($query, []);
    }
}

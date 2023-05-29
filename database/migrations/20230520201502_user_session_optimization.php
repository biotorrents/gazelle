<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UserSessionOptimization extends AbstractMigration
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

        /*
        # these are encrypted
        $query = "
            ALTER TABLE `users_sessions`
            MODIFY COLUMN `ipAddress` varbinary(16) DEFAULT NULL;
        ";
        $app->dbNew->do($query, []);
        */

        $query = "
            ALTER TABLE `users_sessions`
            MODIFY COLUMN `userAgent` varchar(255) DEFAULT NULL;
        ";
        $app->dbNew->do($query, []);

        $query = "
            ALTER TABLE `users_sessions`
            ADD UNIQUE INDEX `userId_sessionId` (`userId`, `sessionId`);
        ";
        $app->dbNew->do($query, []);
    }
}

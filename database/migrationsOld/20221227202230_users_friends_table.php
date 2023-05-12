<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UsersFriendsTable extends AbstractMigration
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
        $app = \Gazelle\App::go();

        $query = "drop table if exists friends";
        $app->dbNew->do($query, []);

        $query = "
            CREATE TABLE IF NOT EXISTS `users_friends` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `userId` INT NOT NULL,
                `friendId` INT NOT NULL,
                `comment` VARCHAR(255),
                `created` DATETIME DEFAULT NOW(),
                `updated` DATETIME ON UPDATE NOW(),
                PRIMARY KEY (`id`, `userId`,`friendId`)
            );
        ";
        $app->dbNew->do($query, []);
    }
}

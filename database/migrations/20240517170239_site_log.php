<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SiteLog extends AbstractMigration
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

        $query = "
            CREATE TABLE IF NOT EXISTS `site_log` (
                `id` bigint UNSIGNED NOT NULL DEFAULT uuid_short(),
                `userId` bigint UNSIGNED NOT NULL,
                `contentId` bigint UNSIGNED NOT NULL,
                `contentType` varchar(255) NOT NULL,
                `action` ENUM('create', 'read', 'update', 'delete') NOT NULL,
                `description` varchar(255) NOT NULL,
                `createdAt` datetime DEFAULT current_timestamp(),
                `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
                `deletedAt` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `user_content_id` (`userId`, `contentId`)
            )
        ";

        $app->dbNew->do($query);
    }
}

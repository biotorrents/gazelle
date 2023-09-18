<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BonusPoints extends AbstractMigration
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
            create table `bonus_point_purchases` (
                `id` bigint unsigned not null default uuid_short(),
                `userId` bigint unsigned not null,
                `key` varchar(128) not null,
                `value` varchar(255) not null,
                `created_at` datetime default current_timestamp(),
                `updated_at` datetime default null on update current_timestamp(),
                `deleted_at` datetime default null,
                primary key (`id`),
                unique key `id` (`id`,`userId`,`key`)
            ) engine=innodb default charset=utf8mb4 collate=utf8mb4_unicode_ci
        ";

        $app->dbNew->do($query);
    }
}

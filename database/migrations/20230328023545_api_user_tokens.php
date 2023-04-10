<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ApiUserTokens extends AbstractMigration
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

        # change data types
        $query = "
            alter table api_user_tokens
                modify column name varchar(255) not null,
                modify column token varchar(255) not null
        ";
        $app->dbNew->do($query, []);
    }
}

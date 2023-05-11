<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropUselessTables extends AbstractMigration
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
            drop table if exists
                artists_alias,
                artists_aliases,
                comments_edits,
                donations,
                donor_rewards,
                forums_specific_rules,
                library_contest,
                reports_email_blacklist,
                users_warnings_forums
        ";
        $app->dbNew->do($query, []);
    }
}

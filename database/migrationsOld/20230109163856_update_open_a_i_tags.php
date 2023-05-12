<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateOpenAITags extends AbstractMigration
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

        # change data definition
        $query = "alter table tags modify tagType varchar(16)";
        $app->dbNew->do($query, []);

        # update userId 0 tags to openai
        $query = "update tags set tagType = ? where userId = ?";
        $app->dbNew->do($query, ["openai", 0]);
    }
}

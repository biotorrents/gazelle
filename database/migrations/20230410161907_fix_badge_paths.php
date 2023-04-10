<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixBadgePaths extends AbstractMigration
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

        # get the data
        $query = "select * from badges";
        $ref = $app->dbNew->multi($query, []);

        # loop through it
        foreach ($ref as $row) {
            # set the new path
            $newPath = str_replace("/static/common/", "/images/", $row["Icon"]);

            # update the database
            $query = "update badges set icon = ? where id = ?";
            $app->dbNew->do($query, [ $newPath, $row["ID"] ]);
        }
    }
}

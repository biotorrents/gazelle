<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatorTable extends AbstractMigration
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

        # https://api.semanticscholar.org/api-docs/graph#tag/Author-Data/operation/get_graph_get_author
        $query = "
            CREATE TABLE IF NOT EXISTS `creators` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `orcid` VARCHAR(32) DEFAULT NULL,
                `semanticScholarId` INT DEFAULT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `aliases` JSON DEFAULT NULL,
                `affiliations` JSON DEFAULT NULL,
                `homepage` VARCHAR(255) DEFAULT NULL,
                `paperCount` INT DEFAULT NULL,
                `citationCount` INT DEFAULT NULL,
                `hIndex` INT DEFAULT NULL,
                `created` DATETIME DEFAULT NOW(),
                `updated` DATETIME DEFAULT NOW() ON UPDATE CURRENT_TIMESTAMP,
                `deleted` DATETIME DEFAULT NULL,
                KEY `id` (`id`,`orcid`,`semanticScholarId`) USING BTREE,
                PRIMARY KEY (`id`)
            );
        ";
        $app->dbNew->do($query, []);

        # migrate data from artists_group
        $query = "select artistId, name from artists_group order by artistId asc";
        $ref = $app->dbNew->multi($query, []);

        # loop through it
        foreach ($ref as $row) {
            $query = "insert into creators (name) values (?)";
            $app->dbNew->do($query, [ $row["name"] ]);
        }
    }
}

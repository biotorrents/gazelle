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
            CREATE TABLE `creators` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `orcid` VARCHAR(32),
                `semanticScholarId` INT,
                `name` VARCHAR(255) NOT NULL,
                `aliases` JSON,
                `affiliations` JSON,
                `homepage` VARCHAR(255),
                `paperCount` INT DEFAULT '0',
                `citationCount` INT DEFAULT '0',
                `hIndex` INT DEFAULT '0',
                KEY `id` (`id`,`orcid`,`semanticScholarId`) USING BTREE,
                PRIMARY KEY (`id`,`orcid`,`semanticScholarId`)
            );
        ";
        $app->dbNew->do($query, []);
    }
}

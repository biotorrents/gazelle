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
                `id` BIGINT NOT NULL AUTO_INCREMENT,
                `uuid` BINARY(16) NOT NULL,
                `orcid` VARCHAR(32) DEFAULT NULL,
                `semanticScholarId` BIGINT DEFAULT NULL,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `aliases` JSON DEFAULT NULL,
                `affiliations` JSON DEFAULT NULL,
                `homepage` VARCHAR(255) DEFAULT NULL,
                `paperCount` INT DEFAULT NULL,
                `citationCount` INT DEFAULT NULL,
                `hIndex` INT DEFAULT NULL,
                `created` DATETIME DEFAULT NOW(),
                `updated` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                `deleted` DATETIME DEFAULT NULL,
                KEY `id` (`id`,`uuid`,`orcid`,`semanticScholarId`) USING BTREE,
                PRIMARY KEY (`id`),
                UNIQUE KEY (`uuid`)
            );
        ";
        $app->dbNew->do($query, []);

        # migrate data from artists_group
        $query = "select artistId, name from artists_group order by artistId asc";
        $ref = $app->dbNew->multi($query, []);

        # loop through it
        foreach ($ref as $row) {
            $uuid = $app->dbNew->uuid();
            $slug = $app->dbNew->slug($row["name"]);

            $query = "insert into creators (uuid, name, slug) values (?, ?, ?)";
            $app->dbNew->do($query, [$uuid, $row["name"], $slug]);
        }
    }
}

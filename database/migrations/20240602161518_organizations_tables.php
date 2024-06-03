<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OrganizationsTables extends AbstractMigration
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

        # create the main table
        # https://ror.readme.io/v2/docs/data-structure
        $query = "
            create table if not exists organizations (
                id bigint unsigned default uuid_short() primary key,
                rorId varchar(32),
                grid varchar(16),

                name varchar(255) not null,
                acronym varchar(16),
                established tinyint unsigned,
                status varchar(16),
                relationships json,

                latitude decimal,
                longitude decimal,
                country char(2),
                state varchar(16),
                city varchar(64),
                postalCode varchar(16),

                type enum('education', 'funder', 'healthcare', 'company', 'archive', 'nonprofit', 'government', 'facility', 'other'),
                homepage varchar(255),
                wikipedia varchar(255),
                failCount tinyint unsigned default 0,
                degreesOfSeparation tinyint unsigned default 1,

                created_at timestamp default current_timestamp,
                updated_at timestamp default current_timestamp on update current_timestamp,
                deleted_at timestamp,

                index id_rorId_grid (id, rorId, grid),
                index id_name_acronym (id, name, acronym)
            )
        ";
        $app->dbNew->do($query, []);

        # create a generic links table
        $query = "
            create table if not exists organizations_links (
                id bigint unsigned default uuid_short() primary key,

                objectId bigint unsigned not null,
                contentId bigint unsigned not null,
                contentType varchar(16) not null,

                created_at timestamp default current_timestamp,
                updated_at timestamp default current_timestamp on update current_timestamp,
                deleted_at timestamp,

                unique index objectId_contentId_contentType (objectId, contentId, contentType)
            )
        ";
        $app->dbNew->do($query, []);
    }
}

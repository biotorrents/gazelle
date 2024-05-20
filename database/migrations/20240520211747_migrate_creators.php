<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MigrateCreators extends AbstractMigration
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

        # create the groups linking table
        $query = "
            CREATE TABLE IF NOT EXISTS `creators_groups` (
                `id` bigint(20) unsigned NOT NULL DEFAULT uuid_short(),
                `creatorId` bigint(20) unsigned NOT NULL,
                `torrentId` bigint(20) unsigned NOT NULL,
                `userId` bigint(20) unsigned NOT NULL,
                `createdAt` datetime DEFAULT current_timestamp(),
                `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
                `deletedAt` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `id_creatorId_torrentId` (`id`,`creatorId`,`torrentId`)
            )
        ";
        $app->dbNew->do($query, []);

        # create the requests linking table
        $query = "
            CREATE TABLE IF NOT EXISTS `creators_requests` (
                `id` bigint(20) unsigned NOT NULL DEFAULT uuid_short(),
                `creatorId` bigint(20) unsigned NOT NULL,
                `requestId` bigint(20) unsigned NOT NULL,
                `userId` bigint(20) unsigned NOT NULL,
                `createdAt` datetime DEFAULT current_timestamp(),
                `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
                `deletedAt` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `id_creatorId_requestId` (`id`,`creatorId`,`requestId`)
            )
        ";
        $app->dbNew->do($query, []);

        # create the tags linking table
        $query = "
            CREATE TABLE IF NOT EXISTS `creators_requests` (
                `id` bigint(20) unsigned NOT NULL DEFAULT uuid_short(),
                `creatorId` bigint(20) unsigned NOT NULL,
                `tagId` bigint(20) unsigned NOT NULL,
                `userId` bigint(20) unsigned NOT NULL,
                `createdAt` datetime DEFAULT current_timestamp(),
                `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
                `deletedAt` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `id_creatorId_tagId` (`id`,`creatorId`,`tagId`)
            )
        ";
        $app->dbNew->do($query, []);

        # import the data from artists_group
        $query = "select * from artists_group";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert into creators (id, name) values (?, ?)";
            $app->dbNew->do($query, [ $row["ArtistID"], $row["Name"] ]);
        }

        # import the data from torrents_artists
        # note this is really for torrent groups
        $query = "select * from torrents_artists";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert into creators_groups (creatorId, torrentId, userId) values (?, ?, ?)";
            $app->dbNew->do($query, [ $row["ArtistID"], $row["GroupID"], $row["UserID"] ]);
        }

        # import the data from requests_artists
        $query = "select * from requests_artists";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            # there is not userId column in requests_artists
            $query = "insert into creators_requests (creatorId, requestId, userId) values (?, ?, ?)";
            $app->dbNew->do($query, [ $row["ArtistID"], $row["RequestID"], 0 ]);
        }
    }
}

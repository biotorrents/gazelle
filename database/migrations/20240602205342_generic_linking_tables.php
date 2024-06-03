<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class GenericLinkingTables extends AbstractMigration
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

        $table = null;
        $definition = "
            create table if not exists {{ table }} (
                id bigint unsigned default uuid_short(),
    
                objectId bigint unsigned not null,
                userId bigint unsigned not null,
                contentId bigint unsigned not null,
                contentType varchar(16) not null,
    
                created_at timestamp default current_timestamp,
                updated_at timestamp default current_timestamp on update current_timestamp,
                deleted_at timestamp,
    
                primary key (id),
                unique index objectId_userId_contentId_contentType (objectId, userId, contentId, contentType)
            )
        ";

        /** collages */

        $table = "collages_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        # torrent groups
        $query = "select * from collages_torrents";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["collageId"], $row["userId"], $row["groupId"], Gazelle\TorrentGroups::$type ]);
        }

        # conversations
        $query = "select * from conversations_threads where contentType = ?";
        $ref = $app->dbNew->multi($query, [ Gazelle\Collages::$type ]);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["contentId"], $row["userId"], $row["id"], Gazelle\Conversations::$type ]);
        }

        /** conversations */

        $table = "conversations_threads_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        # generic content
        $query = "select * from conversations_threads";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["id"], $row["userId"], $row["contentId"], $row["contentType"] ]);
        }

        /** creators */

        $table = "creators_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        # literature
        $query = "select * from literature_creators";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["creatorId"], 2, $row["literatureId"], Gazelle\Literature::$type ]);
        }

        # torrent groups
        $query = "select * from creators_groups";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["creatorId"], $row["userId"], $row["groupId"], Gazelle\TorrentGroups::$type ]);
        }

        # requests
        $query = "select * from creators_requests";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["creatorId"], 2, $row["requestId"], Gazelle\Requests::$type ]);
        }

        # conversations
        $query = "select * from conversations_threads where contentType = ?";
        $ref = $app->dbNew->multi($query, [ Gazelle\Collages::$type ]);

        foreach ($ref as $row) {
            $query = "insert ignore into collages_links (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["contentId"], $row["userId"], $row["id"], Gazelle\Conversations::$type ]);
        }

        /** literature */

        $table = "literature_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        # creators
        $query = "select * from literature_creators";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["literatureId"], 2, $row["creatorId"], Gazelle\Creators::$type ]);
        }

        # torrent groups
        $query = "select * from literature_groups";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["literatureId"], 2, $row["groupId"], Gazelle\TorrentGroups::$type ]);
        }

        # requests
        $query = "select * from literature_requests";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["literatureId"], 2, $row["requestId"], Gazelle\Requests::$type ]);
        }

        /** messages */

        $table = "conversations_messages_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        /** organizations */

        $table = "organizations_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        /** requests */

        $table = "requests_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        # creators
        $query = "select * from creators_requests";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["requestId"], 2, $row["creatorId"], Gazelle\Creators::$type ]);
        }

        # tags
        $query = "select * from requests_tags";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["requestId"], 2, $row["tagId"], Gazelle\Tags::$type ]);
        }

        /** roles */

        $table = "roles_permissions_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        /** site log */

        $table = "site_log_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        /** tags */

        $table = "tags_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        # requests
        $query = "select * from requests_tags";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["tagId"], 2, $row["requestId"], Gazelle\Requests::$type ]);
        }

        # torrent groups
        $query = "select * from torrents_tags";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["TagID"], $row["UserID"], $row["GroupID"], Gazelle\TorrentGroups::$type ]);
        }

        /** torrent groups */

        $table = "torrents_group_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        # collages
        $query = "select * from collages_torrents";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["groupId"], $row["userId"], $row["collageId"], Gazelle\Collages::$type ]);
        }

        # creators
        $query = "select * from creators_groups";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["groupId"], $row["userId"], $row["creatorId"], Gazelle\Creators::$type ]);
        }

        # literature
        $query = "select * from literature_groups";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["groupId"], 2, $row["literatureId"], Gazelle\Literature::$type ]);
        }

        # torrents
        $query = "select * from torrents";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["GroupID"], $row["UserID"], $row["id"], Gazelle\Torrents::$type ]);
        }

        # tags
        $query = "select * from torrents_tags";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["GroupID"], $row["UserID"], $row["TagID"], Gazelle\Tags::$type ]);
        }

        /** torrents */

        $table = "torrents_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        # torrent groups
        $query = "
            select torrents.id as torrentId, torrents.userId as userId, torrents_group.id as groupId
            from torrents join torrents_group on torrents_group.id = torrents.groupId";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert ignore into {$table} (objectId, userId, contentId, contentType) values (?, ?, ?, ?)";
            $app->dbNew->do($query, [ $row["torrentId"], $row["userId"], $row["groupId"], Gazelle\TorrentGroups::$type ]);
        }

        /** users */

        $table = "users_links";
        $query = str_replace("{{ table }}", $table, $definition);
        $app->dbNew->do($query, []);

        /** wiki */

        # nothing to do
    }
}

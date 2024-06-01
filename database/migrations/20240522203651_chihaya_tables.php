<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChihayaTables extends AbstractMigration
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
        # https://github.com/anniemaybytes/chihaya/blob/master/database/schema.sql
        $app = Gazelle\App::go();

        # approved_clients
        $query = "
            create table approved_clients
            (
                id bigint unsigned default uuid_short() primary key,
                peer_id varchar(64) not null,
                description varchar(128) not null,
                archived tinyint(1) default 0 not null,
                created_at datetime default current_timestamp(),
                updated_at datetime on update current_timestamp(),
                deleted_at datetime
            );
        ";
        $app->dbNew->do($query, []);

        # migrate from xbt_client_whitelist
        $query = "select * from xbt_client_whitelist";
        $ref = $app->dbNew->multi($query, []);

        foreach ($ref as $row) {
            $query = "insert into approved_clients (peer_id, description) values (?, ?)";
            $app->dbNew->do($query, [ $row["peer_id"], $row["vstring"] ]);
        }

        /*
        $query = "
            create table approved_clients
            (
                id mediumint unsigned auto_increment primary key,
                peer_id varchar(42) not null,
                archived tinyint(1) default 0 not null
            );
        ";
        $app->dbNew->do($query, []);
        */

        /** */

        # mod_core
        $query = "
            create table mod_core
            (
                mod_option varchar(121) not null primary key,
                mod_setting int(12) not null
            );
        ";
        $app->dbNew->do($query, []);

        /** */

        # torrent_group_freeleech
        $query = "
            create table torrent_group_freeleech
            (
                id bigint unsigned default uuid_short() primary key,
                groupId bigint unsigned not null,
                type enum ('null') not null default 'null',
                downMultiplier float default 1 not null,
                upMultiplier float default 1 not null,
                constraint groupId unique (groupId, type)
            );
        ";
        $app->dbNew->do($query, []);

        /*
        $query = "
            create table torrent_group_freeleech
            (
                ID int(10) auto_increment primary key,
                GroupID int(10) not null,
                Type enum ('anime', 'music') not null,
                DownMultiplier float default 1 not null,
                UpMultiplier float default 1 not null,
                constraint GroupID unique (GroupID, Type)
            );
        ";
        $app->dbNew->do($query, []);
        */

        /** */

        /*
        # torrents
        $query = "
            create table torrents
            (
                ID int(10) auto_increment primary key,
                GroupID int(10) not null,
                TorrentType enum ('anime', 'music') not null,
                info_hash blob not null,
                Leechers int(6) default 0 not null,
                Seeders int(6) default 0 not null,
                last_action int default 0 not null,
                Snatched int unsigned default 0 not null,
                DownMultiplier float default 1 not null,
                UpMultiplier float default 1 not null,
                Status int default 0 not null,
                constraint InfoHash unique (info_hash (20))
            );
        ";
        $app->dbNew->do($query, []);
        */

        /** */

        # transfer_history
        $query = "
            create table transfer_history
            (
                uid bigint unsigned not null,
                fid bigint unsigned not null,
                uploaded bigint default 0 not null,
                downloaded bigint default 0 not null,
                seeding tinyint default 0 not null,
                seedtime int(30) default 0 not null,
                activetime int(30) default 0 not null,
                hnr tinyint default 0 not null,
                remaining bigint default 0 not null,
                active tinyint default 0 not null,
                starttime int default 0 not null,
                last_announce int default 0 not null,
                snatched int default 0 not null,
                snatched_time int default 0 not null,
                primary key (uid, fid)
            );
        ";
        $app->dbNew->do($query, []);

        /*
        $query = "
            create table transfer_history
            (
                uid int not null,
                fid int not null,
                uploaded bigint default 0 not null,
                downloaded bigint default 0 not null,
                seeding tinyint default 0 not null,
                seedtime int(30) default 0 not null,
                activetime int(30) default 0 not null,
                hnr tinyint default 0 not null,
                remaining bigint default 0 not null,
                active tinyint default 0 not null,
                starttime int default 0 not null,
                last_announce int default 0 not null,
                snatched int default 0 not null,
                snatched_time int default 0 not null,
                primary key (uid, fid)
            );
        ";
        $app->dbNew->do($query, []);
        */

        /** */

        # transfer_ips
        $query = "
            create table transfer_ips
            (
                last_announce int unsigned default 0 not null,
                starttime int unsigned default 0 not null,
                uid bigint unsigned not null,
                fid bigint unsigned not null,
                ip int unsigned not null,
                client_id mediumint unsigned not null,
                uploaded bigint unsigned default 0 not null,
                downloaded bigint unsigned default 0 not null,
                port smallint unsigned zerofill default 0 not null,
                primary key (uid, fid, ip, client_id)
            );
        ";
        $app->dbNew->do($query, []);

        /*
        $query = "
            create table transfer_ips
            (
                last_announce int unsigned default 0 not null,
                starttime int unsigned default 0 not null,
                uid int unsigned not null,
                fid int unsigned not null,
                ip int unsigned not null,
                client_id mediumint unsigned not null,
                uploaded bigint unsigned default 0 not null,
                downloaded bigint unsigned default 0 not null,
                port smallint unsigned zerofill default 0 not null,
                primary key (uid, fid, ip, client_id)
            );
        ";
        $app->dbNew->do($query, []);
        */

        /** */

        /*
        # users_main
        $query = "
            create table users_main
            (
                ID int unsigned auto_increment primary key,
                Uploaded bigint unsigned default 0 not null,
                Downloaded bigint unsigned default 0 not null,
                Enabled enum ('0', '1', '2') default '0' not null,
                torrent_pass char(32) not null,
                rawup bigint unsigned default 0 not null,
                rawdl bigint unsigned default 0 not null,
                DownMultiplier float default 1 not null,
                UpMultiplier float default 1 not null,
                DisableDownload tinyint(1) default 0 not null,
                TrackerHide tinyint(1) default 0 not null
            );
        ";
        $app->dbNew->do($query, []);
        */
    }
}

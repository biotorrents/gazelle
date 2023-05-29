<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChihayaSchema extends AbstractMigration
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

        /**
         * approved_clients
         *
         * create table approved_clients
         * (
         *   id       mediumint unsigned auto_increment primary key,
         *   peer_id  varchar(42)          null,
         *   archived tinyint(1) default 0 null
         * );
         */
        $table = $this->table("approved_clients");
        $table
            ->addColumn("uuid", "binary", [
                "length" => 16,
                "default" => Phinx\Util\Literal::from("unhex(replace(uuid(), '-', ''))"),
                "null" => false,
            ])

            ->addColumn("peer_id", "string", ["limit" => 64, "null" => true])
            ->addColumn("archived", "boolean", ["default" => false, "null" => true])
            ->addColumn("title", "string", ["limit" => 255, "default" => "", "null" => true])

            ->addColumn("created_at", "datetime", ["default" => "CURRENT_TIMESTAMP"])
            ->addColumn("updated_at", "datetime", ["null" => true, "update" => "CURRENT_TIMESTAMP"])
            ->addColumn("deleted_at", "datetime", ["null" => true])

            ->create();

        /**
         * mod_core
         *
         * create table mod_core
         * (
         *   mod_option  varchar(121)      not null primary key,
         *   mod_setting int(12) default 0 not null
         * );
         */
        $table = $this->table("mod_core");
        $table
            ->addColumn("uuid", "binary", [
                "length" => 16,
                "default" => Phinx\Util\Literal::from("unhex(replace(uuid(), '-', ''))"),
                "null" => false,
            ])

            ->addColumn("mod_option", "string", ["limit" => 128, "null" => false])
            ->addColumn("archived", "integer", ["default" => 0, "null" => false])

            ->addColumn("created_at", "datetime", ["default" => "CURRENT_TIMESTAMP"])
            ->addColumn("updated_at", "datetime", ["null" => true, "update" => "CURRENT_TIMESTAMP"])
            ->addColumn("deleted_at", "datetime", ["null" => true])

            ->addIndex(["mod_option"], ["unique" => true])

            ->create();

        /**
         * torrent_group_freeleech
         *
         * create table torrent_group_freeleech
         * (
         *   ID             int(10) auto_increment primary key,
         *   GroupID        int(10)                 default 0       not null,
         *   Type           enum ('anime', 'music') default 'anime' not null,
         *   DownMultiplier float                   default 1       not null,
         *   UpMultiplier   float                   default 1       not null,
         *   constraint GroupID unique (GroupID, Type)
         * );
         */
        $table = $this->table("torrent_group_freeleech");
        $table
            ->addColumn("uuid", "binary", [
                "length" => 16,
                "default" => Phinx\Util\Literal::from("unhex(replace(uuid(), '-', ''))"),
                "null" => false,
            ])

            ->addColumn("groupId", "integer", ["default" => 0, "null" => false])
            ->addColumn("type", "string", ["limit" => 32, "default" => "", "null" => false])
            ->addColumn("downMultiplier", "float", ["default" => 1, "null" => false])
            ->addColumn("upMultiplier", "float", ["default" => 1, "null" => false])

            ->addColumn("created_at", "datetime", ["default" => "CURRENT_TIMESTAMP"])
            ->addColumn("updated_at", "datetime", ["null" => true, "update" => "CURRENT_TIMESTAMP"])
            ->addColumn("deleted_at", "datetime", ["null" => true])

            ->addForeignKey("groupId", "torrents_group", ["id"], ["constraint" => "groupId"])

            ->create();

        /**
         * torrents
         *
         * create table torrents
         * (
         *   ID             int(10) auto_increment primary key,
         *   GroupID        int(10)                                 not null,
         *   TorrentType    enum ('anime', 'music') default 'anime' not null,
         *   info_hash      blob                                    not null,
         *   Leechers       int(6)                  default 0       not null,
         *   Seeders        int(6)                  default 0       not null,
         *   last_action    int                     default 0       not null,
         *   Snatched       int unsigned            default 0       not null,
         *   DownMultiplier float                   default 1       not null,
         *   UpMultiplier   float                   default 1       not null,
         *   Status         int                     default 0       not null,
         *   constraint InfoHash unique (info_hash)
         * );
         */
        $table = $this->table("torrents");
        $table
            ->addColumn("torrentType", "string", ["limit" => 32, "default" => "", "null" => false, "after" => "anonymous"])
            ->addColumn("downMultiplier", "float", ["default" => 1, "null" => false, "after" => "snatched"])
            ->addColumn("upMultiplier", "float", ["default" => 1, "null" => false, "after" => "downMultiplier"])
            ->addColumn("status", "integer", ["default" => 0, "null" => false, "after" => "upMultiplier"])

            # todo: add constraint

            ->update();

        /**
         * torrents_group
         *
         * create table torrents_group
         * (
         *   ID   int unsigned auto_increment primary key,
         *   Time int(10) default 0 not null
         * ) charset = utf8mb4;
         */
        $table = $this->table("torrents_group");
        $table
            ->addColumn("time", "integer", ["default" => 0, "null" => false, "after" => "timestamp"])

            #->renameColumn("timestamp", "time")
            #->changeColumn("time", "integer", ["default" => 0, "null" => false, "after" => "timestamp"])

            ->update();

        /**
         * transfer_history
         *
         * create table transfer_history
         * (
         *   uid           int     default 0 not null,
         *   fid           int     default 0 not null,
         *   uploaded      bigint  default 0 not null,
         *   downloaded    bigint  default 0 not null,
         *   seeding       tinyint default 0 not null,
         *   seedtime      int(30) default 0 not null,
         *   activetime    int(30) default 0 not null,
         *   hnr           tinyint default 0 not null,
         *   remaining     bigint  default 0 not null,
         *   active        tinyint default 0 not null,
         *   starttime     int     default 0 not null,
         *   last_announce int     default 0 not null,
         *   snatched      int     default 0 not null,
         *   snatched_time int     default 0 null,
         *   primary key (uid, fid)
         * );
         *
         * PRIMARY KEY (`uid`,`fid`),
         * KEY `active` (`active`),
         * KEY `seeding` (`seeding`,`active`),
         * KEY `hnr` (`hnr`),
         * KEY `ss` (`snatched_time`,`snatched`),
         * KEY `fid` (`fid`),
         * KEY `transfer` (`uploaded`,`downloaded`),
         * KEY `snatched` (`snatched`),
         * KEY `last_announce` (`active`,`last_announce`),
         * KEY `time` (`last_announce`,`seedtime`,`activetime`)
         */
        $table = $this->table("transfer_history");
        $table
            ->addColumn("uuid", "binary", [
                "length" => 16,
                "default" => Phinx\Util\Literal::from("unhex(replace(uuid(), '-', ''))"),
                "null" => false,
            ])

            ->addColumn("uid", "integer", ["default" => 0, "null" => false])
            ->addColumn("fid", "integer", ["default" => 0, "null" => false])
            ->addColumn("uploaded", "biginteger", ["default" => 0, "null" => false])
            ->addColumn("downloaded", "biginteger", ["default" => 0, "null" => false])
            ->addColumn("seeding", "boolean", ["default" => false, "null" => false])
            ->addColumn("seedTime", "biginteger", ["default" => 0, "null" => false])
            ->addColumn("activeTime", "biginteger", ["default" => 0, "null" => false])
            ->addColumn("hnr", "boolean", ["default" => false, "null" => false])
            ->addColumn("remaining", "biginteger", ["default" => 0, "null" => false])
            ->addColumn("active", "boolean", ["default" => false, "null" => false])
            ->addColumn("startTime", "integer", ["default" => 0, "null" => false])
            ->addColumn("last_announce", "integer", ["default" => 0, "null" => false])
            ->addColumn("snatched", "integer", ["default" => 0, "null" => false])
            ->addColumn("snatched_time", "integer", ["default" => 0, "null" => false])

            ->addColumn("created_at", "datetime", ["default" => "CURRENT_TIMESTAMP"])
            ->addColumn("updated_at", "datetime", ["null" => true, "update" => "CURRENT_TIMESTAMP"])
            ->addColumn("deleted_at", "datetime", ["null" => true])

            ->addIndex(["uid", "fid"])
            ->addIndex(["active"])
            ->addIndex(["seeding", "active"], ["name" => "seeding"])
            ->addIndex(["hnr"])
            ->addIndex(["snatched_time", "snatched"], ["name" => "ss"])
            ->addIndex(["fid"])
            ->addIndex(["uploaded", "downloaded"], ["name" => "transfer"])
            ->addIndex(["snatched"])
            ->addIndex(["active", "last_announce"], ["name" => "last_announce"])
            ->addIndex(["last_announce", "seedTime", "activeTime"], ["name" => "time"])

            ->create();

        /**
         * transfer_ips
         *
         * create table transfer_ips
         * (
         *   last_announce int unsigned       default 0 not null,
         *   starttime     int unsigned       default 0 not null,
         *   uid           int unsigned       default 0 not null,
         *   fid           int unsigned       default 0 not null,
         *   ip            int unsigned       default 0 not null,
         *   client_id     mediumint unsigned default 0 not null,
         *   uploaded      bigint unsigned    default 0 not null,
         *   downloaded    bigint unsigned    default 0 not null,
         *   port          smallint unsigned zerofill   null,
         *   primary key (uid, fid, ip, client_id)
         * );
         *
         * PRIMARY KEY (`uid`,`fid`,`ip`,`client_id`),
         * KEY `last_announce` (`last_announce`)
         */
        $table = $this->table("transfer_ips");
        $table
            ->addColumn("uuid", "binary", [
                "length" => 16,
                "default" => Phinx\Util\Literal::from("unhex(replace(uuid(), '-', ''))"),
                "null" => false,
            ])

            ->addColumn("last_announce", "integer", ["default" => 0, "null" => false])
            ->addColumn("startTime", "integer", ["default" => 0, "null" => false])
            ->addColumn("uid", "integer", ["default" => 0, "null" => false])
            ->addColumn("fid", "integer", ["default" => 0, "null" => false])
            ->addColumn("ip", "integer", ["default" => 0, "null" => false])
            ->addColumn("client_id", "smallinteger", ["default" => 0, "null" => false])
            ->addColumn("uploaded", "biginteger", ["default" => 0, "null" => false])
            ->addColumn("downloaded", "biginteger", ["default" => 0, "null" => false])
            ->addColumn("port", "smallinteger", ["default" => 0, "null" => false])

            ->addColumn("created_at", "datetime", ["default" => "CURRENT_TIMESTAMP"])
            ->addColumn("updated_at", "datetime", ["null" => true, "update" => "CURRENT_TIMESTAMP"])
            ->addColumn("deleted_at", "datetime", ["null" => true])

            ->addIndex(["uid", "fid", "ip", "client_id"])
            ->addIndex(["last_announce"])

            ->create();

        /**
         * users_main
         *
         * create table users_main
         * (
         *   ID              int unsigned auto_increment primary key,
         *   Uploaded        bigint unsigned      default 0   not null,
         *   Downloaded      bigint unsigned      default 0   not null,
         *   Enabled         enum ('0', '1', '2') default '0' not null,
         *   torrent_pass    char(32)                         not null,
         *   rawup           bigint unsigned                  not null,
         *   rawdl           bigint unsigned                  not null,
         *   DownMultiplier  float                default 1   not null,
         *   UpMultiplier    float                default 1   not null,
         *   DisableDownload tinyint(1)           default 0   not null,
         *   TrackerHide     tinyint(1)           default 0   not null
         * );
         */
        $table = $this->table("users_main");
        $table
            ->addColumn("rawup", "biginteger", ["null" => false, "after" => "downloaded"])
            ->addColumn("rawdl", "biginteger", ["null" => false, "after" => "rawup"])

            ->addColumn("downMultiplier", "float", ["default" => 1, "null" => false, "after" => "rawdl"])
            ->addColumn("upMultiplier", "float", ["default" => 1, "null" => false, "after" => "downMultiplier"])

            ->addColumn("disableDownload", "boolean", ["default" => false, "null" => false, "after" => "upMultiplier"])
            ->addColumn("trackerHide", "boolean", ["default" => false, "null" => false, "after" => "disableDownload"])

            ->update();
    }
}

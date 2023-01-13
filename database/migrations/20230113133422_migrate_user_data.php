<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MigrateUserData extends AbstractMigration
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
        $app = App::go();

        # drop the existing table
        $query = "drop table if exists users";
        $app->dbNew->do($query, []);

        # create the delight-im/auth table
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `verified` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `resettable` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `roles_mask` int(10) unsigned NOT NULL DEFAULT '0',
  `registered` int(10) unsigned NOT NULL,
  `last_login` int(10) unsigned DEFAULT NULL,
  `force_logout` mediumint(7) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $app->dbNew->do($query, []);

        # get the user data
        $query = "
            select email, passHash, username, unix_timestamp(lastLogin) as lastLogin, unix_timestamp(users_info.joinDate) as joinDate
            from users_main left join users_info on users_info.userId = users_main.id
            order by users_main.id asc
        ";
        $ref = $app->dbNew->multi($query, []);

        # loop through it
        foreach ($ref as $row) {
            # make a delight-im/auth payload
            $data = [
                "email" => $row["email"] ?? null,
                "password" => $row["passHash"] ?? null,
                "username" => $row["username"] ?? null,
                "verified" => 1,
                "registered" => $row["joinDate"] ?? time(),
                "last_login" => $row["lastLogin"] ?? time(),
            ];

            # skip invalid (these must exist)
            if (!$data["email"] || !$data["password"] || !$data["username"]) {
                continue;
            }

            # insert the "new" user
            $query = "
                insert into users (email, password, username, verified, registered, last_login)
                values (:email, :password, :username, :verified, :registered, :last_login)
            ";
            $app->dbNew->do($query, $data);
        }
    } # function
} # class


/*
# debug
count($ref);
foreach ($ref as $row) {
    !d($row);
}
*/

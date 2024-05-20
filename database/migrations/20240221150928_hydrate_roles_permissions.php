<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class HydrateRolesPermissions extends AbstractMigration
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

        # it's the same query for all roles
        $query = "insert into roles_permissions (id, machineName, friendlyName, permissionsList) values (?, ?, ?, ?)";

        /** */

        # guest
        $app->dbNew->do($query, [ 10, "guest", "Guest", json_encode([]) ]);

        # user
        $app->dbNew->do($query, [ 20, "user", "User", json_encode([]) ]);

        # member
        $app->dbNew->do($query, [ 30, "member", "Member", json_encode([]) ]);

        # powerUser
        $app->dbNew->do($query, [ 40, "powerUser", "Power User", json_encode([]) ]);

        # elite
        $app->dbNew->do($query, [ 50, "elite", "Elite", json_encode([]) ]);

        # torrentMaster
        $app->dbNew->do($query, [ 60, "torrentMaster", "Torrent Master", json_encode([]) ]);

        # powerMaster
        $app->dbNew->do($query, [ 70, "powerMaster", "Power Master", json_encode([]) ]);

        # eliteMaster
        $app->dbNew->do($query, [ 80, "eliteMaster", "Elite Master", json_encode([]) ]);

        # legend
        $app->dbNew->do($query, [ 90, "legend", "Legend", json_encode([]) ]);

        # creator
        $app->dbNew->do($query, [ 100, "creator", "Creator", json_encode([]) ]);

        # donor
        $app->dbNew->do($query, [ 110, "donor", "Donor", json_encode([]) ]);

        # vip
        $app->dbNew->do($query, [ 120, "vip", "VIP", json_encode([]) ]);

        # techSupport
        $app->dbNew->do($query, [ 130, "techSupport", "Tech Support", json_encode([]) ]);

        # lesserModerator
        $app->dbNew->do($query, [ 140, "lesserModerator", "Lesser Moderator", json_encode([]) ]);

        # greaterModerator
        $app->dbNew->do($query, [ 150, "greaterModerator", "Greater Moderator", json_encode([]) ]);

        # administrator
        $app->dbNew->do($query, [ 160, "administrator", "Administrator", json_encode([]) ]);

        # developer
        $app->dbNew->do($query, [ 170, "developer", "Developer", json_encode([]) ]);

        # sysop
        $app->dbNew->do($query, [ 180, "sysop", "Sysop", json_encode([]) ]);

        /** */

        # now, update the old permissionId's to the new ones
        $updatePermissionIds = [
            2 => 20, # user
            3 => 30, # member
            4 => 40, # powerUser
            5 => 50, # elite
            11 => 140, # lesserModerator
            15 => 180, # sysop
            19 => 100, # creator
            20 => 110, # donor
            21 => 150, # greaterModerator
        ];

        $query = "update users_main set permissionId = ? where permissionId = ?";
        foreach ($updatePermissionIds as $oldId => $newId) {
            $app->dbNew->do($query, [$newId, $oldId]);
        }
    }
}

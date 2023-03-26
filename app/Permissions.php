<?php

#declare(strict_types=1);


/**
 * Permissions
 */

class Permissions
{
    # delight-im/auth
    public $library = null;


    /**
     * listRoles
     *
     * Lists all the site roles, e.g.,
     * [
     *   "sysop",
     *   "moderator",
     *   "user",
     * ]
     */
    public static function listRoles()
    {
        $app = App::go();

        # this table should be called "roles" tbh
        $query = "select id, name from permissions";
        $ref = $app->dbNew->multi($query, []);

        $roles = array_combine(
            array_column($ref, "id"),
            array_column($ref, "name")
        );

        return $roles;

        /** */

        # todo: migrate over to delight-im/auth
        return Delight\Auth\Role::getMap();

        /*
        $roles = [
            1 => "ADMIN",
            2 => "AUTHOR",
            4 => "COLLABORATOR",
            8 => "CONSULTANT",
            16 => "CONSUMER",
            32 => "CONTRIBUTOR",
            64 => "COORDINATOR",
            128 => "CREATOR",
            256 => "DEVELOPER",
            512 => "DIRECTOR",
            1024 => "EDITOR",
            2048 => "EMPLOYEE",
            4096 => "MAINTAINER",
            8192 => "MANAGER",
            16384 => "MODERATOR",
            32768 => "PUBLISHER",
            65536 => "REVIEWER",
            131072 => "SUBSCRIBER",
            262144 => "SUPER_ADMIN",
            524288 => "SUPER_EDITOR",
            1048576 => "SUPER_MODERATOR",
            2097152 => "TRANSLATOR",
        ];
        */
    }


    /**
     * getUserRole
     *
     * Gets the current user's permissions info.
     */
    public static function getUserRole()
    {
        $app = App::go();

        $query = "select id, name, values from permissions where id = ?";
        $row = $app->dbNew->row($query, [  $app->userNew->extra["PermissionID"] ]);

        return $row;
    }


    /**
     * listPermissions
     *
     * Lists all the site permissions.
     */
    public static function listPermissions()
    {
        # in progress: simple crud natural language permissions
        $newPermissions = [
            # torrents
            "create torrents",
            "read torrents",
            "update own torrents",
            "update any torrents",
            "delete own torrents",
            "delete any torrents",

            # collections
            "create collections",
            "read collections",
            "update own collections",
            "update any collections",
            "delete own collections",
            "delete any collections",

            # requests
            "create requests",
            "read requests",
            "update own requests",
            "update any requests",
            "delete own requests",
            "delete any requests",

            # posts
            "create posts",
            "read posts",
            "update own posts",
            "update any posts",
            "delete own posts",
            "delete any posts",

            # user profiles
            "create user profiles",
            "read user profiles",
            "update own user profiles",
            "update any user profiles",
            "delete own user profiles",
            "delete any user profiles",

            # todo: other items and misc admin permissions
        ];

        # from bootstrap/utilities.php
        $oldPermissions = [
            "admin_advanced_user_search" => "Can access advanced user search",
            "admin_clear_cache" => "Can clear cached",
            "admin_donor_log" => "Can view the donor log",
            "admin_login_watch" => "Can manage login watch",
            "admin_manage_blog" => "Can manage the site blog",
            "admin_manage_fls" => "Can manage FLS",
            "admin_manage_forums" => "Can manage forums (add/edit/delete)",
            "admin_manage_ipbans" => "Can manage IP bans",
            "admin_manage_news" => "Can manage site news",
            "admin_manage_permissions" => "Can edit permission classes/user permissions",
            "admin_manage_polls" => "Can manage polls",
            "admin_manage_wiki" => "Can manage wiki access",
            "admin_reports" => "Can access reports system",
            "admin_schedule" => "Can run the site schedule",
            "admin_whitelist" => "Can manage the list of allowed clients",
            "artist_edit_vanityhouse" => "Can mark artists as part of Vanity House",
            "edit_unknowns" => "Can edit unknown release information",
            "forums_polls_create" => "Can create polls in the forums",
            "forums_polls_moderate" => "Can feature and close polls",
            "project_team" => "Is part of the project team",
            "screenshots_add" => "Can add screenshots to any torrent and delete their own screenshots",
            "screenshots_delete" => "Can delete any screenshot from any torrent",
            "site_admin_forums" => "Forum administrator access",
            "site_advanced_search" => "Advanced search access",
            "site_advanced_top10" => "Advanced Top 10 access",
            "site_can_invite_always" => "Can invite past user limit",
            "site_collages_create" => "Collage create access",
            "site_collages_delete" => "Collage delete access",
            "site_collages_manage" => "Collage manage access",
            "site_collages_personal" => "Can have a personal collage",
            "site_collages_recover" => "Can recover 'deleted' collages",
            "site_collages_renamepersonal" => "Can rename own personal collages",
            "site_collages_subscribe" => "Collage subscription access",
            "site_debug" => "Developer access",
            "site_delete_artist" => "Can delete artists (must be able to delete torrents+requests)",
            "site_delete_tag" => "Can delete tags",
            "site_edit_wiki" => "Wiki edit access",
            "site_forums_double_post" => "Can double post in the forums",
            "site_leech" => "Can leech (Does this work?)",
            "site_make_bookmarks" => "Bookmarks access",
            "site_manage_recommendations" => "Recommendations management access",
            "site_moderate_forums" => "Forum moderation access",
            "site_moderate_requests" => "Request moderation access",
            "site_proxy_images" => "Image proxy & anti-canary",
            "site_ratio_watch_immunity" => "Immune from being put on ratio watch",
            "site_recommend_own" => "Can recommend own torrents",
            "site_search_many" => "Can go past low limit of search results",
            "site_send_unlimited_invites" => "Unlimited invites",
            "site_submit_requests" => "Request create access",
            "site_tag_aliases_read" => "Can view the list of tag aliases",
            "site_top10" => "Top 10 access",
            "site_torrents_notify" => "Notifications access",
            "site_upload" => "Upload torrent access",
            "site_view_flow" => "Can view stats and data pools",
            "site_view_full_log" => "Can view old log entries",
            "site_view_torrent_snatchlist" => "Can view torrent snatch lists",
            "site_vote" => "Request vote access",
            "torrents_add_artist" => "Can add artists to any group",
            "torrents_delete" => "Can delete torrents",
            "torrents_delete_fast" => "Can delete more than 3 torrents at a time",
            "torrents_edit" => "Can edit any torrent",
            "torrents_edit_vanityhouse" => "Can mark groups as part of Vanity House",
            "torrents_fix_ghosts" => "Can fix 'ghost' groups on artist pages",
            "torrents_freeleech" => "Can make torrents freeleech",
            "torrents_search_fast" => "Rapid search (for scripts)",
            "users_delete_users" => "Can delete users",
            "users_disable_any" => "Can disable any users' rights",
            "users_disable_posts" => "Can disable users' posting privileges",
            "users_disable_users" => "Can disable users",
            "users_edit_avatars" => "Can edit avatars",
            "users_edit_invites" => "Can edit invite numbers and cancel sent invites",
            "users_edit_own_ratio" => "Can edit own upload/download amounts",
            "users_edit_password" => "Can change passwords",
            "users_edit_profiles" => "Can edit anyone's profile",
            "users_edit_ratio" => "Can edit anyone's upload/download amounts",
            "users_edit_reset_keys" => "Can reset passkey/authkey",
            "users_edit_titles" => "Can edit titles",
            "users_edit_usernames" => "Can edit usernames",
            "users_edit_watch_hours" => "Can edit contrib watch hours",
            "users_give_donor" => "Can give donor access",
            "users_invite_notes" => "Can add a staff note when inviting someone",
            "users_logout" => "Can log users out (old?)",
            "users_make_invisible" => "Can make users invisible",
            "users_mod" => "Basic moderator tools",
            "users_override_paranoia" => "Can override paranoia",
            "users_promote_below" => "Can promote users to below current level",
            "users_promote_to" => "Can promote users up to current level",
            "users_reset_own_keys" => "Can reset own passkey/authkey",
            "users_view_email" => "Can view email addresses",
            "users_view_friends" => "Can view anyone's friends",
            "users_view_invites" => "Can view who user has invited",
            "users_view_ips" => "Can view IP addresses",
            "users_view_keys" => "Can view passkeys",
            "users_view_seedleech" => "Can view what a user is seeding or leeching",
            "users_view_uploaded" => "Can view a user's uploads, regardless of privacy level",
            "users_warn" => "Can warn users",
            "zip_downloader" => "Download multiple torrents at once",
        ];

        return $oldPermissions;
    }


    /**
     * can
     *
     * Checks if a user can do something.
     */
    public static function can(string $permission): bool
    {
        $userRole = self::getUserRole();

        # try json first
        $rolePermissions = json_decode($userRole["values"], true);
        if ($rolePermissions) {
            return in_array($permission, $rolePermissions);
        }

        # default deny
        return false;

        /*
        # try to unserialize
        $rolePermissions = unserialize($userRole["values"]);
        if ($rolePermissions) {
            return in_array($permission, array_keys($rolePermissions));
        }
        */

        /*
        # try string search
        $rolePermissions = $userRole["values"];
        if ($rolePermissions) {
            return Illuminate\Support\Str::contains($rolePermissions, $permission);
        }
        */
    }


    /**
     * givePermissionTo
     *
     * Grants a permission to a role.
     *
     * @see https://spatie.be/docs/laravel-permission/v5/basic-usage/basic-usage
     */
    public static function givePermissionTo(string $permission)
    {
    }


    /**
     * revokePermissionTo
     *
     * Revokes a permission from a role.
     *
     * @see https://spatie.be/docs/laravel-permission/v5/basic-usage/basic-usage
     */
    public static function revokePermissionTo(string $permission)
    {
    }


    /**
     * assignRole
     *
     * Grants a role to a user.
     *
     * @see https://github.com/delight-im/PHP-Auth#assigning-roles-to-users
     */
    public static function assignRole(string $role)
    {
    }


    /**
     * removeRole
     *
     * Revokes a role from a user.
     *
     * @see https://github.com/delight-im/PHP-Auth#taking-roles-away-from-users
     */
    public static function removeRole(string $role)
    {
    }


    /**
     * createRole
     *
     * Creates a role.
     */
    public static function createRole(string $roleName, array $permissions, bool $staffRole = false)
    {
        $app = App::go();

        $query = "replace into permissions (name, values, displayStaff) values (?, ?, ?)";
        $app->dbNew->do($query, [$roleName, json_encode($permissions), $staffRole]);
    }


    /**
     * readRole
     *
     * Reads a role.
     */
    public static function readRole(string $roleName)
    {
        $app = App::go();

        $query = "select name, values, displayStaff from permissions where name = ?";
        $row = $app->dbNew->row($query, [$roleName]);

        return $row;
    }


    /**
     * updateRole
     *
     * Updates a role.
     */
    public static function updateRole(string $roleName, array $permissions, bool $staffRole = false)
    {
        return self::createRole($roleName, $permissions, $staffRole);
    }


    /**
     * deleteRole
     *
     * Deletes a role.
     */
    public static function deleteRole(string $roleName)
    {
        $app = App::go();

        $query = "delete from permissions where name = ?";
        $app->dbNew->do($query, [$roleName]);
    }


    /** LEGACY CODE */


    /**
     * Check to see if a user has the permission to perform an action
     * This is called by check_perms in util.php, for convenience.
     *
     * @param string PermissionName
     * @param string $MinClass Return false if the user's class level is below this.
     */
    public static function check_perms($PermissionName, $MinClass = 0)
    {
        $app = App::go();

        $app->userOld['EffectiveClass'] ??= 1000;
        if ($app->userOld['EffectiveClass'] >= 1000) {
            return true;
        } // Sysops can do anything

        if ($app->userOld['EffectiveClass'] < $MinClass) {
            return false;
        } // MinClass failure

        return $app->userOld['Permissions'][$PermissionName] ?? false; // Return actual permission
    }


    /**
     * Gets the permissions associated with a certain permissionid
     *
     * @param int $PermissionID the kind of permissions to fetch
     * @return array permissions
     */
    public static function get_permissions($PermissionID)
    {
        $app = App::go();

        $Permission = $app->cacheOld->get_value("perm_$PermissionID");
        if (empty($Permission)) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT Level AS Class, `Values` AS Permissions, Secondary, PermittedForums
            FROM permissions
              WHERE ID = '$PermissionID'");

            $Permission = $app->dbOld->next_record(MYSQLI_ASSOC, ['Permissions']);
            $app->dbOld->set_query_id($QueryID);
            $Permission['Permissions'] = unserialize($Permission['Permissions']);
            $app->cacheOld->cache_value("perm_$PermissionID", $Permission, 2592000);
        }
        return $Permission;
    }


    /**
     * Get a user's permissions.
     *
     * @param $UserID
     * @param array|false $CustomPermissions
     *  Pass in the user's custom permissions if you already have them.
     *  Leave false if you don't have their permissions. The function will fetch them.
     * @return array Mapping of PermissionName=>bool/int
     */
    public static function get_permissions_for_user($UserID, $CustomPermissions = false)
    {
        $app = App::go();

        $UserInfo = User::user_info($UserID);

        // Fetch custom permissions if they weren't passed in.
        if ($CustomPermissions === false) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query('
            SELECT CustomPermissions
            FROM users_main
              WHERE ID = ' . (int)$UserID);

            list($CustomPermissions) = $app->dbOld->next_record(MYSQLI_NUM, false);
            $app->dbOld->set_query_id($QueryID);
        }

        if (!empty($CustomPermissions) && !is_array($CustomPermissions)) {
            $CustomPermissions = unserialize($CustomPermissions);
        }

        $Permissions = self::get_permissions($UserInfo['PermissionID']);

        // Manage 'special' inherited permissions
        $BonusPerms = [];
        $BonusCollages = 0;

        foreach ($UserInfo['ExtraClasses'] as $PermID => $Value) {
            $ClassPerms = self::get_permissions($PermID);
            $BonusCollages += $ClassPerms['Permissions']['MaxCollages'];
            unset($ClassPerms['Permissions']['MaxCollages']);
            $BonusPerms = array_merge($BonusPerms, $ClassPerms['Permissions']);
        }

        if (empty($CustomPermissions)) {
            $CustomPermissions = [];
        }

        $MaxCollages = ($Permissions['Permissions']['MaxCollages'] ?? 0) + $BonusCollages;
        if (isset($CustomPermissions['MaxCollages'])) {
            $MaxCollages += $CustomPermissions['MaxCollages'];
            unset($CustomPermissions['MaxCollages']);
        }
        $Permissions['Permissions']['MaxCollages'] = $MaxCollages;

        // Combine the permissions
        return array_merge(
            $Permissions['Permissions'],
            $BonusPerms,
            $CustomPermissions
        );
    }


    /**
     * is_mod
     */
    public static function is_mod($UserID)
    {
        return self::get_permissions_for_user($UserID)['users_mod'] ?? false;
    }
}

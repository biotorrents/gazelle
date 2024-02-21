<?php

declare(strict_types=1);


/**
 * Gazelle\Roles
 *
 * This should be two classes, Roles and Permissions.
 * Maybe even call them Dates and Persimmons.
 */

namespace Gazelle;

class Roles extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?int $id = null; # primary key
    public string $type = "roles_permissions"; # database table
    public ?RecursiveCollection $attributes = null;

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "machineName" => "machineName",
        "friendlyName" => "friendlyName",
        "permissionsList" => "permissionsList",
        "isPrimaryRole" => "isPrimaryRole",
        "isSecondaryRole" => "isSecondaryRole",
        "isStaffRole" => "isStaffRole",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];

    # cache settings
    private string $cachePrefix = "roles:";
    private string $cacheDuration = "1 hour";

    # role map
    public array $roles = [
        # unauthenticated
        10 => "guest",

        # user class promotions
        20 => "user",
        30 => "member",
        40 => "powerUser",
        50 => "elite",
        60 => "torrentMaster",
        70 => "powerMaster",
        80 => "eliteMaster",
        90 => "legend",

        # special user roles (isSecondaryRole)
        100 => "creator",
        110 => "donor",
        120 => "vip",

        # staff roles, increasing power (isStaffRole)
        130 => "techSupport",
        140 => "lesserModerator",
        150 => "greaterModerator",
        160 => "administrator",
        170 => "developer",
        180 => "sysop",
    ];

    # in progress: simple crud natural language permissions
    # e.g., if $app->user->can("update any torrents") { ... }
    public array $permissions = [
        # torrents
        "create torrents" => "Can create torrents",
        "read torrents" => "Can read torrents",
        "update own torrents" => "Can update own torrents",
        "update any torrents" => "Can update any torrents",
        "delete own torrents" => "Can delete own torrents",
        "delete any torrents" => "Can delete any torrents",

        # torrent groups
        "create torrent groups" => "Can create torrent groups",
        "read torrent groups" => "Can read torrent groups",
        "update own torrent groups" => "Can update own torrent groups",
        "update any torrent groups" => "Can update any torrent groups",
        "delete own torrent groups" => "Can delete own torrent groups",
        "delete any torrent groups" => "Can delete any torrent groups",

        # tags
        "create tags" => "Can create tags",
        "read tags" => "Can read tags",
        "update own tags" => "Can update own tags",
        "update any tags" => "Can update any tags",
        "delete own tags" => "Can delete own tags",
        "delete any tags" => "Can delete any tags",

        /** */

        # collages
        "create collages" => "Can create collages",
        "read collages" => "Can read collages",
        "update own collages" => "Can update own collages",
        "update any collages" => "Can update any collages",
        "delete own collages" => "Can delete own collages",
        "delete any collages" => "Can delete any collages",

        # creators
        "create creators" => "Can create creators",
        "read creators" => "Can read creators",
        "update own creators" => "Can update own creators",
        "update any creators" => "Can update any creators",
        "delete own creators" => "Can delete own creators",
        "delete any creators" => "Can delete any creators",

        # requests
        "create requests" => "Can create requests",
        "read requests" => "Can read requests",
        "update own requests" => "Can update own requests",
        "update any requests" => "Can update any requests",
        "delete own requests" => "Can delete own requests",
        "delete any requests" => "Can delete any requests",

        /** */

        # conversations
        "create conversations" => "Can create conversations",
        "read conversations" => "Can read conversations",
        "update own conversations" => "Can update own conversations",
        "update any conversations" => "Can update any conversations",
        "delete own conversations" => "Can delete own conversations",
        "delete any conversations" => "Can delete any conversations",

        # messages
        "create messages" => "Can create messages",
        "read messages" => "Can read messages",
        "update own messages" => "Can update own messages",
        "update any messages" => "Can update any messages",
        "delete own messages" => "Can delete own messages",
        "delete any messages" => "Can delete any messages",

        # polls
        "create polls" => "Can create polls",
        "read polls" => "Can read polls",
        "update own polls" => "Can update own polls",
        "update any polls" => "Can update any polls",
        "delete own polls" => "Can delete own polls",
        "delete any polls" => "Can delete any polls",

        # user profiles
        "create user profiles" => "Can create user profiles",
        "read user profiles" => "Can read user profiles",
        "update own user profiles" => "Can update own user profiles",
        "update any user profiles" => "Can update any user profiles",
        "delete own user profiles" => "Can delete own user profiles",
        "delete any user profiles" => "Can delete any user profiles",

        /** */

        /*
        # rules
        "create rules" => "Can create rules",
        "read rules" => "Can read rules",
        "update own rules" => "Can update own rules",
        "update any rules" => "Can update any rules",
        "delete own rules" => "Can delete own rules",
        "delete any rules" => "Can delete any rules",
        */

        # wiki articles
        "create wiki articles" => "Can create wiki articles",
        "read wiki articles" => "Can read wiki articles",
        "update own wiki articles" => "Can update own wiki articles",
        "update any wiki articles" => "Can update any wiki articles",
        "delete own wiki articles" => "Can delete own wiki articles",
        "delete any wiki articles" => "Can delete any wiki articles",

        # admin tools (toolbox) individual page access
        # todo: go through all the admin tools and fix them
        "access toolbox" => "Can access the admin tools page",
        "access client whitelist" => "Can access the client whitelist page",
        "access permissions manager" => "Can access the permissions manager page",
        "access database key" => "Can access the database key page",
        "access auto-enable requests" => "Can access the auto-enable requests page",
        "access login watch" => "Can access the login watch page",
        "access service stats" => "Can access the service stats page",
        "access miscellaneous values" => "Can access the miscellaneous values page",
        "access tracker information" => "Can access the tracker information page",
        "access collage recovery" => "Can access the collage recovery page",
        "access freeleech token manager" => "Can access the freeleech token manager page",
        "access multiple freeleech" => "Can access the multiple freeleech page",
        "access tag aliases" => "Can access the tag aliases page",
        "access batch tag editor" => "Can access the batch tag editor page",
        "access official tags manager" => "Can access the official tags manager page",
        "access sitewide freeleech manager" => "Can access the sitewide freeleech manager page",
        "access global notifications" => "Can access the global notifications page",
        "access mass pm" => "Can access the mass PM page",
        "access news posts" => "Can access the news posts page",
        "access email blacklist" => "Can access the email blacklist page",
        "access ip address bans" => "Can access the IP address bans page",
        "access manipulate invite tree" => "Can access the manipulate invite tree page",
        "access invite pool" => "Can access the invite pool page",
        "access registration log" => "Can access the registration log page",
        "access upscale pool" => "Can access the upscale pool page",
    ];


    # in progress: simple crud natural language permissions
    # e.g., if $app->user->can("update any torrents") { ... }
    public array $altPermissions = [
        # torrents
        "torrents" => [
            "create" => "Can create torrents",
            "read" => "Can read torrents",
            "updateOwn" => "Can update own torrents",
            "updateAny" => "Can update any torrents",
            "deleteOwn" => "Can delete own torrents",
            "deleteAny" => "Can delete any torrents",
        ],

        # torrent groups
        "torrentGroups" => [
            "create" => "Can create torrent groups",
            "read" => "Can read torrent groups",
            "updateOwn" => "Can update own torrent groups",
            "updateAny" => "Can update any torrent groups",
            "deleteOwn" => "Can delete own torrent groups",
            "deleteAny" => "Can delete any torrent groups",
        ],

        # tags
        "tags" => [
            "create" => "Can create tags",
            "read" => "Can read tags",
            "updateOwn" => "Can update own tags",
            "updateAny" => "Can update any tags",
            "deleteOwn" => "Can delete own tags",
            "deleteAny" => "Can delete any tags",
        ],

        # collages
        "collages" => [
            "create" => "Can create collages",
            "read" => "Can read collages",
            "updateOwn" => "Can update own collages",
            "updateAny" => "Can update any collages",
            "deleteOwn" => "Can delete own collages",
            "deleteAny" => "Can delete any collages",
        ],

        # creators
        "creators" => [
            "create" => "Can create creators",
            "read" => "Can read creators",
            "updateOwn" => "Can update own creators",
            "updateAny" => "Can update any creators",
            "deleteOwn" => "Can delete own creators",
            "deleteAny" => "Can delete any creators",
        ],

        # requests
        "requests" => [
            "create" => "Can create requests",
            "read" => "Can read requests",
            "updateOwn" => "Can update own requests",
            "updateAny" => "Can update any requests",
            "deleteOwn" => "Can delete own requests",
            "deleteAny" => "Can delete any requests",
        ],

        # conversations
        "conversations" => [
            "create" => "Can create conversations",
            "read" => "Can read conversations",
            "updateOwn" => "Can update own conversations",
            "updateAny" => "Can update any conversations",
            "deleteOwn" => "Can delete own conversations",
            "deleteAny" => "Can delete any conversations",
        ],

        # messages
        "messages" => [
            "create" => "Can create messages",
            "read" => "Can read messages",
            "updateOwn" => "Can update own messages",
            "updateAny" => "Can update any messages",
            "deleteOwn" => "Can delete own messages",
            "deleteAny" => "Can delete any messages",
        ],

        # polls
        "polls" => [
            "create" => "Can create polls",
            "read" => "Can read polls",
            "updateOwn" => "Can update own polls",
            "updateAny" => "Can update any polls",
            "deleteOwn" => "Can delete own polls",
            "deleteAny" => "Can delete any polls",
        ],

        # user profiles
        "userProfiles" => [
            "create" => "Can create user profiles",
            "read" => "Can read user profiles",
            "updateOwn" => "Can update own user profiles",
            "updateAny" => "Can update any user profiles",
            "deleteOwn" => "Can delete own user profiles",
            "deleteAny" => "Can delete any user profiles",
        ],

        # wiki
        "wiki" => [
            "create" => "Can create wiki articles",
            "read" => "Can read wiki articles",
            "updateOwn" => "Can update own wiki articles",
            "updateAny" => "Can update any wiki articles",
            "deleteOwn" => "Can delete own wiki articles",
            "deleteAny" => "Can delete any wiki articles",
        ],

        # toolbox
        "toolbox" => [
            "access" => "Can access the admin tools page",
            "clientWhitelist" => "Can access the client whitelist page",
            "permissionsManager" => "Can access the permissions manager page",
            "databaseKey" => "Can access the database key page",
            "autoEnableRequests" => "Can access the auto-enable requests page",
            "loginWatch" => "Can access the login watch page",
            "serviceStats" => "Can access the service stats page",
            "miscellaneousValues" => "Can access the miscellaneous values page",
            "trackerInformation" => "Can access the tracker information page",
            "collageRecovery" => "Can access the collage recovery page",
            "freeleechTokenManager" => "Can access the freeleech token manager page",
            "multipleFreeleech" => "Can access the multiple freeleech page",
            "tagAliases" => "Can access the tag aliases page",
            "batchTagEditor" => "Can access the batch tag editor page",
            "officialTagsManager" => "Can access the official tags manager page",
            "sitewideFreeleechManager" => "Can access the sitewide freeleech manager page",
            "globalNotifications" => "Can access the global notifications page",
            "massPm" => "Can access the mass PM page",
            "newsPosts" => "Can access the news posts page",
            "emailBlacklist" => "Can access the email blacklist page",
            "ipAddressBans" => "Can access the IP address bans page",
            "manipulateInviteTree" => "Can access the manipulate invite tree page",
            "invitePool" => "Can access the invite pool page",
            "registrationLog" => "Can access the registration log page",
            "upscalePool" => "Can access the upscale pool page",
        ],
    ];


    /**
     * read
     *
     * Decodes the permissions JSON and adds extra attributes.
     *
     * @param int|string $identifier
     * @return void
     */
    public function read(int|string $identifier): void
    {
        $app = App::go();

        # normal read
        parent::read($identifier);

        # decode the permissions
        $this->attributes->permissionsList = json_decode($this->attributes->permissionsList, true);

        # get the user count
        $query = "select count(userId) from users_main where permissionId = ?";
        $this->attributes->userCount = $app->dbNew->single($query, [$this->id]);
    }


    /**
     * getAllRoles
     *
     * Returns an array of Roles objects.
     *
     * @return array
     */
    public function getAllRoles(): array
    {
        $app = App::go();

        $query = "select id from roles_permissions";
        $ref = $app->dbNew->column($query, []);

        $roles = [];
        foreach ($ref as $id) {
            $roles[] = new self($id);
        }

        return $roles;
    }


    /**
     * getAllPermissions
     *
     * Returns an array of permissions.
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        return $this->altPermissions;
    }


    /**
     * parsePermissionSections
     *
     * Parses the permissions into sections.
     * Array keys are used as section headers.
     *
     * @return array
     */
    public function parsePermissionSections(): array
    {
        return $this->altPermissions;

        /** */

        $app = App::go();

        # return cached if available
        $cacheKey = $this->cachePrefix . __FUNCTION__;
        $cacheHit = $app->cache->get($cacheKey);

        if ($cacheHit) {
            return $cacheHit;
        }

        # the section strings to look for
        $torrentsGroupsTags = ["torrents", "torrent groups", "tags"];
        $collagesCreatorsRequests = ["collages", "creators", "requests"];
        $socialUserGeneratedContent = ["conversations", "messages", "polls", "user profiles"];
        $wikiArticles = ["wiki articles"];

        $sections = [];
        foreach ($this->permissions as $key => $value) {
            # torrents, groups, and tags
            foreach ($torrentsGroupsTags as $section) {
                if (str_contains($key, $section)) {
                    $sections["Torrents, groups, and tags"][$key] = $value;
                }
            }

            # collages, creators, and requests
            foreach ($collagesCreatorsRequests as $section) {
                if (str_contains($key, $section)) {
                    $sections["Collages, creators, and requests"][$key] = $value;
                }
            }

            # social user-generated content
            foreach ($socialUserGeneratedContent as $section) {
                if (str_contains($key, $section)) {
                    $sections["Social user-generated content"][$key] = $value;
                }
            }

            # wiki articles and site documentation
            if (str_contains($key, "wiki articles")) {
                $sections["Wiki articles and site documentation"][$key] = $value;
            }

            # admin tools
            if (str_contains($key, "access")) {
                $sections["Admin tools"][$key] = $value;
            }
        } # foreach

        $app->cache->set($cacheKey, $sections, $this->cacheDuration);
        return $sections;
    }


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
        $row = $app->dbNew->row($query, [  $app->user->extra["PermissionID"] ]);

        return $row;
    }


    /**
     * listPermissions
     *
     * Lists all the site permissions.
     */
    public static function listPermissions()
    {
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
    public static function givePermissionTo(string $permission) {}


    /**
     * revokePermissionTo
     *
     * Revokes a permission from a role.
     *
     * @see https://spatie.be/docs/laravel-permission/v5/basic-usage/basic-usage
     */
    public static function revokePermissionTo(string $permission) {}


    /**
     * assignRole
     *
     * Grants a role to a user.
     *
     * @see https://github.com/delight-im/PHP-Auth#assigning-roles-to-users
     */
    public static function assignRole(string $role) {}


    /**
     * removeRole
     *
     * Revokes a role from a user.
     *
     * @see https://github.com/delight-im/PHP-Auth#taking-roles-away-from-users
     */
    public static function removeRole(string $role) {}


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
        return check_perms($PermissionName, $MinClass);

        /*
        $app = App::go();

        $app->userOld['EffectiveClass'] ??= 1000;
        if ($app->userOld['EffectiveClass'] >= 1000) {
            return true;
        } // Sysops can do anything

        if ($app->userOld['EffectiveClass'] < $MinClass) {
            return false;
        } // MinClass failure

        return $app->userOld['Permissions'][$PermissionName] ?? false; // Return actual permission
        */
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

        $Permission = $app->cache->get("perm_$PermissionID");
        if (empty($Permission)) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT Level AS Class, `Values` AS Permissions, Secondary, PermittedForums
            FROM permissions
              WHERE ID = '$PermissionID'");

            $Permission = $app->dbOld->next_record(MYSQLI_ASSOC, ['Permissions']);
            $app->dbOld->set_query_id($QueryID);
            $Permission['Permissions'] = json_decode($Permission['Permissions'], true);
            #$Permission['Permissions'] = unserialize($Permission['Permissions']);
            $app->cache->set("perm_$PermissionID", $Permission, 2592000);
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
              WHERE ID = ' . (int) $UserID);

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

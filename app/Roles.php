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
        "description" => "description",
        "isPrimaryRole" => "isPrimaryRole",
        "isSecondaryRole" => "isSecondaryRole",
        "isDefaultRole" => "isDefaultRole",
        "isStaffRole" => "isStaffRole",
        "maxPersonalCollages" => "maxPersonalCollages",
        "permissionsLevel" => "permissionsLevel",
        "permissionsList" => "permissionsList",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];

    # cache settings
    private string $cachePrefix = "roles:";
    private string $cacheDuration = "1 hour";

    # default role map
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


    /**
     * read
     *
     * Decodes the permissions JSON and adds extra attributes.
     *
     * @param int|string $identifier
     * @return void
     */
    public function read(int|string $identifier = null): void
    {
        $app = App::go();

        # normal read
        parent::read($identifier);

        # decode the permissions
        $this->attributes->permissionsList = json_decode($this->attributes->permissionsList ?? "{}", true);

        # get the user count
        $query = "select count(userId) from users_main where permissionId = ?";
        $this->attributes->userCount = $app->dbNew->single($query, [$this->id]);
    }


    /**
     * delete
     *
     * Deletes a role, unless it's a default role.
     *
     * @param int|string $identifier
     * @return void
     */
    public function delete(int|string $identifier = null): void
    {
        # can't delete default roles
        if ($this->attributes->isDefaultRole) {
            throw new Exception("can't delete default roles");
        }

        # normal delete
        parent::delete($identifier);
    }


    /** can and can't, shall and shan't */


    /**
     * can
     *
     * Checks if a user can do something.
     *
     * @param array $permissions e.g., ["torrents" => "read", "tags" => "updateAny"]
     * @return bool
     */
    public function can(array $permissions): bool
    {
        $app = App::go();

        # get the user's role and all permissions
        $userRole = self::getUserRole();
        $allPermissions = Permissions::getAll();

        # loop through the permissions
        foreach ($permissions as $resource => $action) {
            # invalid resource given
            if (!in_array($resource, array_keys($allPermissions))) {
                return false;
            }

            # invalid action given
            if (!in_array($action, array_keys($allPermissions[$resource]))) {
                return false;
            }

            # user has no permissions on the resource
            $userRole->attributes->permissionsList[$resource] ??= [];
            if (empty($userRole->attributes->permissionsList[$resource])) {
                return false;
            }

            # permission not in user's role
            $permissionsArray = $userRole->attributes->permissionsList->toArray();
            if (!in_array($action, $permissionsArray[$resource])) {
                return false;
            }
        }

        # checks passed, allow the action
        return true;
    }


    /**
     * cant
     *
     * The opposite of can.
     *
     * @param array $permission e.g., ["torrents" => "read", "tags" => "updateAny"]
     * @return bool
     */
    public function cant(array $permissions): bool
    {
        return !$this->can($permissions);
    }


    /** lists of roles and permissions */


    /**
     * getAll
     *
     * Returns an array of Roles objects.
     *
     * @return array
     */
    public function getAll(): array
    {
        $app = App::go();

        # there may be custom roles in the database
        $query = "select id from roles_permissions";
        $ref = $app->dbNew->column($query, []);

        $roles = [];
        foreach ($ref as $id) {
            $roles[] = new self($id);
        }

        return $roles;
    }


    /**
     * getUserRole
     *
     * Gets a user's permissions info.
     *
     * @param ?int $userId
     * @return array
     */
    public static function getUserRole(?int $userId = null): self
    {
        $app = App::go();

        # default to the logged in user
        $userId ??= $app->user->core["id"];

        # get the user's permissionId
        $query = "select permissionId from users_main where userId = ?";
        $permissionId = $app->dbNew->single($query, [$userId]);

        # no permissions found
        if (!$permissionId) {
            throw new Exception("no role found for user {$userId}");
        }

        return new self($permissionId);
    }


    /** role state introspection */


    /**
     * isPrimaryRole
     *
     * Checks if a role is a primary role.
     *
     * @param int $roleId
     * @return bool
     */
    public static function isPrimaryRole(int $roleId): bool
    {
        $role = new self($roleId);
        return $role->attributes->isPrimaryRole;
    }


    /**
     * isSecondaryRole
     *
     * Checks if a role is a secondary role.
     *
     * @param int $roleId
     * @return bool
     */
    public static function isSecondaryRole(int $roleId): bool
    {
        $role = new self($roleId);
        return $role->attributes->isSecondaryRole;
    }


    /**
     * isDefaultRole
     *
     * Checks if a role is a default role.
     *
     * @param int $roleId
     * @return bool
     */
    public static function isDefaultRole(int $roleId): bool
    {
        $role = new self($roleId);
        return $role->attributes->isDefaultRole;
    }


    /**
     * isStaffRole
     *
     * Checks if a role is a staff role.
     *
     * @param int $roleId
     * @return bool
     */
    public static function isStaffRole(int $roleId): bool
    {
        $role = new self($roleId);
        return $role->attributes->isStaffRole;
    }


    /**
     * is_mod
     */
    public static function is_mod($UserID)
    {
        return self::get_permissions_for_user($UserID)['users_mod'] ?? false;
    }
}

<?php

declare(strict_types=1);


/**
 * Gazelle\Permissions
 *
 * Mostly used to store lists of permissions.
 * It's easier than storing them in the database.
 */

namespace Gazelle;

class Permissions
{
    # simple crud natural language permissions
    # e.g., $app->user->can(["torrents" => "read", "tags" => "updateAny"])
    public static array $permissions = [
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

        # notifications
        "notifications" => [
            "create" => "Can create notifications",
            "read" => "Can read notifications",
            "updateOwn" => "Can update own notifications",
            "updateAny" => "Can update any notifications",
            "deleteOwn" => "Can delete own notifications",
            "deleteAny" => "Can delete any notifications",
        ],

        # subscriptions
        "subscriptions" => [
            "create" => "Can create subscriptions",
            "read" => "Can read subscriptions",
            "updateOwn" => "Can update own subscriptions",
            "updateAny" => "Can update any subscriptions",
            "deleteOwn" => "Can delete own subscriptions",
            "deleteAny" => "Can delete any subscriptions",
        ],

        # user accounts
        "userAccounts" => [
            "create" => "Can create user accounts",
            "read" => "Can read user accounts",
            "updateOwn" => "Can update own user accounts",
            "updateAny" => "Can update any user accounts",
            "deleteOwn" => "Can delete own user accounts",
            "deleteAny" => "Can delete any user accounts",
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

        # various admin permissions
        "admin" => [
            "advancedUserSearch" => "Can access advanced user search",
            "alwaysInvite" => "Can invite past user limit",
            "banUsers" => "Can ban users",
            "clearCache" => "Can clear the cache",
            "doublePost" => "Can double post in the forums",
            "freeleechTorrents" => "Can make torrents freeleech",
            "loginWatch" => "Can manage login watch",
            "manageBlog" => "Can manage the site blog",
            "manageForums" => "Can manage the forums (add/edit/delete)",
            "manageTechSupport" => "Can manage tech support",
            "manipulateRatio" => "Can manipulate user ratio",
            "moderateForums" => "Can moderate the forums",
            "moderateUsers" => "Can moderate users",
            "proxyImages" => "Can proxy images",
            "readUserInvites" => "Can view who user has invited",
            "reports" => "Can access the reports system",
            "sensitiveUserData" => "Can access user IPs and emails",
            "staffInbox" => "Can access the staff inbox",
            "unlimitedInvites" => "Can send unlimited invites",
            "updateRatios" => "Can update user ratios",
            "warnUsers" => "Can warn users",
        ],

        # admin toolbox
        "toolbox" => [
            "access" => "Can access the admin tools page",
            "autoEnableRequests" => "Can access the auto-enable requests page",
            "batchTagEditor" => "Can access the batch tag editor page",
            "clientWhitelist" => "Can access the client whitelist page",
            "collageRecovery" => "Can access the collage recovery page",
            "databaseKey" => "Can access the database key page",
            "emailBlacklist" => "Can access the email blacklist page",
            "forumManager" => "Can access the forum manager page",
            "freeleechTokenManager" => "Can access the freeleech token manager page",
            "globalNotifications" => "Can access the global notifications page",
            "invitePool" => "Can access the invite pool page",
            "ipAddressBans" => "Can access the IP address bans page",
            "loginWatch" => "Can access the login watch page",
            "manipulateInviteTree" => "Can access the manipulate invite tree page",
            "massPm" => "Can access the mass PM page",
            "miscellaneousValues" => "Can access the miscellaneous values page",
            "multipleFreeleech" => "Can access the multiple freeleech page",
            "newsPosts" => "Can access the news posts page",
            "officialTagsManager" => "Can access the official tags manager page",
            "permissionsManager" => "Can access the permissions manager page",
            "registrationLog" => "Can access the registration log page",
            "serviceStats" => "Can access the service stats page",
            "sitewideFreeleechManager" => "Can access the sitewide freeleech manager page",
            "tagAliases" => "Can access the tag aliases page",
            "trackerInformation" => "Can access the tracker information page",
            "upscalePool" => "Can access the upscale pool page",
        ],
    ];


    /**
     * getOne
     *
     * Returns a single object permission set.
     *
     * @param string $key
     * @return ?array
     */
    public static function getOne(string $key): ?array
    {
        return self::$permissions[$key] ?? null;
    }


    /**
     * getAll
     *
     * Returns an array of all permissions.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return self::$permissions;
    }


    /** */


    # putting the old permissions here as i work through the mess
    # i've commented all array keys that don't appear in the codebase
    public static array $oldPermissions = [
        #"admin_advanced_user_search" => "Can access advanced user search",
        #"admin_clear_cache" => "Can clear cached",
        #"admin_donor_log" => "Can view the donor log",
        #"admin_login_watch" => "Can manage login watch",
        #"admin_manage_blog" => "Can manage the site blog",
        #"admin_manage_fls" => "Can manage FLS",
        #"admin_manage_forums" => "Can manage forums (add/edit/delete)",
        #"admin_manage_ipbans" => "Can manage IP bans",
        #"admin_manage_news" => "Can manage site news",
        #"admin_manage_permissions" => "Can edit permission classes/user permissions",
        #"admin_manage_polls" => "Can manage polls",
        #"admin_manage_wiki" => "Can manage wiki access",
        #"admin_reports" => "Can access reports system",
        #"admin_schedule" => "Can run the site schedule",
        #"admin_whitelist" => "Can manage the list of allowed clients",
        #"artist_edit_vanityhouse" => "Can mark artists as part of Vanity House",
        #"edit_unknowns" => "Can edit unknown release information",
        #"forums_polls_create" => "Can create polls in the forums",
        #"forums_polls_moderate" => "Can feature and close polls",
        #"project_team" => "Is part of the project team",
        #"screenshots_add" => "Can add screenshots to any torrent and delete their own screenshots",
        #"screenshots_delete" => "Can delete any screenshot from any torrent",
        #"site_admin_forums" => "Forum administrator access",
        #"site_advanced_search" => "Advanced search access",
        "site_advanced_top10" => "Advanced Top 10 access",
        #"site_can_invite_always" => "Can invite past user limit",
        #"site_collages_create" => "Collage create access",
        #"site_collages_delete" => "Collage delete access",
        #"site_collages_manage" => "Collage manage access",
        #"site_collages_personal" => "Can have a personal collage",
        #"site_collages_recover" => "Can recover 'deleted' collages",
        #"site_collages_renamepersonal" => "Can rename own personal collages",
        #"site_collages_subscribe" => "Collage subscription access",
        #"site_debug" => "Developer access",
        #"site_delete_artist" => "Can delete artists (must be able to delete torrents+requests)",
        #"site_delete_tag" => "Can delete tags",
        #"site_edit_wiki" => "Wiki edit access",
        #"site_forums_double_post" => "Can double post in the forums",
        #"site_leech" => "Can leech (Does this work?)",
        #"site_make_bookmarks" => "Bookmarks access",
        #"site_manage_recommendations" => "Recommendations management access",
        #"site_moderate_forums" => "Forum moderation access",
        #"site_moderate_requests" => "Request moderation access",
        #"site_proxy_images" => "Image proxy & anti-canary",
        #"site_ratio_watch_immunity" => "Immune from being put on ratio watch",
        #"site_recommend_own" => "Can recommend own torrents",
        #"site_search_many" => "Can go past low limit of search results",
        #"site_send_unlimited_invites" => "Unlimited invites",
        #"site_submit_requests" => "Request create access",
        #"site_tag_aliases_read" => "Can view the list of tag aliases",
        #"site_top10" => "Top 10 access",
        #"site_torrents_notify" => "Notifications access",
        #"site_upload" => "Upload torrent access",
        #"site_view_flow" => "Can view stats and data pools",
        "site_view_full_log" => "Can view old log entries",
        "site_view_torrent_snatchlist" => "Can view torrent snatch lists",
        #"site_vote" => "Request vote access",
        #"torrents_add_artist" => "Can add artists to any group",
        #"torrents_delete" => "Can delete torrents",
        #"torrents_delete_fast" => "Can delete more than 3 torrents at a time",
        #"torrents_edit" => "Can edit any torrent",
        #"torrents_edit_vanityhouse" => "Can mark groups as part of Vanity House",
        "torrents_fix_ghosts" => "Can fix 'ghost' groups on artist pages",
        #"torrents_freeleech" => "Can make torrents freeleech",
        #"torrents_search_fast" => "Rapid search (for scripts)",
        #"users_delete_users" => "Can delete users",
        #"users_disable_any" => "Can disable any users' rights",
        #"users_disable_posts" => "Can disable users' posting privileges",
        #"users_disable_users" => "Can disable users",
        #"users_edit_avatars" => "Can edit avatars",
        "users_edit_invites" => "Can edit invite numbers and cancel sent invites",
        "users_edit_own_ratio" => "Can edit own upload/download amounts",
        #"users_edit_password" => "Can change passwords",
        #"users_edit_profiles" => "Can edit anyone's profile",
        #"users_edit_ratio" => "Can edit anyone's upload/download amounts",
        #"users_edit_reset_keys" => "Can reset passkey/authkey",
        #"users_edit_titles" => "Can edit titles",
        #"users_edit_usernames" => "Can edit usernames",
        #"users_edit_watch_hours" => "Can edit contrib watch hours",
        "users_give_donor" => "Can give donor access",
        "users_invite_notes" => "Can add a staff note when inviting someone",
        #"users_logout" => "Can log users out (old?)",
        "users_make_invisible" => "Can make users invisible",
        #"users_mod" => "Basic moderator tools",
        #"users_override_paranoia" => "Can override paranoia",
        #"users_promote_below" => "Can promote users to below current level",
        #"users_promote_to" => "Can promote users up to current level",
        #"users_reset_own_keys" => "Can reset own passkey/authkey",
        #"users_view_email" => "Can view email addresses",
        #"users_view_friends" => "Can view anyone's friends",
        "users_view_invites" => "Can view who user has invited",
        #"users_view_ips" => "Can view IP addresses",
        #"users_view_keys" => "Can view passkeys",
        #"users_view_seedleech" => "Can view what a user is seeding or leeching",
        #"users_view_uploaded" => "Can view a user's uploads, regardless of privacy level",
        #"users_warn" => "Can warn users",
        #"zip_downloader" => "Download multiple torrents at once",
    ];


    /**
     * getOld
     *
     * Returns an array of old permissions.
     *
     * @return array
     */
    public static function getOld(): array
    {
        return self::$oldPermissions;
    }
} # class

<?php

declare(strict_types=1);


/**
 * admin toolbox
 */

# index
Flight::route("/toolbox", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "access"]);
    $app->twig->display("admin/tools.twig", [
        "title" => "Admin tools",
        "sidebar" => true,
    ]);
}, false, "toolboxIndex");


# autoEnableRequests
Flight::route("/toolbox/auto-enable-requests", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "autoEnableRequests"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/enable_requests.php";
}, false, "autoEnableRequests");


# batchTagEditor
Flight::route("/toolbox/batch-tag-editor", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "batchTagEditor"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/misc/tags.php";
}, false, "batchTagEditor");


# clientWhitelist
Flight::route("/toolbox/client-whitelist", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "clientWhitelist"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/clientWhitelist.php";
}, false, "clientWhitelist");


# collageRecovery
Flight::route("/toolbox/collage-recovery", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "collageRecovery"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/collageRecovery.php";
}, false, "collageRecovery");


# databaseKey
Flight::route("/toolbox/database-key", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "databaseKey"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/databaseKey.php";
}, false, "databaseKey");


# emailBlacklist
Flight::route("/toolbox/email-blacklist", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "emailBlacklist"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/email_blacklist.php";
}, false, "emailBlacklist");


# forumManager
Flight::route("/toolbox/forum-manager", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "forumManager"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/forum_list.php";
}, false, "forumManager");


# freeleechTokenManager
Flight::route("/toolbox/freeleech-token-manager", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "freeleechTokenManager"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/tokens.php";
}, false, "freeleechTokenManager");


# globalNotifications
Flight::route("/toolbox/global-notifications", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "globalNotifications"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/global_notification.php";
}, false, "globalNotifications");


# invitePool
Flight::route("/toolbox/invite-pool", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "invitePool"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/data/invite_pool.php";
}, false, "invitePool");


# ipAddressBans
Flight::route("/toolbox/ip-address-bans", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "ipAddressBans"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/bans.php";
}, false, "ipAddressBans");


# loginWatch
Flight::route("/toolbox/login-watch", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "loginWatch"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/login_watch.php";
}, false, "loginWatch");


# manipulateInviteTree
Flight::route("/toolbox/manipulate-invite-tree", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "manipulateInviteTree"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/misc/manipulate_tree.php";
}, false, "manipulateInviteTree");


# massPm
Flight::route("/toolbox/mass-pm", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "massPm"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/mass_pm.php";
}, false, "massPm");


# miscellaneousValues
Flight::route("/toolbox/miscellaneous-values", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "miscellaneousValues"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/development/misc_values.php";
}, false, "miscellaneousValues");


# multipleFreeleech
Flight::route("/toolbox/multiple-freeleech", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "multipleFreeleech"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/multiple_freeleech.php";
}, false, "multipleFreeleech");


# newsPosts
Flight::route("/toolbox/news-posts(/@id)", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "newsPosts"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/news.php";
}, false, "newsPosts");


# officialTagsManager
Flight::route("/toolbox/official-tags-manager", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "officialTagsManager"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/official_tags.php";
}, false, "officialTagsManager");


# permissionsManager
Flight::route("/toolbox/permissions-manager(/@id)", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "permissionsManager"]);

    if ($id) {
        require_once "{$app->env->serverRoot}/sections/toolbox/roles/createUpdate.php";
    } else {
        require_once "{$app->env->serverRoot}/sections/toolbox/roles/listAll.php";
    }
}, false, "permissionsManager");


# registrationLog
Flight::route("/toolbox/registration-log", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "registrationLog"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/data/registration_log.php";
}, false, "registrationLog");


# serviceStats
Flight::route("/toolbox/service-stats", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "serviceStats"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/development/service_stats.php";
}, false, "serviceStats");


# sitewideFreeleechManager
Flight::route("/toolbox/sitewide-freeleech-manager", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "sitewideFreeleechManager"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/sitewide_freeleech.php";
}, false, "sitewideFreeleechManager");


# tagAliases
Flight::route("/toolbox/tag-aliases", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "tagAliases"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/tag_aliases.php";
}, false, "tagAliases");


# trackerInformation
Flight::route("/toolbox/tracker-information", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "trackerInformation"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/data/ocelot_info.php";
}, false, "trackerInformation");


# upscalePool
Flight::route("/toolbox/upscale-pool", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "upscalePool"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/data/upscale_pool.php";
}, false, "upscalePool");

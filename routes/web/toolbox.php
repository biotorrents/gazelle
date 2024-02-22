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
});


# autoEnableRequests
Flight::route("/toolbox/autoEnableRequests", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "autoEnableRequests"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/enable_requests.php";
});


# batchTagEditor
Flight::route("/toolbox/batchTagEditor", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "batchTagEditor"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/misc/tags.php";
});


# clientWhitelist
Flight::route("/toolbox/clientWhitelist", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "clientWhitelist"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/clientWhitelist.php";
});


# collageRecovery
Flight::route("/toolbox/collageRecovery", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "collageRecovery"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/collageRecovery.php";
});


# databaseKey
Flight::route("/toolbox/databaseKey", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "databaseKey"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/databaseKey.php";
});


# emailBlacklist
Flight::route("/toolbox/emailBlacklist", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "emailBlacklist"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/email_blacklist.php";
});


# forumManager
Flight::route("/toolbox/forumManager", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "forumManager"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/forum_list.php";
});


# freeleechTokenManager
Flight::route("/toolbox/freeleechTokenManager", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "freeleechTokenManager"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/tokens.php";
});


# globalNotifications
Flight::route("/toolbox/globalNotifications", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "globalNotifications"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/global_notification.php";
});


# invitePool
Flight::route("/toolbox/invitePool", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "invitePool"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/data/invite_pool.php";
});


# ipAddressBans
Flight::route("/toolbox/ipAddressBans", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "ipAddressBans"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/bans.php";
});


# loginWatch
Flight::route("/toolbox/loginWatch", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "loginWatch"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/login_watch.php";
});


# manipulateInviteTree
Flight::route("/toolbox/manipulateInviteTree", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "manipulateInviteTree"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/misc/manipulate_tree.php";
});


# massPm
Flight::route("/toolbox/massPm", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "massPm"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/mass_pm.php";
});


# miscellaneousValues
Flight::route("/toolbox/miscellaneousValues", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "miscellaneousValues"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/development/misc_values.php";
});


# multipleFreeleech
Flight::route("/toolbox/multipleFreeleech", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "multipleFreeleech"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/multiple_freeleech.php";
});


# newsPosts
Flight::route("/toolbox/newsPosts(/@id)", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "newsPosts"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/news.php";
});


# officialTagsManager
Flight::route("/toolbox/officialTagsManager", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "officialTagsManager"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/official_tags.php";
});


# permissionsManager
Flight::route("/toolbox/permissionsManager(/@id)", function ($id) {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "permissionsManager"]);

    if ($id) {
        require_once "{$app->env->serverRoot}/sections/toolbox/roles/createUpdate.php";
    } else {
        require_once "{$app->env->serverRoot}/sections/toolbox/roles/listAll.php";
    }
});


# registrationLog
Flight::route("/toolbox/registrationLog", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "registrationLog"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/data/registration_log.php";
});


# serviceStats
Flight::route("/toolbox/serviceStats", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "serviceStats"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/development/service_stats.php";
});


# sitewideFreeleechManager
Flight::route("/toolbox/sitewideFreeleechManager", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "sitewideFreeleechManager"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/sitewide_freeleech.php";
});


# tagAliases
Flight::route("/toolbox/tagAliases", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "tagAliases"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/managers/tag_aliases.php";
});


# trackerInformation
Flight::route("/toolbox/trackerInformation", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "trackerInformation"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/data/ocelot_info.php";
});


# upscalePool
Flight::route("/toolbox/upscalePool", function () {
    $app = Gazelle\App::go();
    $app->middleware(["toolbox" => "upscalePool"]);
    require_once "{$app->env->serverRoot}/sections/toolbox/data/upscale_pool.php";
});

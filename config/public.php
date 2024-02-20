<?php

declare(strict_types=1);


/**
 * public configuration
 */

# which environment are we running in?
# expected values are ["production", "development"]
$env->environment = "development";


# development or production?
match ($env->environment) {
    "production" => $env->dev = false,
    "development" => $env->dev = true,
    default => throw new Exception("invalid environment"),
};


# disable kint on production
if (!$env->dev) {
    Kint\Kint::$enabled_mode = false;
}


# https://stackify.com/display-php-errors/
if ($env->dev) {
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
    error_reporting(E_ALL);
}


# allow the site encryption key to be set without an account
# (should only be used for initial setup)
match ($env->environment) {
    "production" => $env->enablePublicEncryptionKey = false,
    "development" => $env->enablePublicEncryptionKey = true,
    default => throw new Exception("invalid environment"),
};


# is there an apcu database key loaded?
$env->apcuExists = apcu_exists("DBKEY");


# mariadb date format
$env->sqlDate = "Y-m-d H:i:s";


/**
 * site identity
 *
 * NO TRAILING SLASHES ON ANY PATHS!
 * e.g., /var/www = good, /var/www/ = bad
 */

# site name, e.g., BioTorrents.de
match ($env->environment) {
    "production" => $env->siteName = "BioTorrents.de",
    "development" => $env->siteName = "[dev] BioTorrents.de",
    default => throw new Exception("invalid environment"),
};


# website FQDN, e.g., dev.torrents.bio
match ($env->environment) {
    "production" => $env->siteDomain = "torrents.bio",
    "development" => $env->siteDomain = "dev.torrents.bio",
    default => throw new Exception("invalid environment"),
};


# old domain, to handle the biotorrents.de => torrents.bio migration
# if not needed, simply set to the same values as $env->siteDomain
match ($env->environment) {
    "production" => $env->oldSiteDomain = "biotorrents.de",
    "development" => $env->oldSiteDomain = "dev.biotorrents.de",
    default => throw new Exception("invalid environment"),
};


# REMOVE ME
(
    !$env->dev
        ? define("siteDomain", "torrents.bio") # production
        : define("siteDomain", "dev.torrents.bio") # development
);
# REMOVE ME


# meta description
$env->siteDescription = "An open platform for libre biology data";


# navigation glyphs
$env->separator = "-"; # e.g., News - dev.torrents.bio
$env->crumb = "›"; # e.g., Forums › Board › Thread


# image host FQDN, e.g., pics.torrents.bio
$env->imageDomain = "pics.torrents.bio";


# documentation site
$env->docsDomain = "docs.torrents.bio";


# starboard notebook site
# https://github.com/gzuidhof/starboard-notebook
$env->starboardDomain = "starboard.torrents.bio"; # set as null to use guido's cdn
if (!$env->starboardDomain) {
    $env->starboardDomain = "cdn.starboard.gg/npm/starboard-notebook@0.13.2/dist/index.html";
}


# web root: currently used for twig
$env->webRoot = "/var/www";


# app filesystem route (not web root)
# e.g., /var/www/html/gazelle
match ($env->environment) {
    "production" => $env->serverRoot = "/var/www/html/gazelle",
    "development" => $env->serverRoot = "/var/www/html/gazelle",
    default => throw new Exception("invalid environment"),
};


# do we want to maintain a local satis archive?
match ($env->environment) {
    "production" => $env->enableSatis = false,
    "development" => $env->enableSatis = true,
    default => throw new Exception("invalid environment"),
};


# location of the satis archive on disk
match ($env->environment) {
    "production" => $env->satisRoot = "/var/www/html/satis",
    "development" => $env->satisRoot = "/var/www/html/satis",
    default => throw new Exception("invalid environment"),
};


# REMOVE ME
(
    !$env->dev
        ? define("serverRoot", "/var/www/html/gazelle") # production
        : define("serverRoot", "/var/www/html/gazelle") # development
);
# REMOVE ME


# where torrent files are stored, e.g., /var/www/torrents
match ($env->environment) {
    "production" => $env->torrentStore = "/var/www/torrents",
    "development" => $env->torrentStore = "/var/www/torrents",
    default => throw new Exception("invalid environment"),
};


# allows you to run static content off another server
# the default is usually what you want though
match ($env->environment) {
    "production" => $env->staticServer = "/public",
    "development" => $env->staticServer = "/public",
    default => throw new Exception("invalid environment"),
};


# REMOVE ME
define("staticServer", "/public");
# REMOVE ME


# hash algorithm used for SRI
# https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity
$env->subresourceIntegrity = "sha512";


/**
 * toggle announce channels
 */

# irc
match ($env->environment) {
    "production" => $env->announceIrc = true,
    "development" => $env->announceIrc = true,
    default => throw new Exception("invalid environment"),
};


# rss
match ($env->environment) {
    "production" => $env->announceRss = true,
    "development" => $env->announceRss = true,
    default => throw new Exception("invalid environment"),
};


# slack
match ($env->environment) {
    "production" => $env->announceSlack = true,
    "development" => $env->announceSlack = true,
    default => throw new Exception("invalid environment"),
};


# twitter
match ($env->environment) {
    "production" => $env->announceTwitter = true,
    "development" => $env->announceTwitter = true,
    default => throw new Exception("invalid environment"),
};


/**
 * irc
 */

# hostname for the onsite chat tool
$env->ircHostname = "irc.{$env->siteDomain}";


# server address
$env->ircAddress = "10.10.10.60";


# port (raw commands)
$env->ircPort = 51010;


# bot nickname
$env->ircBotNick = "ebooks";


# REMOVE ME
define("BOT_SERVER", "irc.$env->siteDomain");
define("SOCKET_LISTEN_ADDRESS", "10.10.10.60");
define("SOCKET_LISTEN_PORT", 51010);
define("BOT_NICK", "ebooks");
# REMOVE ME


# REMOVE ME
# irc channels for official business
define("ANNOUNCE_CHAN", "#announce");
define("DEBUG_CHAN", "#debug");
define("REQUEST_CHAN", "#requests");
define("STAFF_CHAN", "#staff");
define("ADMIN_CHAN", "#staff");
define("DISABLED_CHAN", "#support");
# REMOVE ME


/**
 * features and settings
 */

# enable donation page
$env->enableDonations = true;


# send re-enable requests to user's email
$env->FEATURE_EMAIL_REENABLE = true;


# REMOVE ME
define("FEATURE_EMAIL_REENABLE", true);
# REMOVE ME


# attempt to support the BioPHP library
# https://packagist.org/packages/biotorrents/biophp
# https://pkg.go.dev/github.com/TimothyStiles/poly/seqhash
$env->enableBioPhp = true;


# set to false to disable open registration, true to allow anyone to register
match ($env->environment) {
    "production" => $env->openRegistration = true,
    "development" => $env->openRegistration = false,
    default => throw new Exception("invalid environment"),
};


# the maximum number of users the site can have, 0 for no limit
match ($env->environment) {
    "production" => $env->userLimit = 0,
    "development" => $env->userLimit = 0,
    default => throw new Exception("invalid environment"),
};


# REMOVE ME
define("userLimit", 0);
# REMOVE ME


# user perks on new registration
$env->newUserInvites = 2;
$env->newUserTokens = 2;
$env->newUserUpload = 5 * 1024 ** 3; # 5 GiB


# bonus points (unit name)
$env->bonusPoints = "Bonus Points";


# REMOVE ME
define("bonusPoints", "Bonus Points");
# REMOVE ME


# award formula coefficient
$env->bonusPointsCoefficient = 1.5; # oppaitime default 0.5


# https://www.mtu.edu/umc/services/websites/writing/characters-avoid/
$env->illegalCharacters = [ "#", "%", "&", "{", "}", "\\", "<", ">", "*", "?", "\/", "$", "!", "'", "\"", ":", "@", "+", "`", "|", "=" ];


/*
# inherited from oppaitime
$env->illegalCharacters = ["\"", "*", "\/", ":", "<", ">", "?", "\\", "|"];
*/


# ratio requirements, in descending order
$env->ratioRequirements = [
    # downloaded       req (0% seed) req (100% seed)
     [200 * 1024 ** 3, 0.60,         0.60],
     [160 * 1024 ** 3, 0.60,         0.50],
     [120 * 1024 ** 3, 0.50,         0.40],
     [100 * 1024 ** 3, 0.40,         0.30],
     [80  * 1024 ** 3, 0.30,         0.20],
     [60  * 1024 ** 3, 0.20,         0.10],
     [40  * 1024 ** 3, 0.15,         0.00],
     [20  * 1024 ** 3, 0.10,         0.00],
     [10  * 1024 ** 3, 0.05,         0.00],
];


# REMOVE ME
define("RATIO_REQUIREMENTS", [
    # downloaded       req (0% seed) req (100% seed)
     [200 * 1024 ** 3, 0.60,         0.60],
     [160 * 1024 ** 3, 0.60,         0.50],
     [120 * 1024 ** 3, 0.50,         0.40],
     [100 * 1024 ** 3, 0.40,         0.30],
     [80  * 1024 ** 3, 0.30,         0.20],
     [60  * 1024 ** 3, 0.20,         0.10],
     [40  * 1024 ** 3, 0.15,         0.00],
     [20  * 1024 ** 3, 0.10,         0.00],
     [10  * 1024 ** 3, 0.05,         0.00],
]);
# REMOVE ME


# default site options
$env->defaultSiteOptions = json_encode([
    "autoSubscribe" => true,
    "calmMode" => false,
    "communityStats" => true,
    "coverArtCollections" => 20,
    "coverArtTorrents" => true,
    "coverArtTorrentsExtra" => true,
    "darkMode" => false,
    "donorIcon" => true,
    "font" => "",
    "listUnreadsFirst" => true,
    "openaiContent" => true,
    "percentileStats" => true,
    "profileConversations" => true,
    "recentCollages" => true,
    "recentRequests" => true,
    "recentSnatches" => true,
    "recentUploads" => true,
    "requestStats" => true,
    "searchPagination" => 20,
    "searchType" => "simple",
    "showSnatched" => true,
    "styleId" => 1,
    "styleUri" => "",
    "torrentGrouping" => "open",
    "torrentGrouping" => true,
    "torrentStats" => true,
    "unseededAlerts" => true,
    "userAvatars" => true,
]);


/**
 * services
 *
 * public apis, domains, etc.
 * not intended for private keys
 */

# current sci-hub domains
# https://lovescihub.wordpress.com
$env->sciHubTlds = ["ren", "tw", "se"];


# REMOVE ME
define("SCI_HUB", "se");
# REMOVE ME


/**
 * user class ids
 *
 * needed for automatic promotions
 * found in the `permissions` table
 */

$GiB = 1024 * 1024 * 1024;
$week = 3600 * 24 * 7;

# https://redacted.ch/wiki.php?action=article&name=userclasses
$env->classPromotions = [

    "user" => [
        "id" => 2,
        "title" => "User",

        "nextId" => 3,
        "nextTitle" => "Member",

        "dataUploaded" => 0,
        "torrentsUploaded" => 0,
        "minimumRatio" => 0.0,
        "maximumTime" => null,
    ],

    "member" => [
        "id" => 3,
        "title" => "Member",

        "nextId" => 4,
        "nextTitle" => "Power User",

        "dataUploaded" => 10 * $GiB,
        "torrentsUploaded" => 1,
        "minimumRatio" => 0.8,
        "maximumTime" => time() - $week * 1,
    ],

    "powerUser" => [
        "id" => 4,
        "title" => "Power User",

        "nextId" => 5,
        "nextTitle" => "Elite",

        "dataUploaded" => 20 * $GiB,
        "torrentsUploaded" => 2,
        "minimumRatio" => 1.0,
        "maximumTime" => time() - $week * 2,
    ],

    "elite" => [
        "id" => 5,
        "title" => "Elite",

        "nextId" => 23,
        "nextTitle" => "Torrent Master",

        "dataUploaded" => 50 * $GiB,
        "torrentsUploaded" => 5,
        "minimumRatio" => 1.2,
        "maximumTime" => time() - $week * 4,
    ],

    "torrentMaster" => [
        "id" => 23,
        "title" => "Torrent Master",

        "nextId" => 24,
        "nextTitle" => "Power Master",

        "dataUploaded" => 100 * $GiB,
        "torrentsUploaded" => 10,
        "minimumRatio" => 1.4,
        "maximumTime" => time() - $week * 8,
    ],

    "powerMaster" => [
        "id" => 24,
        "title" => "Power Master",

        "nextId" => 25,
        "nextTitle" => "Elite Master",

        "dataUploaded" => 200 * $GiB,
        "torrentsUploaded" => 20,
        "minimumRatio" => 1.6,
        "maximumTime" => time() - $week * 16,
    ],

    "eliteMaster" => [
        "id" => 25,
        "title" => "Elite Master",

        "nextId" => 8,
        "nextTitle" => "Legend",

        "dataUploaded" => 500 * $GiB,
        "torrentsUploaded" => 50,
        "minimumRatio" => 1.8,
        "maximumTime" => time() - $week * 32,
    ],

    "legend" => [
        "id" => 8,
        "title" => "Legend",

        "nextId" => null,
        "nextTitle" => null,

        "dataUploaded" => 1000 * $GiB,
        "torrentsUploaded" => 100,
        "minimumRatio" => 2.0,
        "maximumTime" => time() - $week * 64,
    ],

];


# REMOVE ME
# name of class => class id (not level)
define("ADMIN", 1);
define("USER", 2);
define("MEMBER", 3);
define("POWER", 4);
define("ELITE", 5);
define("LEGEND", 8);
define("MOD", 11);
define("SYSOP", 15);
define("ARTIST", 19);
define("DONOR", 20);
define("VIP", 21);
define("TORRENT_MASTER", 23);
define("POWER_TM", 24);
define("ELITE_TM", 25);
define("FLS_TEAM", 33);
define("FORUM_MOD", 9001);
# REMOVE ME


/**
 * forums
 */

$env->ANNOUNCEMENT_FORUM = 1;
$env->TRASH_FORUM = 8;


# REMOVE ME
define("STAFF_FORUM", 3);
define("DONOR_FORUM", 7);
# REMOVE ME


# REMOVE ME
# pagination
define("TORRENT_COMMENTS_PER_PAGE", 10);
define("POSTS_PER_PAGE", 25);
define("TOPICS_PER_PAGE", 50);
define("TORRENTS_PER_PAGE", 50);
define("REQUESTS_PER_PAGE", 25);
define("MESSAGES_PER_PAGE", 25);
define("LOG_ENTRIES_PER_PAGE", 50);
# REMOVE ME


# REMOVE ME
# cache catalogues
define("THREAD_CATALOGUE", 500); # limit to THREAD_CATALOGUE posts per cache key
# REMOVE ME

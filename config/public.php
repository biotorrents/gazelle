<?php

declare(strict_types=1);


/**
 * public
 */

# development or production?
ENV::setPub("dev", true);

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
ENV::setPub("enablePublicEncryptionKey", false);

# is there an apcu database key loaded?
ENV::setPub("apcuKey", apcu_exists("DBKEY"));

# mariadb date format
ENV::setPub("sqlDate", "Y-m-d H:i:s");


/**
 * site identity
 *
 * NO TRAILING SLASHES ON ANY PATHS!
 * e.g., /var/www = good, /var/www/ = bad
 */

# site name
ENV::setPub(
    "siteName",
    (!$env->dev
        ? "torrents.bio" # production
        : "dev.torrents.bio") # development
);

# meta description
ENV::setPub("siteDescription", "An open platform for libre biology data");

# navigation glyphs
ENV::setPub("separator", "-"); # e.g., News - dev.torrents.bio
ENV::setPub("crumb", "›"); # e.g., Forums › Board › Thread

# website FQDN, e.g., dev.torrents.bio
( # old format
    !$env->dev
        ? define("siteDomain", "torrents.bio") # production
        : define("siteDomain", "dev.torrents.bio") # development
);

ENV::setPub(
    "siteDomain",
    (!$env->dev
        ? "torrents.bio" # production
        : "dev.torrents.bio") # development
);

# old domain, to handle the biotorrents.de => torrents.bio migration
# if not needed, simply set to the same values as $env->siteDomain
ENV::setPub(
    "oldSiteDomain",
    (!$env->dev
        ? "biotorrents.de" # production
        : "dev.torrents.bio") # pevelopment
);

# image host FQDN, e.g., pics.torrents.bio
ENV::setPub("imageDomain", "pics.torrents.bio");

# documentation site
ENV::setPub("docsDomain", "docs.torrents.bio");

# web root: currently used for twig
ENV::setPub("webRoot", "/var/www");

# app filesystem route (not web root)
# e.g., /var/www/html/dev.torrents.bio
( # old format
    !$env->dev
        ? define("serverRoot", "/var/www/html/gazelle") # production
        : define("serverRoot", "/var/www/html/gazelle") # development
);

ENV::setPub(
    "serverRoot",
    (!$env->dev
      ? "/var/www/html/gazelle" # production
      : "/var/www/html/gazelle") # development
);

# where torrent files are stored, e.g., /var/www/torrents
ENV::setPub(
    "torrentStore",
    (!$env->dev
        ? "/var/www/torrents" # production
        : "/var/www/torrents") # development);
);

# allows you to run static content off another server
# the default is usually what you want though
define("staticServer", "/public");
ENV::setPub("staticServer", "/public");

# hash algorithm used for SRI
ENV::setPub("SRI", "sha512");


/**
 *  tracker
 *
 * @see http://bittorrent.org/beps/bep_0012.html
 */

# production
if (!$env->dev) {
    define("ANNOUNCE_URLS", [
         [ # tier 1
           "https://track.torrents.bio:443",
          ], [] # tier 2
      ]);

    $AnnounceURLs = [
      [ # tier 1
        "https://track.torrents.bio:443",
      ],
      [ # tier 2
        #"udp://tracker.coppersurfer.tk:6969/announce",
        #"udp://tracker.cyberia.is:6969/announce",
        #"udp://tracker.leechers-paradise.org:6969/announce",
      ],
    ];
    ENV::setPub(
        "ANNOUNCE_URLS",
        $env->convert($AnnounceURLs)
    );
}

# development
else {
    define("ANNOUNCE_URLS", [
      [ # tier 1
        "https://trx.torrents.bio:443",
      ],
      [ # tier 2
      #"udp://tracker.coppersurfer.tk:6969/announce",
      #"udp://tracker.cyberia.is:6969/announce",
      #"udp://tracker.leechers-paradise.org:6969/announce",
    ],
    ]);

    $AnnounceURLs = [
      [ # tier 1
        "https://trx.torrents.bio:443",
      ], [], # tier 2
    ];
    ENV::setPub(
        "ANNOUNCE_URLS",
        $env->convert($AnnounceURLs)
    );
}


/**
 * announce channels
 */

ENV::setPub("announceIrc", true);
ENV::setPub("announceRss", true);
ENV::setPub("announceSlack", true);
ENV::setPub("announceTwitter", true);


/**
 * IRC/Slack
 */

# IRC server address. Used for onsite chat tool
define("BOT_SERVER", "irc.$env->siteDomain");
ENV::setPub("ircHostname", "irc.{$env->siteDomain}");


define("SOCKET_LISTEN_ADDRESS", "10.0.0.4");
ENV::setPub("ircAddress", "10.0.0.4");

define("SOCKET_LISTEN_PORT", 51010);
ENV::setPub("ircPort", 51010);

define("BOT_NICK", "ebooks");
ENV::setPub("ircBotNick", "ebooks");

# IRC channels for official business
define("ANNOUNCE_CHAN", "#announce");
define("DEBUG_CHAN", "#debug");
define("REQUEST_CHAN", "#requests");
define("STAFF_CHAN", "#staff");
define("ADMIN_CHAN", "#staff");
define("HELP_CHAN", "#support");
define("DISABLED_CHAN", "#support");
#define("BOT_CHAN", "#userbots");


/**
 * features
 */

# enable donation page
ENV::setPub("enableDonations", true);

# send re-enable requests to user's email
define("FEATURE_EMAIL_REENABLE", true);
ENV::setPub("FEATURE_EMAIL_REENABLE", true);

# attempt to send email from the site
ENV::setPub("enableSiteEmail", true);


# Attempt to support the BioPHP library
# https://packagist.org/packages/biotorrents/biophp
# https://pkg.go.dev/github.com/TimothyStiles/poly/seqhash
ENV::setPub("enableBioPhp", true);


/**
 * Settings
 */

# Set to false to disable open registration, true to allow anyone to register
ENV::setPub(
    "openRegistration",
    (!$env->dev
        ? true # Production
        : true) # Development
);

# The maximum number of users the site can have, 0 for no limit
define("userLimit", 0);
ENV::setPub("userLimit", 0);

# user perks on new registration
ENV::setPub("newUserInvites", 2);
ENV::setPub("newUserTokens", 2);
ENV::setPub("newUserUpload", 5368709120); # 5 GiB

# bonus points (unit name)
define("bonusPoints", "Bonus Points");
ENV::setPub("bonusPoints", "Bonus Points");

# award formula coefficient
ENV::setPub("bonusPointsCoefficient", 1.5); # oppaitime default 0.5

# tag namespaces (configurable via CSS selectors)
#ENV::setPub("tagNamespaces", ["male", "female", "parody", "character"]);

# https://www.mtu.edu/umc/services/websites/writing/characters-avoid/
ENV::setPub(
    "illegalCharacters",
    [ "#", "%", "&", "{", "}", "\\", "<", ">", "*", "?", "\/", "$", "!", "'", "\"", ":", "@", "+", "`", "|", "=" ]
);

/*
# inherited from oppaitime
ENV::setPub(
    "illegalCharacters",
    ["\"", "*", "\/", ":", "<", ">", "?", "\\", "|"]
);
*/

# default site options
$defaultSiteOptions = [
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
];

ENV::setPub("defaultSiteOptions", json_encode($defaultSiteOptions));


/**
 * services
 *
 * Public APIs, domains, etc.
 * Not intended for private API keys.
 */

# Current Sci-Hub domains
# https://lovescihub.wordpress.com
define("SCI_HUB", "se");
ENV::setPub(
    "SCI_HUB",
    ["ren", "tw", "se"]
);


/**
 * User class IDs
 *
 * Needed for automatic promotions.
 * Found in the `permissions` table.
 */

#       Name of class     Class ID (not level)
define("ADMIN", "1");
define("USER", "2");
define("MEMBER", "3");
define("POWER", "4");
define("ELITE", "5");
define("LEGEND", "8");
define("MOD", "11");
define("SYSOP", "15");
define("ARTIST", "19");
define("DONOR", "20");
define("VIP", "21");
define("TORRENT_MASTER", "23");
define("POWER_TM", "24");
define("FLS_TEAM", "33");
define("FORUM_MOD", "9001");


/**
 * Forums
 */

define("STAFF_FORUM", 3);
define("DONOR_FORUM", 7);

ENV::setPub("TRASH_FORUM", 8);
ENV::setPub("ANNOUNCEMENT_FORUM", 1);
ENV::setPub("SUGGESTIONS_FORUM", 2);

# Pagination
define("TORRENT_COMMENTS_PER_PAGE", 10);
define("POSTS_PER_PAGE", 25);
define("TOPICS_PER_PAGE", 50);
define("TORRENTS_PER_PAGE", 50);
define("REQUESTS_PER_PAGE", 25);
define("MESSAGES_PER_PAGE", 25);
define("LOG_ENTRIES_PER_PAGE", 50);

ENV::setPub("paginationDefault", 25);

# Cache catalogues
define("THREAD_CATALOGUE", 500); // Limit to THREAD_CATALOGUE posts per cache key

# Miscellaneous values
define("MAX_RANK", 6);
define("MAX_EXTRA_RANK", 8);
define("MAX_SPECIAL_RANK", 3);

ENV::setPub("DONOR_FORUM_RANK", 6);


/**
 * ratio and badges
 */

# ratio requirements, in descending order
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

# God I wish I didn't have to do this but I just don't care anymore
$AutomatedBadgeIDs = [
  "DL" => [
    "16"   => 10,
    "32"   => 11,
    "64"   => 12,
    "128"  => 13,
    "256"  => 14,
    "512"  => 15,
    "1024" => 16,
    "2048" => 17,
    "4096" => 18,
    "8192" => 19,
  ],

  "UL" => [
    "16"   => 20,
    "32"   => 21,
    "64"   => 22,
    "128"  => 23,
    "256"  => 24,
    "512"  => 25,
    "1024" => 26,
    "2048" => 27,
    "4096" => 28,
    "8192" => 29,
  ],

  "Posts" => [
    "10"   => 30,
    "20"   => 31,
    "50"   => 32,
    "100"  => 33,
    "200"  => 34,
    "500"  => 35,
    "1000" => 36,
    "2000" => 37,
    "5000" => 38,
    "10000" => 39,
  ]
];
ENV::setPub(
    "AUTOMATED_BADGE_IDS",
    $env->convert($AutomatedBadgeIDs)
);

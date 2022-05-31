<?php
declare(strict_types=1);


/**
 * public
 */

# development or production?
ENV::setPub("dev", true);

# disable kint on production
if (!$env->dev) {
    Kint\Kint::$enabled_mode	= false;
}


/**
 * site identity
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
        ? define("SITE_DOMAIN", "torrents.bio") # production
        : define("SITE_DOMAIN", "dev.torrents.bio") # development
);

ENV::setPub(
    "SITE_DOMAIN",
    (!$env->dev
        ? "torrents.bio" # production
        : "dev.torrents.bio") # development
);

# old domain, to handle the biotorrents.de => torrents.bio migration
# if not needed, simply set to the same values as $env->siteDomainNew
ENV::setPub(
    "OLD_SITE_DOMAIN",
    (!$env->dev
        ? "biotorrents.de" # production
        : "dev.torrents.bio") # pevelopment
);

# image host FQDN, e.g., pics.torrents.bio
ENV::setPub("imageDomain", "pics.torrents.bio");

# web root: currently used for twig
ENV::setPub("webRoot", "/var/www/");

# app filesystem route (not web root), e.g., /var/www/html/dev.torrents.bio/
( # old format
    !$env->dev
        ? define("SERVER_ROOT", "/var/www/html/biotorrents.de/") # production
        : define("SERVER_ROOT", "/var/www/html/dev.torrents.bio/") # development
);

ENV::setPub(
    "SERVER_ROOT",
    (!$env->dev
        ? "/var/www/html/biotorrents.de/" # production
        : "/var/www/html/dev.torrents.bio/") # development
);

# where torrent files are stored, e.g., /var/www/torrents-dev/
( # old format
    !$env->dev
        ? define("TORRENT_STORE", "/var/www/torrents/") # production
        : define("TORRENT_STORE", "/var/www/torrents-dev/") # development
);

ENV::setPub(
    "TORRENT_STORE",
    (!$env->dev
        ? "/var/www/torrents/" # production
        : "/var/www/torrents-dev/") # development);
);

# allows you to run static content off another server
# the default is usually what you want though
define("STATIC_SERVER", "/public/");
ENV::setPub("STATIC_SERVER", "/public/");

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
           "https://track.biotorrents.de:443",
          ], [] # tier 2
      ]);

    $AnnounceURLs = [
      [ # tier 1
        "https://track.biotorrents.de:443",
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
        "https://trx.biotorrents.de:443",
      ], [] # tier 2
    ]);

    $AnnounceURLs = [
      [ # tier 1
        "https://trx.biotorrents.de:443",
      ], [], # tier 2
    ];
    ENV::setPub(
        "ANNOUNCE_URLS",
        $env->convert($AnnounceURLs)
    );
}


/**
 * search
 */

# SphinxqlQuery needs constants to work
# $app->env breaks the torrent and request pages
define("SPHINXQL_HOST", "127.0.0.1");
define("SPHINXQL_PORT", 9306);
define("SPHINXQL_SOCK", false);
define("SPHINX_MAX_MATCHES", 1000); # must be <= server max_matches (default 1000)


/**
 * memcached
 *
 * Very important to run two instances,
 * one each for development and production.
 */

 # production
if (!$env->dev) {
    ENV::setPriv(
        "MEMCACHED_SERVERS",
        [[
          "host" => "unix:///var/run/memcached/memcached.sock",
          "port" => 0,
          "buckets" => 1
        ]]
    );
}

# development
else {
    ENV::setPriv(
        "MEMCACHED_SERVERS",
        [[
          "host" => "unix:///var/run/memcached/memcached-dev.sock",
          "port" => 0,
          "buckets" => 1
        ]]
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
define("BOT_SERVER", "irc.$env->SITE_DOMAIN");
define("SOCKET_LISTEN_ADDRESS", "10.0.0.4");
define("SOCKET_LISTEN_PORT", 51010);
define("BOT_NICK", "ebooks");

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
ENV::setPub("FEATURE_DONATE", true);

# send re-enable requests to user's email
define("FEATURE_EMAIL_REENABLE", true);
ENV::setPub("FEATURE_EMAIL_REENABLE", true);

# attempt to send email from the site
ENV::setPub("enableSiteEmail", true);

# Allow the site encryption key to be set without an account
# (should only be used for initial setup)
ENV::setPub("FEATURE_SET_ENC_KEY_PUBLIC", true);

# Attempt to support the BioPHP library
# https://packagist.org/packages/biotorrents/biophp
# https://blog.libredna.org/post/seqhash/
ENV::setPub("FEATURE_BIOPHP", false);


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
define("USER_LIMIT", 0);
ENV::setPub("USER_LIMIT", 0);

# User perks
ENV::setPub("STARTING_INVITES", 2);
ENV::setPub("STARTING_TOKENS", 2);
ENV::setPub("STARTING_UPLOAD", 5368709120);
ENV::setPub("DONOR_INVITES", 2);

# Bonus Points
define("BONUS_POINTS", "Bonus Points");
ENV::setPub("BONUS_POINTS", "Bonus Points");

ENV::setPub("BP_COEFF", 1.5); # OT default 0.5

# Tag namespaces (configurable via CSS selectors)
#ENV::setPub("tagNamespaces", ["male", "female", "parody", "character"]);

# Banned stuff (file characters, browsers, etc.)
ENV::setPub(
    "BAD_CHARS",
    ["\"", "*", "/", ":", "<", ">", "?", "\\", "|"]
);



/**
 * Services
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

# Semantic Scholar
# https://api.semanticscholar.org
ENV::setPub("SS", "https://api.semanticscholar.org/v1/paper/");

# IP Geolocation
ENV::setPub("IP_GEO", "https://tools.keycdn.com/geo.json?host=");


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

# Cache catalogues
define("THREAD_CATALOGUE", 500); // Limit to THREAD_CATALOGUE posts per cache key

# Miscellaneous values
define("MAX_RANK", 6);
define("MAX_EXTRA_RANK", 8);
define("MAX_SPECIAL_RANK", 3);

ENV::setPub("DONOR_FORUM_RANK", 6);


/**
 * Ratio and badges
 */

# Ratio requirements, in descending order
define("RATIO_REQUIREMENTS", [
 # Downloaded     Req (0% seed) Req (100% seed)
  [200 * 1024**3, 0.60,         0.60],
  [160 * 1024**3, 0.60,         0.50],
  [120 * 1024**3, 0.50,         0.40],
  [100 * 1024**3, 0.40,         0.30],
  [80  * 1024**3, 0.30,         0.20],
  [60  * 1024**3, 0.20,         0.10],
  [40  * 1024**3, 0.15,         0.00],
  [20  * 1024**3, 0.10,         0.00],
  [10  * 1024**3, 0.05,         0.00],
]);

# God I wish I didn't have to do this but I just don't care anymore
$AutomatedBadgeIDs = [
  "DL" => [
    "8"    => 10,
    "16"   => 11,
    "32"   => 12,
    "64"   => 13,
    "128"  => 14,
    "256"  => 15,
    "512"  => 16,
    "1024" => 17,
    "2048" => 18,
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
  ],

  "Posts" => [
    "5"    => 30,
    "10"   => 31,
    "25"   => 32,
    "50"   => 33,
    "100"  => 34,
    "250"  => 35,
    "500"  => 36,
    "1000" => 37,
    "2500" => 38,
  ]
];
ENV::setPub(
    "AUTOMATED_BADGE_IDS",
    $env->convert($AutomatedBadgeIDs)
);

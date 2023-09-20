<?php

declare(strict_types=1);


/**
 * private app keys
 *
 * copy me to private.php
 */

# database key for encrypting sensitive data
match ($env->environment) {
    "production" => ENV::setPriv("databaseKey", ""),
    "development" => ENV::setPriv("databaseKey", ""),
    default => throw new Exception("invalid environment"),
};


# pre-shared key for generating hmacs for the image proxy
match ($env->environment) {
    "production" => ENV::setPriv("imagePsk", ""),
    "development" => ENV::setPriv("imagePsk", ""),
    default => throw new Exception("invalid environment"),
};


# currently used for api token auth
match ($env->environment) {
    "production" => ENV::setPriv("siteCryptoKey", ""),
    "development" => ENV::setPriv("siteCryptoKey", ""),
    default => throw new Exception("invalid environment"),
};


# alphanumeric random key: the scheduler argument
match ($env->environment) {
    "production" => ENV::setPriv("scheduleKey", ""),
    "development" => ENV::setPriv("scheduleKey", ""),
    default => throw new Exception("invalid environment"),
};


# used for generating unique rss auth keys
match ($env->environment) {
    "production" => ENV::setPriv("rssHash", ""),
    "development" => ENV::setPriv("rssHash", ""),
    default => throw new Exception("invalid environment"),
};


# hashed with the sessionId for internal api calls
match ($env->environment) {
    "production" => ENV::setPriv("siteApiSecret", ""),
    "development" => ENV::setPriv("siteApiSecret", ""),
    default => throw new Exception("invalid environment"),
};


/**
 * database
 */

# enable database replication support
match ($env->environment) {
    "production" => $env->enableDatabaseReplication = true,
    "development" => $env->enableDatabaseReplication = true,
    default => throw new Exception("invalid environment"),
};


# production
if ($env->environment === "production") {
    # source: or the settings for only one database
    ENV::setPriv("databaseSource", [
        "host" => "",
        "port" => 3306,
        "socket" => null,

        "username" => "",
        "passphrase" => "",

        "database" => "",
        "charset" => "",
    ]);

    # replicas: array of structures as above
    ENV::setPriv("databaseReplicas", [
        "someLabel" => [
            "host" => "",
            "port" => 3306,
            "socket" => null,

            "username" => "",
            "passphrase" => "",

            "database" => "",
            "charset" => "",
        ],

        /*
        "anotherLabel" => [
            "host" => "",
            "port" => 3306,
            "socket" => null,

            "username" => "",
            "passphrase" => "",

            "database" => "",
            "charset" => "",
        ],
        */
    ]);
}


# development
if ($env->environment === "development") {
    ENV::setPriv("databaseSource", [
        "host" => "",
        "port" => 3306,
        "socket" => null,

        "username" => "",
        "passphrase" => "",

        "database" => "",
        "charset" => "",
    ]);

    ENV::setPriv("databaseReplicas", [
         "someLabel" => [
            "host" => "",
            "port" => 3306,
            "socket" => null,

            "username" => "",
            "passphrase" => "",

            "database" => "",
            "charset" => "",
        ],
    ]);
}


/**
 * redis cache
 */

# algorithm for hashed cache keys
match ($env->environment) {
    "production" => $env->cacheAlgorithm = "sha3-512",
    "development" => $env->cacheAlgorithm = "sha3-512",
    default => throw new Exception("invalid environment"),
};


# enable cluster support
match ($env->environment) {
    "production" => $env->enableRedisCluster = true,
    "development" => $env->enableRedisCluster = true,
    default => throw new Exception("invalid environment"),
};


# this should be an array of at least three "host:port" strings
# https://redis.io/docs/management/scaling/#create-a-redis-cluster
ENV::setPriv("redisNodes", [
    "",
    "",
    "",
]);

# single server (not a cluster)
match ($env->environment) {
    "production" => ENV::setPriv("redisHost", ""),
    "development" => ENV::setPriv("redisHost", ""),
    default => throw new Exception("invalid environment"),
};


# port
match ($env->environment) {
    "production" => ENV::setPriv("redisPort", 6379),
    "development" => ENV::setPriv("redisPort", 6379),
    default => throw new Exception("invalid environment"),
};


# username
match ($env->environment) {
    "production" => ENV::setPriv("redisUsername", ""),
    "development" => ENV::setPriv("redisUsername", ""),
    default => throw new Exception("invalid environment"),
};


# passphrase
match ($env->environment) {
    "production" => ENV::setPriv("redisPassphrase", ""),
    "development" => ENV::setPriv("redisPassphrase", ""),
    default => throw new Exception("invalid environment"),
};


/**
 * tracker
 *
 * @see http://bittorrent.org/beps/bep_0012.html
 */

# tracker host, e.g., 0.0.0.0
match ($env->environment) {
    "production" => ENV::setPriv("trackerHost", ""),
    "development" => ENV::setPriv("trackerHost", ""),
    default => throw new Exception("invalid environment"),
};


# tracker port, e.g., 34000
match ($env->environment) {
    "production" => ENV::setPriv("trackerPort", 34000),
    "development" => ENV::setPriv("trackerPort", 34000),
    default => throw new Exception("invalid environment"),
};


# must be 32 alphanumeric characters and match site_password in ocelot.conf
match ($env->environment) {
    "production" => ENV::setPriv("trackerSecret", ""),
    "development" => ENV::setPriv("trackerSecret", ""),
    default => throw new Exception("invalid environment"),
};


# must be 32 alphanumeric characters and match report_password in ocelot.conf
match ($env->environment) {
    "production" => ENV::setPriv("trackerReportKey", ""),
    "development" => ENV::setPriv("trackerReportKey", ""),
    default => throw new Exception("invalid environment"),
};


# production
if ($env->environment === "production") {
    $env->ANNOUNCE_URLS = $env->convert([
        [ # tier 1
            "",
        ],
        [ # tier 2
            #"udp://tracker.coppersurfer.tk:6969/announce",
            #"udp://tracker.cyberia.is:6969/announce",
            #"udp://tracker.leechers-paradise.org:6969/announce",
        ],
    ]);

    # REMOVE ME
    define("ANNOUNCE_URLS", [
        [ # tier 1
          "",
        ],
        [ # tier 2
        ],
    ]);
    # REMOVE ME
}


# development
if ($env->environment === "development") {
    $env->ANNOUNCE_URLS = $env->convert([
        [ # tier 1
            "",
        ],
        [ # tier 2
            #"udp://tracker.coppersurfer.tk:6969/announce",
            #"udp://tracker.cyberia.is:6969/announce",
            #"udp://tracker.leechers-paradise.org:6969/announce",
        ],
    ]);

    # REMOVE ME
    define("ANNOUNCE_URLS", [
        [ # tier 1
          "",
        ],
        [ # tier 2
        ],
    ]);
    # REMOVE ME
}


/**
 * manticore search engine
 */

# host
match ($env->environment) {
    "production" => ENV::setPriv("manticoreHost", ""),
    "development" => ENV::setPriv("manticoreHost", ""),
    default => throw new Exception("invalid environment"),
};


# port
match ($env->environment) {
    "production" => ENV::setPriv("manticorePort", 9306),
    "development" => ENV::setPriv("manticorePort", 9306),
    default => throw new Exception("invalid environment"),
};


# socket
match ($env->environment) {
    "production" => ENV::setPriv("manticoreSocket", null),
    "development" => ENV::setPriv("manticoreSocket", null),
    default => throw new Exception("invalid environment"),
};


# must be <= server max_matches (default 1000)
match ($env->environment) {
    "production" => ENV::setPriv("manticoreMaxMatches", 1000),
    "development" => ENV::setPriv("manticoreMaxMatches", 1000),
    default => throw new Exception("invalid environment"),
};


/**
 * site email
 */

# attempt to send email from the site
match ($env->environment) {
    "production" => $env->enableSiteEmail = true,
    "development" => $env->enableSiteEmail = true,
    default => throw new Exception("invalid environment"),
};


# host
match ($env->environment) {
    "production" => ENV::setPriv("emailHost", ""),
    "development" => ENV::setPriv("emailHost", ""),
    default => throw new Exception("invalid environment"),
};


# port
match ($env->environment) {
    "production" => ENV::setPriv("emailPort", 587),
    "development" => ENV::setPriv("emailPort", 587),
    default => throw new Exception("invalid environment"),
};


# username
match ($env->environment) {
    "production" => ENV::setPriv("emailUsername", ""),
    "development" => ENV::setPriv("emailUsername", ""),
    default => throw new Exception("invalid environment"),
};


# passphrase
match ($env->environment) {
    "production" => ENV::setPriv("emailPassphrase", ""),
    "development" => ENV::setPriv("emailPassphrase", ""),
    default => throw new Exception("invalid environment"),
};


/**
 * plausible stats api
 *
 * @see https://plausible.io/docs/stats-api
 */

# enable or disable plausible stats
match ($env->environment) {
    "production" => $env->enablePlausible = true,
    "development" => $env->enablePlausible = true,
    default => throw new Exception("invalid environment"),
};


# base uri for api calls
match ($env->environment) {
    "production" => $env->plausibleUri = "",
    "development" => $env->plausibleUri = "",
    default => throw new Exception("invalid environment"),
};


# api key
match ($env->environment) {
    "production" => ENV::setPriv("plausibleKey", ""),
    "development" => ENV::setPriv("plausibleKey", ""),
    default => throw new Exception("invalid environment"),
};


/**
 * slack announce
 *
 * @see https://slack.com/help/articles/115005265063-Incoming-webhooks-for-Slack
 */

# production
if ($env->environment === "production") {
    ENV::setPriv(
        "slackWebhooks",
        [
            "announce" => "",
            "debug" => "",
            "requests" => "",
        ]
    );
}


# development
if ($env->environment === "development") {
    ENV::setPriv(
        "slackWebhooks",
        [
            "announce" => "",
            "debug" => "",
            "requests" => "",
        ]
    );
}


/**
 * discourse integration
 *
 * @see https://docs.discourse.org
 */

# enable or disable most social features
match ($env->environment) {
    "production" => $env->enableDiscourse = true,
    "development" => $env->enableDiscourse = true,
    default => throw new Exception("invalid environment"),
};


# base uri for api calls
match ($env->environment) {
    "production" => $env->discourseUri = "",
    "development" => $env->discourseUri = "",
    default => throw new Exception("invalid environment"),
};


# api key
match ($env->environment) {
    "production" => ENV::setPriv("discourseKey", ""),
    "development" => ENV::setPriv("discourseKey", ""),
    default => throw new Exception("invalid environment"),
};


# discourse connect shared secret
# see https://meta.discourse.org/t/discourseconnect-official-single-sign-on-for-discourse-sso/13045
match ($env->environment) {
    "production" => ENV::setPriv("connectSecret", ""),
    "development" => ENV::setPriv("connectSecret", ""),
    default => throw new Exception("invalid environment"),
};


# discourse forum categories
$env->discourseCategories = $env->convert([
    # [id, slug]
    2 => "site-feedback",
    3 => "staff",
    4 => "general",

    # [slug, id]
    "site-feedback" => 2,
    "staff" => 3,
    "general" => 4,
]);


/**
 * openAi api
 *
 * @see https://platform.openai.com/docs/api-reference
 */

# enable or disable the integration
match ($env->environment) {
    "production" => $env->enableOpenAi = true,
    "development" => $env->enableOpenAi = true,
    default => throw new Exception("invalid environment"),
};


# secret key and organization id
if ($env->environment === "production") {
    ENV::setPriv(
        "openAiApi",
        [
            "secretKey" => "",
            "organizationId" => "",
        ]
    );
}


# development
if ($env->environment === "development") {
    ENV::setPriv(
        "openAiApi",
        [
            "secretKey" => "",
            "organizationId" => "",
        ]
    );
}


/**
 * twitter api
 *
 * @see https://developer.twitter.com/en/docs/twitter-api
 */

# enable or disable the integration
match ($env->environment) {
    "production" => $env->enableTwitter = true,
    "development" => $env->enableTwitter = true,
    default => throw new Exception("invalid environment"),
};


# secret key and organization id
if ($env->environment === "production") {
    ENV::setPriv(
        "twitterApi",
        [
            "consumerKey" => "",
            "consumerSecret" => "",
            "bearerToken" => "",
            "accessToken" => "",
            "accessTokenSecret" => "",
        ]
    );
}


# development
if ($env->environment === "development") {
    ENV::setPriv(
        "twitterApi",
        [
            "consumerKey" => "",
            "consumerSecret" => "",
            "bearerToken" => "",
            "accessToken" => "",
            "accessTokenSecret" => "",
        ]
    );
}

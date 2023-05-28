<?php

declare(strict_types=1);


/**
 * private app keys
 *
 * Separate keys for development and production.
 * Increased security and protection against config overwrites.
 */

# pre-shared key for generating hmacs for the image proxy
ENV::setPriv("imagePsk", "");

# production
if (!$env->dev) {
    # currently used for API token auth
    ENV::setPriv("siteCryptoKey", "");

    # alphanumeric random key: the scheduler argument
    ENV::setPriv("scheduleKey", "");

    # used for generating unique RSS auth key
    ENV::setPriv("rssHash", "");

    # hashed with the sessionId for internal api calls
    ENV::setPriv("siteApiSecret", file_get_contents("{$env->webRoot}/siteApiSecret.txt"));
}

# development
else {
    ENV::setPriv("siteCryptoKey", "");
    ENV::setPriv("scheduleKey", "");
    ENV::setPriv("rssHash", "");
    ENV::setPriv("siteApiSecret", file_get_contents("{$env->webRoot}/siteApiSecret.txt"));
}


/**
 * database
 */

# enable database replication support
ENV::setPub("databaseReplicationEnabled", true);

# production
if (!$env->dev) {
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
        "manticore" => [
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
else {
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
        "manticore" => [
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
 * cache
 */

# algorithm for hashed cache keys
ENV::setPub("cacheAlgorithm", "sha3-512");

# enable redis cluster support
ENV::setPub("redisClusterEnabled", true);

# this should be an array of at least three "host:port" strings
# https://redis.io/docs/management/scaling/#create-a-redis-cluster
ENV::setPriv("redisNodes", [
    "", # dev.torrents.bio
    "", # web.torernts.bio
    "", # database.torrents.bio
    "", # manticore.torrents.bio
]);

# single redis server (not a cluster)
ENV::setPriv("redisHost", "");
ENV::setPriv("redisPort", 6379);

# redis authentication
ENV::setPriv("redisUsername", "");
ENV::setPriv("redisPassphrase", "");


/**
 * tracker
 */

# ocelot connection, e.g., 0.0.0.0
ENV::setPriv("trackerHost", "");

# production
if (!$env->dev) {
    ENV::setPriv("trackerPort", 34000);

    # must be 32 alphanumeric characters and match site_password in ocelot.conf
    ENV::setPriv("trackerSecret", "");

    # must be 32 alphanumeric characters and match report_password in ocelot.conf
    ENV::setPriv("trackerReportKey", "");
}

# development
else {
    ENV::setPriv("trackerPort", 34001);
    ENV::setPriv("trackerSecret", "");
    ENV::setPriv("trackerReportKey", "");
}


/**
 * manticore search engine
 */

ENV::setPriv("manticoreHost", "");
ENV::setPriv("manticorePort", 9306);
ENV::setPriv("manticoreSocket", null);
ENV::setPriv("manticoreMaxMatches", 1000); # must be <= server max_matches (default 1000)


/**
 * site email
 */

ENV::setPriv("emailHost", "");
ENV::setPriv("emailPort", 587);

ENV::setPriv("emailUsername", "");
ENV::setPriv("emailPassphrase", "");


/**
 * Plausible Stats API
 * @see https://plausible.io/docs/stats-api
 */

# enable or disable plausible stats
ENV::setPub("enablePlausible", true);

# base URI for API calls
ENV::setPub("plausibleUri", "");

# production
if (!$env->dev) {
    ENV::setPriv("plausibleKey", "");
}

# development
else {
    ENV::setPriv("plausibleKey", "");
}


/**
 * slack announce
 * @see https://slack.com/help/articles/115005265063-Incoming-webhooks-for-Slack
 */

ENV::setPriv(
    "slackWebhooks",
    [
        "announce" => "",
        "debug" => "",
        "requests" => "",
    ]
);


/**
 * discourse
 * @see https://docs.discourse.org
 */

# enable or disable most social features
ENV::setPub("enableDiscourse", true);

# discourse forum categories
$discourseCategories = [
    # [id, slug]
    2 => "site-feedback",
    3 => "staff",
    4 => "general",

    # [slug, id]
    "site-feedback" => 2,
    "staff" => 3,
    "general" => 4,
];
ENV::setPub(
    "discourseCategories",
    $env->convert($discourseCategories)
);

/*
# discourse forum categories
$discourseCategories = [
    # [id, slug]
    1 => "uncategorized",
    3 => "staff",
    5 => "blog",
    6 => "comments",
    7 => "marketplace",
    8 => "news",
    9 => "wiki",

    # [slug, id]
    "uncategorized" => 1,
    "staff" => 3,
    "blog" => 5,
    "comments" => 6,
    "marketplace" => 7,
    "news" => 8,
    "wiki" => 9,
];
ENV::setPub(
    "discourseCategories",
    $env->convert($discourseCategories)
);
*/

# base URI for API calls
ENV::setPub("discourseUri", "");

# production
if (!$env->dev) {
    ENV::setPriv("discourseKey", "");
}

# development
else {
    ENV::setPriv("discourseKey", "");
}

# discourse connect shared secret
# see https://meta.discourse.org/t/discourseconnect-official-single-sign-on-for-discourse-sso/13045
ENV::setPriv("connectSecret", "");


/**
 * OpenAI API
 */

# enable or disable the integration
ENV::setPub("enableOpenAi", true);

# secret key and organization id
ENV::setPriv(
    "openAiApi",
    [
        "secretKey" => "",
        "organizationId" => "",
    ]
);


/**
 * Twitter API
 */

# enable or disable the integration
ENV::setPub("enableTwitter", true);

# secret key and organization id
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

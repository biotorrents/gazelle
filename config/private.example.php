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

# common info
ENV::setPriv("sqlHost", "");
ENV::setPriv("sqlPort", 3306);

# leave set even if using TCP due to DB::class strict mode
ENV::setPriv("sqlSocket", "");

# TLS client certs
ENV::setPriv("sqlCert", "");
ENV::setPriv("sqlKey", "");
ENV::setPriv("sqlCertAuthority", "");

# production
if (!$app->env->dev) {
    ENV::setPriv("sqlDatabase", "");
    ENV::setPriv("sqlUsername", "");
    ENV::setPriv("sqlPassphrase", "");
}

# development
else {
    ENV::setPriv("sqlDatabase", "");
    ENV::setPriv("sqlUsername", "");
    ENV::setPriv("sqlPassphrase", "");
}


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

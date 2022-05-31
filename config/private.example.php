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
if (!$app->env->dev) {
    # currently used for API token auth
    ENV::setPriv("siteCryptoKey", "");
  
    # alphanumeric random key: the scheduler argument
    ENV::setPriv("scheduleKey", "");
  
    # used for generating unique RSS auth key
    ENV::setPriv("rssHash", "");

    # used for getting resources via Json->fetch
    ENV::setPriv("siteApiKey", "");
}

# development
else {
    ENV::setPriv("siteCryptoKey", "");
    ENV::setPriv("scheduleKey", "");
    ENV::setPriv("rssHash", "");
    ENV::setPriv("siteApiKey", "");
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
if (!$app->env->dev) {
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

# base URI for API calls
ENV::setPub("plausibleUri", "");

# production
if (!$app->env->dev) {
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

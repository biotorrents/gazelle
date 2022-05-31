<?php
declare(strict_types=1);

/**
 * App keys
 *
 * Separate keys for development and production.
 * Increased security and protection against config overwrites.
 */

# Pre-shared key for generating hmacs for the image proxy
ENV::setPriv('IMAGE_PSK', '');

 # Production
if (!$env->dev) {
    # Unused in OT Gazelle. Currently used for API token auth
    ENV::setPriv('ENCKEY', '');
  
    # Alphanumeric random key. This key must be the argument to schedule.php for the schedule to work
    ENV::setPriv('SCHEDULE_KEY', '');
  
    # Random key. Used for generating unique RSS auth key
    ENV::setPriv('RSS_HASH', '');

    # System API key. Used for getting resources via Json->fetch()
    ENV::setPriv('SELF_API', '');
}

# Development
else {
    ENV::setPriv('ENCKEY', '');
    ENV::setPriv('SCHEDULE_KEY', '');
    ENV::setPriv('RSS_HASH', '');
    ENV::setPriv('SELF_API', '');
}


/**
 * Database
 */

# Common info
ENV::setPriv('SQL_HOST', '10.0.0.3');
ENV::setPriv('SQL_PORT', 3306);

# Leave set even if using TCP due to DB::class strict mode
ENV::setPriv('SQL_SOCK', '/var/run/mysqld/mysqld.sock');

# TLS client certs
ENV::setPriv('SQL_CERT', "/var/www/tls-keys/client-cert-ohm.pem");
ENV::setPriv('SQL_KEY', "/var/www/tls-keys/client-key-ohm.pem");
ENV::setPriv('SQL_CA', "/var/www/tls-keys/ca.pem");

/*
ENV::setPriv('SQL_CERT', "$env->webRoot/tls-keys/client-cert-ohm.pem");
ENV::setPriv('SQL_KEY', "$env->webRoot/tls-keys/client-key-ohm.pem");
ENV::setPriv('SQL_CA', "$env->webRoot/tls-keys/ca.pem");
*/

# Production
if (!$env->dev) {
    ENV::setPriv('SQL_DB', 'gazelle_production');
    ENV::setPriv('SQL_USER', 'gazelle_production');
    ENV::setPriv('SQL_PASS', '');
}

# Development
else {
    ENV::setPriv('SQL_DB', 'gazelle_development');
    ENV::setPriv('SQL_USER', 'gazelle_development');
    ENV::setPriv('SQL_PASS', '');
}


/**
 * Tracker
 */

# Ocelot connection, e.g., 0.0.0.0
ENV::setPriv('TRACKER_HOST', '0.0.0.0');

 # Production
if (!$env->dev) {
    ENV::setPriv('TRACKER_PORT', 34000);
  
    # Must be 32 alphanumeric characters and match site_password in ocelot.conf
    ENV::setPriv('TRACKER_SECRET', '');

    # Must be 32 alphanumeric characters and match report_password in ocelot.conf
    ENV::setPriv('TRACKER_REPORTKEY', '');
}

# Development
else {
    ENV::setPriv('TRACKER_PORT', 34001);
    ENV::setPriv('TRACKER_SECRET', '');
    ENV::setPriv('TRACKER_REPORTKEY', '');
}


/**
 * Plausible Stats API
 * @see https://plausible.io/docs/stats-api
 */

# Base URI for API calls
ENV::setPub('PLAUSIBLE_URI', 'https://stats.torrents.bio/api/v1');

# Production
if (!$env->dev) {
    ENV::setPriv('PLAUSIBLE_KEY', '');
}

# Development
else {
    ENV::setPriv('PLAUSIBLE_KEY', '');
}

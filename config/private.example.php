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
if (!$ENV->DEV) {
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
ENV::setPriv('SQLHOST', '10.0.0.3');
ENV::setPriv('SQLPORT', 3306);

# Leave set even if using TCP due to DB strict mode
ENV::setPriv('SQLSOCK', '/var/run/mysqld/mysqld.sock');

# TLS client certs
ENV::setPriv('SQL_CERT', "/var/www/tls-keys/client-cert-ohm.pem");
ENV::setPriv('SQL_KEY', "/var/www/tls-keys/client-key-ohm.pem");
ENV::setPriv('SQL_CA', "/var/www/tls-keys/ca.pem");

/*
ENV::setPriv('SQL_CERT', "$ENV->WEB_ROOT/tls-keys/client-cert-ohm.pem");
ENV::setPriv('SQL_KEY', "$ENV->WEB_ROOT/tls-keys/client-key-ohm.pem");
ENV::setPriv('SQL_CA', "$ENV->WEB_ROOT/tls-keys/ca.pem");
*/

 # Production
 if (!$ENV->DEV) {
     ENV::setPriv('SQLDB', 'gazelle_production');
     ENV::setPriv('SQLLOGIN', 'gazelle_production');
     ENV::setPriv('SQLPASS', '');
 }

# Development
else {
    ENV::setPriv('SQLDB', 'gazelle_development');
    ENV::setPriv('SQLLOGIN', 'gazelle_development');
    ENV::setPriv('SQLPASS', '');
}


/**
 * Tracker
 */

# Ocelot connection, e.g., 0.0.0.0
ENV::setPriv('TRACKER_HOST', '0.0.0.0');

 # Production
if (!$ENV->DEV) {
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

<?php

declare(strict_types=1);


/**
 * phinx config
 */

# bootstrap the app
require_once __DIR__."/bootstrap/cli.php";

# use the source database for this
$databaseSource = $app->env->getPriv("databaseSource");

# config array
return
[
    "paths" => [
        "migrations" => "{$app->env->serverRoot}/database/migrations",
        "seeds" => "{$app->env->serverRoot}/database/seeds",
    ],

    "environments" => [
        "default_migration_table" => "phinxLog",
        "default_environment" => "development",

        # production and development are the same in the config
        # they transparently switch databases on $app->env->dev
        "production" => [
            "adapter" => "mysql",
            "host" => $databaseSource["host"],
            "name" => $databaseSource["database"],
            "user" => $databaseSource["username"],
            "pass" => $databaseSource["passphrase"],
            "port" => $databaseSource["port"],
            "charset" => $databaseSource["charset"],
        ],

        "development" => [
            "adapter" => "mysql",
            "host" => $databaseSource["host"],
            "name" => $databaseSource["database"],
            "user" => $databaseSource["username"],
            "pass" => $databaseSource["passphrase"],
            "port" => $databaseSource["port"],
            "charset" => $databaseSource["charset"],
        ],

        /*
        "testing" => [
            "adapter" => "mysql",
            "host" => "localhost",
            "name" => "testing_db",
            "user" => "root",
            "pass" => "",
            "port" => "3306",
            "charset" => "utf8",
        ],
        */
    ],

    "version_order" => "creation",
];

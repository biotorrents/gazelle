<?php

declare(strict_types=1);


/**
 * Phinx config
 */

# composer autoload
require_once __DIR__."/vendor/autoload.php";

# load the app
$app = App::go();

# config array
return
[
    "paths" => [
        "migrations" => "{$app->env->serverRoot}/database/migrations",
        "seeds" => "{$app->env->serverRoot}/database/seeds",
    ],

    "environments" => [
        "default_migration_table" => "phinxlog",
        "default_environment" => "development",

        # production and development are the same in the config
        # they transparently switch databases on $app->env->dev
        "production" => [
            "adapter" => "mysql",
            "host" => $app->env->getPriv("sqlHost"),
            "name" => $app->env->getPriv("sqlDatabase"),
            "user" => $app->env->getPriv("sqlUsername"),
            "pass" => $app->env->getPriv("sqlPassphrase"),
            "port" => $app->env->getPriv("sqlPort"),
            "charset" => "utf8mb4",
        ],

        "development" => [
            "adapter" => "mysql",
            "host" => $app->env->getPriv("sqlHost"),
            "name" => $app->env->getPriv("sqlDatabase"),
            "user" => $app->env->getPriv("sqlUsername"),
            "pass" => $app->env->getPriv("sqlPassphrase"),
            "port" => $app->env->getPriv("sqlPort"),
            "charset" => "utf8mb4",
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

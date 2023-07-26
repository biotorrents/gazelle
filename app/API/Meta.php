<?php

declare(strict_types=1);


/**
 * Gazelle\API\Meta
 */

namespace Gazelle\API;

class Meta extends Base
{
    /**
     * manifest
     */
    public static function manifest(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        try {
            $data = \Gazelle\App::manifest();

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * ontology
     */
    public static function ontology(): void
    {
        $app = \Gazelle\App::go();

        self::validatePermissions($_SESSION["token"]["id"], ["read"]);

        try {
            $data = $app->env->CATS;

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class

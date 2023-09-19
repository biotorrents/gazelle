<?php

declare(strict_types=1);


/**
 * Gazelle\Api\BonusPoints
 */

namespace Gazelle\Api;

class BonusPoints extends Base
{
    /**
     * randomFreeleech
     */
    public static function randomFreeleech(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create", "update"]);

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->randomFreeleech();

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class

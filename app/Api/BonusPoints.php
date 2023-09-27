<?php

declare(strict_types=1);


/**
 * Gazelle\Api\BonusPoints
 */

namespace Gazelle\Api;

class BonusPoints extends Base
{
    /**
     * pointsToUpload
     */
    public static function pointsToUpload(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["amount"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->pointsToUpload($request["amount"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * uploadToPoints
     */
    public static function uploadToPoints(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["amount"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->uploadToPoints($request["amount"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** torrents */


    /**
     * randomFreeleech
     */
    public static function randomFreeleech(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->randomFreeleech();

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * specificFreeleech
     */
    public static function specificFreeleech(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["identifier"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->specificFreeleech($request["identifier"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * freeleechToken
     */
    public static function freeleechToken(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->freeleechToken();

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * neutralLeechTag
     */
    public static function neutralLeechTag(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["identifier"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->neutralLeechTag($request["identifier"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * freeleechTag
     */
    public static function freeleechTag(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["identifier"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->freeleechTag($request["identifier"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * neutralLeechCategory
     */
    public static function neutralLeechCategory(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["identifier"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->neutralLeechCategory($request["identifier"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * freeleechCategory
     */
    public static function freeleechCategory(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["identifier"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->freeleechCategory($request["identifier"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /** user profile */


    /**
     * personalCollage
     */
    public static function personalCollage(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->personalCollage();

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * invite
     */
    public static function invite(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->invite();

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * customTitle
     */
    public static function customTitle(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create", "update"]);

        $request = \Http::json();
        $request["customTitle"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->customTitle($request["customTitle"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * createGlitchUsername
     */
    public static function createGlitchUsername(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->glitchUsername(isDelete: false);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * deleteGlitchUsername
     */
    public static function deleteGlitchUsername(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["delete"]);

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->glitchUsername(isDelete: true);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * snowflakeProfile
     */
    public static function snowflakeProfile(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create", "update"]);

        self::failure(400, "not implemented");

        /*
        $request = \Http::json();
        $request["snowflake"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->snowflakeProfile($request["snowflake"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
        */
    }


    /** badges */


    /**
     * sequentialBadge
     */
    public static function sequentialBadge(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->sequentialBadge();

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * lotteryBadge
     */
    public static function lotteryBadge(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["amount"] ??= null;
        $request["ticket"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->lotteryBadge($request["amount"], $request["ticket"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * auctionBadge
     */
    public static function auctionBadge(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["amount"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->auctionBadge($request["amount"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * coinBadge
     */
    public static function coinBadge(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $request = \Http::json();
        $request["amount"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->coinBadge($request["amount"]);

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }


    /**
     * randomBadge
     */
    public static function randomBadge(): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        try {
            $bonusPoints = new \Gazelle\BonusPoints();
            $data = $bonusPoints->randomBadge();

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class

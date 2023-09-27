<?php

declare(strict_types=1);


/**
 * Gazelle\Api\BonusPoints
 */

namespace Gazelle\Api;

class BonusPoints extends Base
{
    /**
     * checkout
     */
    public static function checkout(string $item): void
    {
        self::validatePermissions($_SESSION["token"]["id"], ["create"]);

        $item ??= null;
        if (!$item) {
            self::failure(400, "no item selected");
        }

        $request = \Http::json();
        $request["amount"] ??= null;
        $request["delete"] ??= null;
        $request["emoji"] ??= null;
        $request["identifier"] ??= null;
        $request["ticket"] ??= null;
        $request["title"] ??= null;
        $request["update"] ??= null;

        try {
            $bonusPoints = new \Gazelle\BonusPoints();

            $data = match ($item) {
                "pointsToUpload" => $bonusPoints->pointsToUpload($request["amount"]),
                "uploadToPoints" => $bonusPoints->uploadToPoints($request["amount"]),

                "randomFreeleech" => $bonusPoints->randomFreeleech(),
                "specificFreeleech" => $bonusPoints->specificFreeleech($request["identifier"]),
                "freeleechToken" => $bonusPoints->freeleechToken(),
                "neutralLeechTag" => $bonusPoints->neutralLeechTag($request["identifier"]),
                "freeleechTag" => $bonusPoints->freeleechTag($request["identifier"]),
                "neutralLeechCategory" => $bonusPoints->neutralLeechCategory($request["identifier"]),
                "freeleechCategory" => $bonusPoints->freeleechCategory($request["identifier"]),

                "personalCollage" => $bonusPoints->personalCollage(),
                "invite" => $bonusPoints->invite(),
                "customTitle" => $bonusPoints->customTitle($request["title"]),
                "glitchUsername" => $bonusPoints->glitchUsername($request["delete"]),
                "snowflakeProfile" => $bonusPoints->snowflakeProfile($request["emoji"]),

                "sequentialBadge" => $bonusPoints->sequentialBadge(),
                "lotteryBadge" => $bonusPoints->lotteryBadge($request["amount"], $request["ticket"]),
                "auctionBadge" => $bonusPoints->auctionBadge($request["amount"]),
                "coinBadge" => $bonusPoints->coinBadge($request["amount"]),
                "randomBadge" => $bonusPoints->randomBadge(),
            };

            self::success(200, $data);
        } catch (\Throwable $e) {
            self::failure(400, $e->getMessage());
        }
    }
} # class

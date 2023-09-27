<?php

declare(strict_types=1);


/**
 * bonus points
 */

# pointsToUpload
Flight::route("POST /api/bonusPoints/pointsToUpload", ["Gazelle\Api\BonusPoints", "pointsToUpload"]);


# uploadToPoints
Flight::route("POST /api/bonusPoints/uploadToPoints", ["Gazelle\Api\BonusPoints", "uploadToPoints"]);


/** torrents */


# randomFreeleech
Flight::route("POST /api/bonusPoints/randomFreeleech", ["Gazelle\Api\BonusPoints", "randomFreeleech"]);


# specificFreeleech
Flight::route("POST /api/bonusPoints/specificFreeleech", ["Gazelle\Api\BonusPoints", "specificFreeleech"]);


# freeleechToken
Flight::route("POST /api/bonusPoints/freeleechToken", ["Gazelle\Api\BonusPoints", "freeleechToken"]);


# neutralLeechTag
Flight::route("POST /api/bonusPoints/neutralLeechTag", ["Gazelle\Api\BonusPoints", "neutralLeechTag"]);


# freeleechTag
Flight::route("POST /api/bonusPoints/freeleechTag", ["Gazelle\Api\BonusPoints", "freeleechTag"]);


# neutralLeechCategory
Flight::route("POST /api/bonusPoints/neutralLeechCategory", ["Gazelle\Api\BonusPoints", "neutralLeechCategory"]);


# freeleechCategory
Flight::route("POST /api/bonusPoints/freeleechCategory", ["Gazelle\Api\BonusPoints", "freeleechCategory"]);


/** user profile */


# personalCollage
Flight::route("POST /api/bonusPoints/personalCollage", ["Gazelle\Api\BonusPoints", "personalCollage"]);


# invite
Flight::route("POST /api/bonusPoints/invite", ["Gazelle\Api\BonusPoints", "invite"]);


# customTitle
Flight::route("POST /api/bonusPoints/customTitle", ["Gazelle\Api\BonusPoints", "customTitle"]);
Flight::route("PUT /api/bonusPoints/customTitle", ["Gazelle\Api\BonusPoints", "customTitle"]);


# glitchUsername
Flight::route("POST /api/bonusPoints/glitchUsername", ["Gazelle\Api\BonusPoints", "createGlitchUsername"]);
Flight::route("DELETE /api/bonusPoints/glitchUsername", ["Gazelle\Api\BonusPoints", "deleteGlitchUsername"]);


/*
# snowflakeProfile
Flight::route("POST /api/bonusPoints/snowflakeProfile", ["Gazelle\Api\BonusPoints", "snowflakeProfile"]);
Flight::route("PUT /api/bonusPoints/snowflakeProfile", ["Gazelle\Api\BonusPoints", "snowflakeProfile"]);
Flight::route("DELETE /api/bonusPoints/snowflakeProfile", ["Gazelle\Api\BonusPoints", "snowflakeProfile"]);
*/


/** badges */


# sequentialBadge
Flight::route("POST /api/bonusPoints/sequentialBadge", ["Gazelle\Api\BonusPoints", "sequentialBadge"]);


# lotteryBadge
Flight::route("POST /api/bonusPoints/lotteryBadge", ["Gazelle\Api\BonusPoints", "lotteryBadge"]);


# auctionBadge
Flight::route("POST /api/bonusPoints/auctionBadge", ["Gazelle\Api\BonusPoints", "auctionBadge"]);


# coinBadge
Flight::route("POST /api/bonusPoints/coinBadge", ["Gazelle\Api\BonusPoints", "coinBadge"]);


# randomBadge
Flight::route("POST /api/bonusPoints/randomBadge", ["Gazelle\Api\BonusPoints", "randomBadge"]);

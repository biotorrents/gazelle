<?php

declare(strict_types=1);


/**
 * bonus points
 */

# checkout
Flight::route("POST /api/store/checkout/@item", ["Gazelle\Api\BonusPoints", "checkout"]);

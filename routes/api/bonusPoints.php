<?php

declare(strict_types=1);


/**
 * bonus points
 */

# checkout
Flight::post("/api/store/checkout/@item", ["Gazelle\Api\BonusPoints", "checkout"]);

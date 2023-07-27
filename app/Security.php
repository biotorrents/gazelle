<?php

declare(strict_types=1);


/**
 * Security
 *
 * THIS IS GOING AWAY
 *
 * Designed to hold common authentication functions from various sources:
 *  - bootstrap/app.php
 *  - "Quick SQL injection check"
 */

class Security
{
    /**
     * Check integer
     *
     * Makes sure a number ID is valid,
     * e.g., a page ID requested by GET.
     */
    public static function int(mixed ...$ids)
    {
        foreach ($ids as $id) {
            return ($id !== abs(intval($id))) ?? Http::response(400);
        }
    }
}

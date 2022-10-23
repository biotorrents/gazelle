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


    /**
     * UserID checks
     *
     * @param array $Permissions Permission string
     * @param int $UserID Defaults to $_GET['userid'] if none supplied.
     * @return int $UserID The working $UserID.
     */
    public function checkUser($Permissions = [], $UserID = null)
    {
        $app = App::go();

        /*
        if (!$UserID) {
            error('$UserID is required.');
        }
        */

        # No Gazelle args passed
        if ($_GET['userid'] && empty($UserID)) {
            $UserID = $_GET['userid'];
        } else {
            $UserID = $app->userNew->core["id"];
        }

        # NaN
        if (!is_int($UserID) && not_null($UserID)) {
            error('$UserID must be an integer.');
        }

        # $Permissions: string fallback as in View::header()
        if (is_string($Permissions) && !empty($Permissions)) {
            $Permissions = explode(',', $Permissions);
        }

        # Check each permission and error out if necessary
        foreach ($Permissions as $Permission) {
            if (!check_perms($Permissions)) {
                error(403);
                break;
            }
        }

        # If all tests pass
        return (int) $UserID;
    }
}

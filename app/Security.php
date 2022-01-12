<?php
declare(strict_types = 1);

/**
 * Security
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
    public static function int(...$ids)
    {
        foreach ($ids as $id) {
            return (intval($id) < 1) ?? Http::response(400);
        }

        return true;
    }


    /**
     * Setup pitfalls
     *
     * A series of quick sanity checks during app init.
     * Previously in bootstrap/app.php.
     */
    public static function SetupPitfalls()
    {
        $ENV = ENV::go();

        # short_open_tag
        if (!ini_get('short_open_tag')) {
            error('short_open_tag != On in php.ini.');
        }

        # blake3
        if ($ENV->FEATURE_BIOPHP && !extension_loaded('blake3')) {
            error('Please install and enable the php-blake3 extension.');
        }

        # Deal with dumbasses
        if (isset($_REQUEST['info_hash']) || isset($_REQUEST['peer_id'])) {
            error(
                'd14:failure reason40:Invalid .torrent, try downloading again.e',
                $NoHTML = true
            );
        }

        return;
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
        /*
        if (!$UserID) {
            error('$UserID is required.');
        }
        */

        # No Gazelle args passed
        if ($_GET['userid'] && empty($UserID)) {
            $UserID = $_GET['userid'];
        } else {
            $UserID = G::$LoggedUser['ID'];
        }

        # NaN
        if (!is_int($UserID) && not_null($UserID)) {
            error('$UserID must be an integer.');
        }

        # $Permissions: string fallback as in View::show_header()
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

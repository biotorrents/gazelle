<?php

#declare(strict_types=1);


/**
 * LockedAccounts
 */

class LockedAccounts
{
    /**
     * lock_account
     *
     * Lock an account
     *
     * @param int $UserID The ID of the user to lock
     * @param int $Type The lock type, should be a constant value
     * @param string $Message The message to write to user notes
     * @param string $Reason The reason for the lock
     * @param int $LockedByUserID The ID of the staff member that locked $UserID's account. 0 for system
     */
    public static function lock_account($UserID, $Type, $Message, $Reason, $LockedByUserID)
    {
        $app = \Gazelle\App::go();

        if ($LockedByUserID === 0) {
            $Username = "System";
        } else {
            $app->dbOld->query("SELECT Username FROM users_main WHERE ID = '" . $LockedByUserID . "'");
            list($Username) = $app->dbOld->next_record();
        }

        $app->dbOld->query("
        INSERT INTO locked_accounts (UserID, Type)
          VALUES ('" . $UserID . "', " . $Type . ")");

        Tools::update_user_notes($UserID, sqltime() . " - " . db_string($Message) . " by $Username\nReason: " . db_string($Reason) . "\n\n");
        $app->cacheOld->delete_value("user_info_$UserID");
    }


    /**
     * unlock_account
     *
     * Unlock an account
     *
     * @param int $UserID The ID of the user to unlock
     * @param int $Type The lock type, should be a constant value. Used for database verification
     *                  to avoid deleting the wrong lock type
     * @param string $Reason The reason for unlock
     * @param int $UnlockedByUserID The ID of the staff member unlocking $UserID's account. 0 for system
     */
    public static function unlock_account($UserID, $Type, $Message, $Reason, $UnlockedByUserID)
    {
        $app = \Gazelle\App::go();

        if ($UnlockedByUserID === 0) {
            $Username = "System";
        } else {
            $app->dbOld->query("SELECT Username FROM users_main WHERE ID = '" . $UnlockedByUserID . "'");
            list($Username) = $app->dbOld->next_record();
        }

        $app->dbOld->query("DELETE FROM locked_accounts WHERE UserID = '$UserID' AND Type = '". $Type ."'");

        if ($app->dbOld->affected_rows() === 1) {
            $app->cacheOld->delete_value("user_info_$UserID");
            Tools::update_user_notes($UserID, sqltime() . " - " . db_string($Message) . " by $Username\nReason: " . db_string($Reason) . "\n\n");
        }
    }
} # class

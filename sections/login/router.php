<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# endpoints go here

# start the router
#Flight::start();


/** LEGACY ROUTES */


/**
 * Login handler
 *
 * I don't know where to start here.
 * Maybe the nested conditionals from Line 34.
 * That's the whole file, after all.
 */

# Initialize
$ENV = ENV::go();
$Validate = new Validate;
$TwoFA = new \RobThree\Auth\TwoFactorAuth($ENV->SITE_NAME);
$U2F = new \u2flib_server\U2F("https://$ENV->SITE_DOMAIN");

# Fix Flight, ugh
$action = $_REQUEST['action'] ?? null;
$cache = new Cache($ENV->getPriv('MEMCACHED_SERVERS'));

# They want the disabled page
if ($action === 'disabled') {
    Http::redirect("disabled.php");
    exit;
}

# IP ban after failed logins
if (Tools::site_ban_ip($_SERVER['REMOTE_ADDR'])) {
    error('Your IP address has been banned.');
}

# Account recovery workflow
if ($action === 'recover') {
    // Recover password
    if (!empty($_REQUEST['key'])) {
        // User has entered a new password, use step 2
        $db->query("
        SELECT
          m.`ID`,
          m.`Email`,
          i.`ResetExpires`
        FROM `users_main` AS m
          INNER JOIN `users_info` AS i ON i.`UserID` = m.`ID`
          WHERE i.`ResetKey` = ?
          AND i.`ResetKey` != ''", $_REQUEST['key']);
        list($UserID, $Email, $Country, $Expires) = $db->next_record();

        if (!apcu_exists('DBKEY')) {
            error('Database not fully decrypted. Please wait for staff to fix this and try again later.');
        }

        $Email = Crypto::decrypt($Email);

        if ($UserID && strtotime($Expires) > time()) {
            // If the user has requested a password change, and his key has not expired
            $Validate->SetFields('password', '1', 'regex', 'You entered an invalid password. Any password at least 15 characters long is accepted, but a strong password is 8 characters or longer, contains at least 1 lowercase and uppercase letter, contains at least a number or symbol', array('regex' => '/(?=^.{6,}$).*$/'));
            $Validate->SetFields('verifypassword', '1', 'compare', 'Your passwords did not match.', array('comparefield' => 'password'));

            if (!empty($_REQUEST['password'])) {
                // If the user entered a password.
                // If not, $PassWasReset !== 1, success message hidden.
                $Err = $Validate->ValidateForm($_REQUEST);
                if ($Err == '') {
                    // Form validates without error, set new secret and password.
                    $db->query(
                        "
                    UPDATE
                      `users_main` AS m,
                      `users_info` AS i
                    SET
                      m.`PassHash` = ?,
                      i.`ResetKey` = '',
                      m.`LastLogin` = NOW(),
                      i.`ResetExpires` = NULL
                      WHERE m.`ID` = ?
                      AND i.`UserID` = m.`ID`",
                        Users::make_sec_hash($_REQUEST['password']),
                        $UserID
                    );

                    $PassWasReset = true;
                    $user['ID'] = $UserID; // Set $user['ID'] for logout_all_sessions() to work
                    logout_all_sessions();
                }
            }

            // Either a form asking for them to enter the password
            // Or a success message if $PassWasReset is 1
            require('recover_step2.php');
        } else {
            // Either his key has expired, or he hasn't requested a pass change at all
            if (strtotime($Expires) < time() && $UserID) {
                // If his key has expired, clear all the reset information
                $db->query("
                UPDATE users_info
                SET ResetKey = '',
                  ResetExpires = NULL
                  WHERE UserID = ?", $UserID);
                $_SESSION['reseterr'] = 'The link you were given has expired.'; // Error message to display on form
            }
            // Show him the first form (enter email address)
            Http::redirect("login.php?action=recover");
        }
    } // End step 2

    // User has not clicked the link in his email, use step 1
    else {
        # Check for DBKEY before handling user inputs
        if (!apcu_exists('DBKEY')) {
            $Err = 'Database not fully decrypted. Please wait for staff to fix this and try again';
        }

        if (!empty($_REQUEST['email'])) {
            // User has entered email and submitted form
            $Validate->SetFields('email', '1', 'email', 'You entered an invalid email address');
            $Err = $Validate->ValidateForm($_REQUEST);

            if (!$Err) {
                // Form validates correctly
                $db->query("
                SELECT
                  `Email`
                FROM
                  `users_main`
                ");

                /**
                 * Note that if any user has a blank email field,
                 * the comparison will fail and the script will 500!
                 */
                while (list($EncEmail) = $db->next_record()) {
                    if (trim(
                        strtolower($_REQUEST['email'])
                    ) === strtolower(Crypto::decrypt($EncEmail))) {
                        break; // $EncEmail is now the encrypted form of the given email from the database
                    }
                }

                $db->query("
                SELECT
                  `ID`,
                  `Username`,
                  `Email`
                FROM
                  `users_main`
                WHERE
                  `Email` = '$EncEmail'
                ");

                list($UserID, $Username, $Email) = $db->next_record();
                $Email = Crypto::decrypt($Email);

                if ($UserID) {
                    // Email exists in the database
                    // Set ResetKey, send out email, and set $Sent to 1 to show success page
                    Users::reset_password($UserID, $Username, $Email);
                    $Sent = 1; // If $Sent is 1, recover_step1.php displays a success message

                    //Log out all of the users current sessions
                    $cache->delete_value("user_info_$UserID");
                    $cache->delete_value("user_info_heavy_$UserID");
                    $cache->delete_value("user_stats_$UserID");
                    $cache->delete_value("enabled_$UserID");

                    $db->query("
                    SELECT
                      `SessionID`
                    FROM
                      `users_sessions`
                    WHERE
                      `UserID` = '$UserID'
                    ");

                    while (list($SessionID) = $db->next_record()) {
                        $cache->delete_value("session_$UserID"."_$SessionID");
                    }

                    $db->query("
                    UPDATE
                      `users_sessions`
                    SET
                      `Active` = 0
                    WHERE
                      `UserID` = '$UserID'
                      AND `Active` = 1
                    ");
                } else {
                    $Err = 'There is no user with that email address.';
                }
            }
        } elseif (!empty($_SESSION['reseterr'])) {
            // User has not entered email address, and there is an error set in session data
            // This is typically because their key has expired.
            // Stick the error into $Err so recover_step1.php can take care of it
            $Err = $_SESSION['reseterr'];
            unset($_SESSION['reseterr']);
        }

        // Either a form for the user's email address, or a success message
        require('recover_step1.php');
    }
} // End password recovery

// Normal login
else {
    $Validate->SetFields('username', true, 'regex', 'You did not enter a valid username', array('regex' => USERNAME_REGEX));
    $Validate->SetFields('password', '1', 'string', 'You entered an invalid password', array('minlength' => '15', 'maxlength' => '307200'));

    try {
        list($Attempts, $Banned) = $cache->get_value('login_attempts_'.db_string($_SERVER['REMOTE_ADDR']));
    } catch (Exception $e) {
        $cache->add_value('login_attempts_'.db_string($_SERVER['REMOTE_ADDR']), 0);
    }
    // Function to log a user's login attempt
    function log_attempt()
    {
        global $cache, $Attempts;

        $Attempts = ($Attempts ?? 0) + 1;
        $cache->cache_value('login_attempts_'.db_string($_SERVER['REMOTE_ADDR']), array($Attempts, ($Attempts > 5)), 60*60*$Attempts);
        $AllAttempts = ($cache->get_value('login_attempts')) ? false : [];
        $AllAttempts[$_SERVER['REMOTE_ADDR']] = time()+(60*60*$Attempts);
        foreach ($AllAttempts as $IP => $Time) {
            if ($Time < time()) {
                unset($AllAttempts[$IP]);
            }
        }
        $cache->cache_value('login_attempts', $AllAttempts, 0);
    }

    // If user has submitted form
    if (isset($_POST['username']) && !empty($_POST['username']) && isset($_POST['password']) && !empty($_POST['password'])) {
        if ($Banned) {
            /*
            Http::redirect("login.php");
            error();
            */
        }
        $Err = $Validate->ValidateForm($_POST);

        if (!$Err) {
            // Passes preliminary validation (username and password "look right")
            $db->query("
            SELECT
              ID,
              PermissionID,
              CustomPermissions,
              PassHash,
              TwoFactor,
              Enabled
            FROM users_main
              WHERE Username = ?
              AND Username != ''", $_POST['username']);
            list($UserID, $PermissionID, $CustomPermissions, $PassHash, $TwoFactor, $Enabled) = $db->next_record(MYSQLI_NUM, array(2));
            if (!$Banned) {
                if ($UserID && Users::check_password($_POST['password'], $PassHash)) {
                    // Update hash if better algorithm available
                    if (password_needs_rehash($PassHash, PASSWORD_DEFAULT)) {
                        $db->query("
                        UPDATE users_main
                        SET PassHash = ?
                          WHERE Username = ?", make_sec_hash($_POST['password']), $_POST['username']);
                    }

                    if (empty($TwoFactor) || $TwoFA->verifyCode($TwoFactor, $_POST['twofa'])) {
                        # todo: Make sure the type is (int)
                        if ($Enabled === '1') {
                            $U2FRegs = [];
                            $db->query("
                            SELECT KeyHandle, PublicKey, Certificate, Counter, Valid
                            FROM u2f
                              WHERE UserID = ?", $UserID);
                            // Needs to be an array of objects, so we can't use to_array()
                            while (list($KeyHandle, $PublicKey, $Certificate, $Counter, $Valid) = $db->next_record()) {
                                $U2FRegs[] = (object)['keyHandle'=>$KeyHandle, 'publicKey'=>$PublicKey, 'certificate'=>$Certificate, 'counter'=>$Counter, 'valid'=>$Valid];
                            }

                            if (sizeof($U2FRegs) > 0) {
                                // U2F is enabled for this account
                                if (isset($_POST['u2f-request']) && isset($_POST['u2f-response'])) {
                                    // Data from the U2F login page is present. Verify it.
                                    try {
                                        $U2FReg = $U2F->doAuthenticate(json_decode($_POST['u2f-request']), $U2FRegs, json_decode($_POST['u2f-response']));
                                        if ($U2FReg->valid != '1') {
                                            error('Token disabled.');
                                        }
                                        $db->query(
                                            "UPDATE u2f
                                SET Counter = ?
                                WHERE KeyHandle = ?
                                AND UserID = ?",
                                            $U2FReg->counter,
                                            $U2FReg->keyHandle,
                                            $UserID
                                        );
                                    } catch (\Exception $e) {
                                        $U2FErr = 'U2F key invalid. Error: '.($e->getMessage());
                                        if ($e->getMessage() == 'Token disabled.') {
                                            $U2FErr = 'This token was disabled due to suspected cloning. Contact staff for assistance';
                                        }
                                        if ($e->getMessage() == 'Counter too low.') {
                                            $BadHandle = json_decode($_POST['u2f-response'], true)['keyHandle'];
                                            $db->query("UPDATE u2f
                                  SET Valid = '0'
                                  WHERE KeyHandle = ?
                                  AND UserID = ?", $BadHandle, $UserID);
                                            $U2FErr = 'U2F counter too low. This token has been disabled due to suspected cloning. Contact staff for assistance';
                                        }
                                    }
                                } else {
                                    // Data from the U2F login page is not present. Go there
                                    require('u2f.php');
                                    #error();
                                }
                            }

                            if (sizeof($U2FRegs) == 0 || !isset($U2FErr)) {
                                $SessionID = Users::make_secret(64);
                                Http::setCookie(['session' => $SessionID]);
                                Http::setCookie(['userid' => $UserID]);

                                $db->query("
                                INSERT INTO users_sessions
                                  (UserID, SessionID, KeepLogged, IP, LastUpdate, FullUA)
                                VALUES
                                  ('$UserID',
                                  '".db_string($SessionID)."',
                                  '1',
                                  '".db_string(apcu_exists('DBKEY')?Crypto::encrypt($_SERVER['REMOTE_ADDR']):'0.0.0.0')."',
                                  NOW(),
                                  '".db_string($_SERVER['HTTP_USER_AGENT'])."')");

                                $cache->begin_transaction("users_sessions_$UserID");
                                $cache->insert_front($SessionID, [
                                  'SessionID' => $SessionID,
                                  'IP' => (apcu_exists('DBKEY')?Crypto::encrypt($_SERVER['REMOTE_ADDR']):'0.0.0.0'),
                                  'LastUpdate' => sqltime()
                                ]);
                                $cache->commit_transaction(0);

                                $Sql = "
                                UPDATE users_main
                                SET
                                  LastLogin = NOW(),
                                  LastAccess = NOW()
                                  WHERE ID = '".db_string($UserID)."'";

                                $db->query($Sql);

                                if (!empty($_COOKIE['redirect'])) {
                                    $URL = $_COOKIE['redirect'];
                                    Http::deleteCookie('redirect');
                                    Http::redirect("$URL");
                                #error();
                                } else {
                                    Http::redirect("index.php");
                                    #error();
                                }
                            } else {
                                log_attempt();
                                $Err = $U2FErr;
                                Http::deleteCookie('keeplogged');
                            }
                        } else {
                            log_attempt();
                            if ($Enabled == 2) {

                                // Save the username in a cookie for the disabled page
                                Http::setCookie(['username' => db_string($_POST['username'])]);
                                Http::redirect("login.php?action=disabled");
                            # todo: Make sure the type is (int)
                            } elseif ($Enabled === '0') {
                                $Err = 'Your account has not been confirmed. Please check your email, including the spam folder';
                            }
                            Http::deleteCookie('keeplogged');
                        }
                    } else {
                        log_attempt();
                        $Err = 'Two-factor authentication failed';
                        Http::deleteCookie('keeplogged');
                    }
                } else {
                    log_attempt();
                    $Err = 'Your username or password was incorrect';
                    Http::deleteCookie('keeplogged');
                }
            } else {
                log_attempt();
                Http::deleteCookie('keeplogged');
            }
        } else {
            log_attempt();
            Http::deleteCookie('keeplogged');
        }
    }
    require_once __DIR__.'/login.php';
}

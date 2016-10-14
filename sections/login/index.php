<?php

/*-- TODO ---------------------------//
Add the JavaScript validation into the display page using the class
//-----------------------------------*/

// Allow users to reset their password while logged in
if (!empty($LoggedUser['ID']) && $_REQUEST['act'] != 'recover') {
  header('Location: index.php');
  die();
}

if (BLOCK_OPERA_MINI && isset($_SERVER['HTTP_X_OPERAMINI_PHONE'])) {
  error('Opera Mini is banned. Please use another browser.');
}

// Check if IP is banned
if (Tools::site_ban_ip($_SERVER['REMOTE_ADDR'])) {
  error('Your IP address has been banned.');
}

require(SERVER_ROOT.'/classes/validate.class.php');
$Validate = NEW VALIDATE;

if (array_key_exists('action', $_GET) && $_GET['action'] == 'disabled') {
  require('disabled.php');
  die();
}

if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'recover') {
  // Recover password
  if (!empty($_REQUEST['key'])) {
    // User has entered a new password, use step 2
    $DB->query("
      SELECT
        m.ID,
        m.Email,
        m.ipcc,
        i.ResetExpires
      FROM users_main AS m
        INNER JOIN users_info AS i ON i.UserID = m.ID
      WHERE i.ResetKey = '".db_string($_REQUEST['key'])."'
        AND i.ResetKey != ''
        AND m.Enabled = '1'");
    list($UserID, $Email, $Country, $Expires) = $DB->next_record();

    if (!apc_exists('DBKEY')) {
      error('Database not fully decrypted. Please wait for staff to fix this and try again later');
    }

    $Email = DBCrypt::decrypt($Email);

    if ($UserID && strtotime($Expires) > time()) {
      // If the user has requested a password change, and his key has not expired
      $Validate->SetFields('password', '1', 'regex', 'You entered an invalid password. Any password at least 6 characters long is accepted, but a strong password is 8 characters or longer, contains at least 1 lowercase and uppercase letter, contains at least a number or symbol', array('regex' => '/(?=^.{6,}$).*$/'));
      $Validate->SetFields('verifypassword', '1', 'compare', 'Your passwords did not match.', array('comparefield' => 'password'));

      if (!empty($_REQUEST['password'])) {
        // If the user has entered a password.
        // If the user has not entered a password, $PassWasReset is not set to 1, and the success message is not shown
        $Err = $Validate->ValidateForm($_REQUEST);
        if ($Err == '') {
          // Form validates without error, set new secret and password.
          $DB->query("
            UPDATE
              users_main AS m,
              users_info AS i
            SET
              m.PassHash = '".db_string(Users::make_sec_hash($_REQUEST['password']))."',
              i.ResetKey = '',
              m.LastLogin = NOW(),
              i.ResetExpires = '0000-00-00 00:00:00'
            WHERE m.ID = '$UserID'
              AND i.UserID = m.ID");
          $DB->query("
            INSERT INTO users_history_passwords
              (UserID, ChangerIP, ChangeTime)
            VALUES
              ('$UserID', '".DBCrypt::encrypt($_SERVER['REMOTE_ADDR'])."', '".sqltime()."')");
          $PassWasReset = true;
          $LoggedUser['ID'] = $UserID; // Set $LoggedUser['ID'] for logout_all_sessions() to work
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
        $DB->query("
          UPDATE users_info
          SET ResetKey = '',
            ResetExpires = '0000-00-00 00:00:00'
          WHERE UserID = '$UserID'");
        $_SESSION['reseterr'] = 'The link you were given has expired.'; // Error message to display on form
      }
      // Show him the first form (enter email address)
      header('Location: login.php?act=recover');
    }

  } // End step 2

  // User has not clicked the link in his email, use step 1
  else {
    $Validate->SetFields('email', '1', 'email', 'You entered an invalid email address.');

    if (!empty($_REQUEST['email'])) {
      // User has entered email and submitted form
      $Err = $Validate->ValidateForm($_REQUEST);
      if (!apc_exists('DBKEY')) {
        $Err = 'Database not fully decrypted. Please wait for staff to fix this and try again.';
      }

      if (!$Err) {
        // Form validates correctly
        $DB->query("
          SELECT
            Email
          FROM users_main
          WHERE Enabled = '1'");
        while(list($EncEmail) = $DB->next_record()) {
          if ($_REQUEST['email'] == DBCrypt::decrypt($EncEmail)) {
            break; // $EncEmail is now the encrypted form of the given email from the database
          }
        }

        $DB->query("
          SELECT
            ID,
            Username,
            Email
          FROM users_main
          WHERE Email = '$EncEmail'
            AND Enabled = '1'");
        list($UserID, $Username, $Email) = $DB->next_record();
        $Email = DBCrypt::decrypt($Email);

        if ($UserID) {
          // Email exists in the database
          // Set ResetKey, send out email, and set $Sent to 1 to show success page
          Users::resetPassword($UserID, $Username, $Email);

          $Sent = 1; // If $Sent is 1, recover_step1.php displays a success message

          //Log out all of the users current sessions
          $Cache->delete_value("user_info_$UserID");
          $Cache->delete_value("user_info_heavy_$UserID");
          $Cache->delete_value("user_stats_$UserID");
          $Cache->delete_value("enabled_$UserID");

          $DB->query("
            SELECT SessionID
            FROM users_sessions
            WHERE UserID = '$UserID'");
          while (list($SessionID) = $DB->next_record()) {
            $Cache->delete_value("session_$UserID"."_$SessionID");
          }
          $DB->query("
            UPDATE users_sessions
            SET Active = 0
            WHERE UserID = '$UserID'
              AND Active = 1");
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

else if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'newlocation') {
  if (isset($_REQUEST['key'])) {
    if ($ASNCache = $Cache->get_value('new_location_'.$_REQUEST['key'])) {
      $Cache->cache_value('new_location_'.$ASNCache['UserID'].'_'.$ASNCache['ASN'], true);
      require('newlocation.php');
      die();
    } else {
      error(403);
    }
  } else {
    error(403);
  }
} // End new location

// Normal login
else {
  $Validate->SetFields('username', true, 'regex', 'You did not enter a valid username.', array('regex' => USERNAME_REGEX));
  $Validate->SetFields('password', '1', 'string', 'You entered an invalid password.', array('minlength' => '6', 'maxlength' => '307200'));

  list($Attempts, $Banned) = $Cache->get_value('login_attempts_'.db_string($_SERVER['REMOTE_ADDR']));

  // Function to log a user's login attempt
  function log_attempt() {
    global $Cache, $Attempts;
    $Attempts = ($Attempts ?? 0) + 1;
    $Cache->cache_value('login_attempts_'.db_string($_SERVER['REMOTE_ADDR']), array($Attempts, ($Attempts > 5)), 60*60*$Attempts);
    $AllAttempts = $Cache->get_value('login_attempts');
    $AllAttempts[$_SERVER['REMOTE_ADDR']] = time()+(60*60*$Attempts);
    foreach($AllAttempts as $IP => $Time) {
      if ($Time < time()) { unset($AllAttempts[$IP]); }
    }
    $Cache->cache_value('login_attempts', $AllAttempts, 0);
  }

  // If user has submitted form
  if (isset($_POST['username']) && !empty($_POST['username']) && isset($_POST['password']) && !empty($_POST['password'])) {
    if ($Banned) {
      header("Location: login.php");
      die();
    }
    $Err = $Validate->ValidateForm($_POST);

    if (!$Err) {
      // Passes preliminary validation (username and password "look right")
      $DB->query("
        SELECT
          ID,
          PermissionID,
          CustomPermissions,
          PassHash,
          Enabled
        FROM users_main
        WHERE Username = '".db_string($_POST['username'])."'
          AND Username != ''");
      list($UserID, $PermissionID, $CustomPermissions, $PassHash, $Enabled) = $DB->next_record(MYSQLI_NUM, array(2));
      if (!$Banned) {
        if ($UserID && Users::check_password($_POST['password'], $PassHash)) {
          // Update hash if better algorithm available
          if (password_needs_rehash($PassHash, PASSWORD_DEFAULT)) {
            $DB->query("
              UPDATE users_main
              SET PassHash = '".make_sec_hash($_POST['password'])."'
              WHERE Username = '".db_string($_POST['username'])."'");
          }
          if ($Enabled == 1) {

            // Check if the current login attempt is from a location previously logged in from
            if (apc_exists('DBKEY')) {
              $DB->query("
                SELECT IP
                FROM users_history_ips
                WHERE UserID = $UserID");
              $IPs = $DB->to_array(false, MYSQLI_NUM);
              $QueryParts = array();
              foreach ($IPs as $i => $IP) {
                $IPs[$i] = DBCrypt::decrypt($IP[0]);
              }
              $IPs = array_unique($IPs);
              if (count($IPs) > 0) { // Always allow first login
                foreach ($IPs as $IP) {
                  $QueryParts[] = "(StartIP<=INET6_ATON('$IP') AND EndIP>=INET6_ATON('$IP'))";
                }
                $DB->query('SELECT ASN FROM geoip_asn WHERE '.implode(' OR ', $QueryParts));
                $PastASNs = array_column($DB->to_array(false, MYSQLI_NUM), 0);
                $DB->query("SELECT ASN FROM geoip_asn WHERE StartIP<=INET6_ATON('$_SERVER[REMOTE_ADDR]') AND EndIP>=INET6_ATON('$_SERVER[REMOTE_ADDR]')");
                list($CurrentASN) = $DB->next_record();

                if (!in_array($CurrentASN, $PastASNs)) {
                  // Never logged in from this location before
                  if ($Cache->get_value('new_location_'.$UserID.'_'.$CurrentASN) !== true) {
                    $DB->query("
                      SELECT
                        UserName,
                        Email
                      FROM users_main
                      WHERE ID = $UserID");
                    list($Username, $Email) = $DB->next_record();
                    Users::authLocation($UserID, $Username, $CurrentASN, DBCrypt::decrypt($Email));
                    require('newlocation.php');
                    die();
                  }
                }
              }
            }

            $SessionID = Users::make_secret(64);
            $KeepLogged = ($_POST['keeplogged'] ?? false) ? 1 : 0;
            setcookie('session', $SessionID, (time()+60*60*24*365)*$KeepLogged, '/', '', true, true);
            setcookie('userid', $UserID, (time()+60*60*24*365)*$KeepLogged, '/', '', true, true);

            // Because we <3 our staff
            $Permissions = Permissions::get_permissions($PermissionID);
            $CustomPermissions = unserialize($CustomPermissions);
            if (isset($Permissions['Permissions']['site_disable_ip_history'])
              || isset($CustomPermissions['site_disable_ip_history'])
            ) {
              $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            }

            $DB->query("
              INSERT INTO users_sessions
                (UserID, SessionID, KeepLogged, Browser, OperatingSystem, IP, LastUpdate, FullUA)
              VALUES
                ('$UserID', '".db_string($SessionID)."', '$KeepLogged', '$Browser', '$OperatingSystem', '".db_string(apc_exists('DBKEY')?DBCrypt::encrypt($_SERVER['REMOTE_ADDR']):'0.0.0.0')."', '".sqltime()."', '".db_string($_SERVER['HTTP_USER_AGENT'])."')");

            $Cache->begin_transaction("users_sessions_$UserID");
            $Cache->insert_front($SessionID, array(
                'SessionID' => $SessionID,
                'Browser' => $Browser,
                'OperatingSystem' => $OperatingSystem,
                'IP' => (apc_exists('DBKEY')?DBCrypt::encrypt($_SERVER['REMOTE_ADDR']):'0.0.0.0'),
                'LastUpdate' => sqltime()
                ));
            $Cache->commit_transaction(0);

            $Sql = "
              UPDATE users_main
              SET
                LastLogin = '".sqltime()."',
                LastAccess = '".sqltime()."'
              WHERE ID = '".db_string($UserID)."'";

            $DB->query($Sql);

            if (!empty($_COOKIE['redirect'])) {
              $URL = $_COOKIE['redirect'];
              setcookie('redirect', '', time() - 60 * 60 * 24, '/', '', false);
              header("Location: $URL");
              die();
            } else {
              header('Location: index.php');
              die();
            }
          } else {
            log_attempt();
            if ($Enabled == 2) {

              // Save the username in a cookie for the disabled page
              setcookie('username', db_string($_POST['username']), time() + 60 * 60, '/', '', false);
              header('Location: login.php?action=disabled');
            } elseif ($Enabled == 0) {
              $Err = 'Your account has not been confirmed.<br />Please check your email.';
            }
            setcookie('keeplogged', '', time() + 60 * 60 * 24 * 365, '/', '', false);
          }
        } else {
          log_attempt();

          $Err = 'Your username or password was incorrect.';
          setcookie('keeplogged', '', time() + 60 * 60 * 24 * 365, '/', '', false);
        }

      } else {
        log_attempt();
        setcookie('keeplogged', '', time() + 60 * 60 * 24 * 365, '/', '', false);
      }

    } else {
      log_attempt();
      setcookie('keeplogged', '', time() + 60 * 60 * 24 * 365, '/', '', false);
    }
  }
  require('sections/login/login.php');
}

<?php
declare(strict_types=1);

# https://github.com/paragonie/anti-csrf
Http::csrf();


$app = App::go();
$auth = new Auth();

# variables
$post = Http::query("post");
$server = Http::query("server");

$email = Esc::email($post["email"]) ?? null;
$ip = Esc::ip($server["REMOTE_ADDR"]) ?? null;

$passphrase = Esc::string($post["passphrase"]) ?? null;
$confirmPassphrase = Esc::string($post["confirmPassphrase"]) ?? null;


# step one: send recover email
if (!empty($email) && !empty($ip)) {
    $response = $auth->recoverStart($email, $ip);
    $stepOne = true;
}

# step two: validate selector and token
$selector ??= null;
$token ??= null;

if (!empty($selector) && !empty($token)) {
    $response = $auth->recoverMiddle($selector, $token);
    $stepTwo = true;
}

# step three: set new passphrase
# nested ifs were cleaner here
if (!empty($passphrase) && !empty($confirmPassphrase)) {
    if (!empty($post["selector"]) && !empty($post["token"])) {
        $response = $auth->recover($selector, $token, $passphrase, $confirmPassphrase);
        $stepThree = true;
    }
}


/** GAZELLE */


try {
    # set new secret and password
    $query = "
        update users_main as main, users_info as info set
        main.passHash = ?, info.resetKey = '', main.lastLogin = now(), info.resetExpires = null
        where main.id = ? and info.userId = main.id
    ";
    $app->dbNew->do($query, [Auth::makeHash($passphrase), $userId]);

    # log out all of the users current sessions
    $app->cacheOld->delete_value("user_info_{$userId}");
    $app->cacheOld->delete_value("user_info_heavy_{$userId}");
    $app->cacheOld->delete_value("user_stats_{$userId}");
    $app->cacheOld->delete_value("enabled_{$userId}");

    $query = "select sessionId from users_sessions where userId = ?";
    $ref = $app-dbNew->column($query, [$userId]);

    foreach ($ref as $row) {
        $sessionId = $row["sessionId"] ?? null;
        $app->cacheOld->delete_value("session_{$userId}_{$sessionId}");
    }
    
    # delete all stored sessions
    $query = "delete * from users_sessions where userId = ?";
    $ref = $app-dbNew->do($query, [$userId]);
} catch (Exception $e) {
    $response = $e->getMessage();
}


/** TWIG TEMPLATE */


$stepThree ??= null;
if ($stepThree !== true) {
    # default recovery
    $app->twig->display("user/auth/recover.twig", [
        "response" => $response ?? null,
        "stepOne" => $stepOne ?? null,
        "stepTwo" => $stepTwo ?? null,
    ]);
} else {
    # "thanks for confirming"
    $app->twig->display("user/auth/confirm.twig");
}

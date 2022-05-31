<?php
declare(strict_types=1);


# index
Flight::route("/", function () {
    $app = App::go();

    if (isset($app->user["ID"])) {
        #if (!isset($_REQUEST["action"])) {
        require_once "{$app->env->SERVER_ROOT}/sections/index/private.php";
    #} else {
            /*
        switch ($_REQUEST["action"]) {
            case "poll":
                include(SERVER_ROOT."/sections/forums/poll_vote.php");
                break;

            default:
                error(400);
            }
            */
        #}
    } else {
        Http::redirect("login");
    }
});


# login
Flight::route("/login", function () {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/login.php";
});


# disabled
Flight::route("/disabled", function () {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/disabled.php";
});


# enable: todo
Flight::route("/enable/@token", function (string $token) {
    $app = App::go();

    if (isset($app->user["ID"]) || !isset($token) || !$app->env->FEATURE_EMAIL_REENABLE) {
        Http::redirect();
    }
    
    if (isset($token)) {
        $error = AutoEnable::handle_token($token);
    }
    
    View::header("Enable Request");
    echo $error; # this is always set
    View::footer();
});


# recover
Flight::route("/recover", function () {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/recover.php";
});


# logout
Flight::route("/logout", function () {
    # no more bullshit
    $auth = new Auth();
    $auth->logout();
});


# register
Flight::route("/register(/@invite)", function ($invite) {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/register.php";
});


# confirm email
Flight::route("/confirm/@selector/@token", function ($selector, $token) {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/confirm.php";
});


# pwgen
Flight::route("/pwgen(/@method)", function ($method) {
    $app = App::go();

    if ($method === "diceware") {
        header("Content-Type: text/plain; charset=utf-8");
        require_once "{$app->env->SERVER_ROOT}/sections/user/pwgen/diceware.php";
    }

    if ($method === "hash") {
        header("Content-Type: text/plain; charset=utf-8");
        require_once "{$app->env->SERVER_ROOT}/sections/user/pwgen/hash.php";
    }
});

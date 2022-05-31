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

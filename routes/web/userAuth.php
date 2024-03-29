<?php

declare(strict_types=1);


/**
 * userAuth
 */

# login
Flight::route("/login", function () {
    $app = \Gazelle\App::go();

    if ($app->user->isLoggedIn()) {
        Http::redirect();
    } else {
        require_once "{$app->env->serverRoot}/sections/user/auth/login.php";
    }
});


# disabled
Flight::route("/disabled", function () {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/user/auth/disabled.php";
});


# enable: todo
Flight::route("/enable/@token", function (string $token) {
    $app = \Gazelle\App::go();

    if (isset($app->user->core["id"]) || !isset($token) || !$app->env->FEATURE_EMAIL_REENABLE) {
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
Flight::route("/recover(/@selector(/@token))", function (?string $selector, ?string $token) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/user/auth/recover.php";
});


# logout
Flight::route("/logout", function () {
    # no more bullshit
    $auth = new Auth();
    $auth->logout();

    # send to login
    Http::redirect("login");
});


# register
Flight::route("/register(/@invite)", function ($invite) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/user/auth/register.php";
});


# confirm email
Flight::route("/confirm/@selector/@token", function (string $selector, string $token) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/user/auth/confirm.php";
});


# resend confirmation email
Flight::route("/resend/@identifier", function ($identifier) {
    $app = \Gazelle\App::go();
    require_once "{$app->env->serverRoot}/sections/user/auth/resend.php";
});


/*
# pwgen
Flight::route("/pwgen(/@method)", function ($method) {
    $app = \Gazelle\App::go();

    if ($method === "diceware") {
        header("Content-Type: text/plain; charset=utf-8");
        require_once "{$app->env->serverRoot}/sections/user/pwgen/diceware.php";
    }

    if ($method === "hash") {
        header("Content-Type: text/plain; charset=utf-8");
        require_once "{$app->env->serverRoot}/sections/user/pwgen/hash.php";
    }
});
*/


# discourse connect
# https://meta.discourse.org/t/discourseconnect-official-single-sign-on-for-discourse-sso/13045
# e.g., https://somesite.com/sso?sso=PAYLOAD&sig=SIG
Flight::route("/discourse?sso=@payload&sig=@signature", function () {
    $app = \Gazelle\App::go();

    if ($app->env->enableDiscourse === true) {
        require_once "{$app->env->serverRoot}/sections/social/discourseConnect.php";
    } else {
        return false;
    }
});

<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */


/** index */

# index
Flight::route("/", function () {
    $app = App::go();

    if (isset($app->user["ID"])) {
        #if (!isset($_REQUEST["action"])) {
        require_once __DIR__."/../sections/index/private.php";
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


/** legal */

# about
Flight::route("/about", function () {
    $app = App::go();

    $text = Text::parse(
        file_get_contents("{$app->env->SERVER_ROOT}/templates/legal/about.md")
    );

    $app->twig->display("legal/tldr.twig", ["title" => "About", "text" => $text]);
});

# canary
Flight::route("/canary", function () {
    header("Content-Type: text/plain; charset=utf-8");
    require_once __DIR__."/../templates/legal/canary.txt";
});

# dmca
Flight::route("/dmca", function () {
    $app = App::go();

    $text = Text::parse(
        file_get_contents("{$app->env->SERVER_ROOT}/templates/legal/dmca.md")
    );

    $app->twig->display("legal/tldr.twig", ["title" => "DMCA", "text" => $text]);
});

# privacy
Flight::route("/privacy", function () {
    $app = App::go();

    $text = Text::parse(
        file_get_contents("{$app->env->SERVER_ROOT}/templates/legal/privacy.md")
    );

    $app->twig->display("legal/tldr.twig", ["title" => "Privacy", "text" => $text]);
});

# pubkey
Flight::route("/pubkey", function () {
    header("Content-Type: text/plain; charset=utf-8");
    require_once __DIR__."/../templates/legal/pubkey.txt";
});


/** login */

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



/** logout */
Flight::route("/logout", function () {
    # no more bullshit
    $auth = new Auth();
    $auth->logout();
});


/**
 * USER AUTH
 */

# registration page
Flight::route("/register(/@invite)", function ($invite) {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/register.php";
});

# confirm new registration
Flight::route("/confirm/@selector/@token", function ($selector, $token) {
    $app = App::go();
    require_once "{$app->env->SERVER_ROOT}/sections/user/auth/confirm.php";
});


# start the router
Flight::start();

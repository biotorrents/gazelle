<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

/** index */
Flight::route("/", function () {
    if (isset(G::$user["ID"])) {
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
Flight::route("/about", function () {
    $ENV = ENV::go();
    $twig = Twig::go();

    $text = Text::parse(
        file_get_contents("{$ENV->SERVER_ROOT}/templates/legal/about.md")
    );

    View::header("About");
    echo $twig->render("legal/tldr.twig", ["text" => $text]);
    View::footer();
});

# canary
Flight::route("/canary", function () {
    header("Content-Type: text/plain; charset=utf-8");
    require_once __DIR__."/../templates/legal/canary.txt";
});

# dmca
Flight::route("/dmca", function () {
    $ENV = ENV::go();
    $twig = Twig::go();

    $text = Text::parse(
        file_get_contents("{$ENV->SERVER_ROOT}/templates/legal/dmca.md")
    );

    View::header("DMCA");
    echo $twig->render("legal/tldr.twig", ["text" => $text]);
    View::footer();
});

# privacy
Flight::route("/privacy", function () {
    $ENV = ENV::go();
    $twig = Twig::go();

    $text = Text::parse(
        file_get_contents("{$ENV->SERVER_ROOT}/templates/legal/privacy.md")
    );

    View::header("Privacy");
    echo $twig->render("legal/tldr.twig", ["text" => $text]);
    View::footer();
});

# pubkey
Flight::route("/pubkey", function () {
    header("Content-Type: text/plain; charset=utf-8");
    require_once __DIR__."/../templates/legal/pubkey.txt";
});


/** login */
Flight::route("/login", function () {
    # 2022-02-13: currently lots of crazy logic here
    require_once __DIR__."/../sections/login/router.php";
    #require_once __DIR__."/../sections/login/login.php";
});


/** logout */
Flight::route("/logout", function () {
    # no more bullshit
    logout_all_sessions();
});


# start the router
Flight::start();

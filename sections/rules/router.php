<?php
declare(strict_types=1);

/**
 * Flight router
 * @see https://flightphp.com/learn
 */

# golden
Flight::route("/rules", function () {
    $app = App::go();

    $text = Text::parse(
        file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/golden.md")
    );

    View::header("Golden rules");
    echo $app->twig->render("rules/rules.twig", ["text" => $text]);
    View::footer();
});

# chat
Flight::route("/rules/chat", function () {
    $app = App::go();

    $text = Text::parse(
        file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/chat.md")
    );

    View::header("Chat rules");
    echo $app->twig->render("rules/rules.twig", ["text" => $text]);
    View::footer();
});

# clients
Flight::route("/rules/clients", function () {
    require_once __DIR__."/clients.php";
});

# collages
Flight::route("/rules/collages", function () {
    $app = App::go();

    $text = Text::parse(
        file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/collages.md"),
        false
    );

    View::header("Collection rules");
    echo $app->twig->render("rules/rules.twig", ["text" => $text]);
    View::footer();
});

Flight::route("/rules/ratio", function () {
    require_once __DIR__."/ratio.php";
});

# requests
Flight::route("/rules/requests", function () {
    $app = App::go();
    
    $text = Text::parse(
        file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/requests.md")
    );
    
    View::header("Request rules");
    echo $app->twig->render("rules/rules.twig", ["text" => $text]);
    View::footer();
});

# tags
Flight::route("/rules/tags", function () {
    $app = App::go();
    
    $text = Text::parse(
        file_get_contents("{$app->env->SERVER_ROOT}/templates/rules/tags.md")
    );
    
    View::header("Tagging rules");
    echo $app->twig->render("rules/rules.twig", ["text" => $text]);
    View::footer();
});

# upload
Flight::route("/rules/upload", function () {
    require_once __DIR__."/upload.php";
});

# start the router
Flight::start();

<?php
declare(strict_types=1);

# enable
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

# start the router
Flight::start();

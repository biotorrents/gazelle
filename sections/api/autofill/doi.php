<?php
declare(strict_types=1);


$app = App::go();
$ENV = ENV::go();

if (!$_GET['doi']) {
    json_error('expected doi param');
} elseif (!preg_match($app->env->regexDoi, strtoupper($_GET['doi']))) {
    json_error('expected valid doi');
} else {
    $DOI = $_GET['doi'];
}

# https://weichie.com/blog/curl-api-calls-with-php/
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "$ENV->SS/$DOI");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
$output = curl_exec($curl);
curl_close($curl);

# I don't like this nested json_*code() business
# It's slow and unnecesary since SS already outputs JSON
# todo: At least cache the response, then refactor
print
    json_encode(
        [
            'status' => 'success',
            'response' => json_decode($output, true),
        ],
        JSON_UNESCAPED_SLASHES
    );

<?php
declare(strict_types=1);

$ENV = ENV::go();
$twig = Twig::go();

echo $twig->render(
    'footer/public.twig',
    ['ENV' => $ENV]
);

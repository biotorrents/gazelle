<?php
declare(strict_types=1);

$Twig = Twig::go();
$p = $_GET['p'];

switch ($p) {
    case 'about':
        View::header('About');
        echo $Twig->render('legal/about.html');
        View::footer();
        break;

    case 'privacy':
        View::header('Privacy');
        echo $Twig->render('legal/privacy.html');
        View::footer();
        break;
    
    case 'dmca':
        View::header('DMCA');
        echo $Twig->render('legal/dmca.html');
        View::footer();
        break;
   
    default:
        View::header('404 Not Found');
        error(404);
        View::footer();
        break;
}

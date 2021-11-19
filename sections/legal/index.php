<?php
declare(strict_types=1);

$Twig = Twig::go();
$p = $_GET['p'];

switch ($p) {
    case 'about':
        View::show_header('About');
        echo $Twig->render('legal/about.html');
        View::show_footer();
        break;

    case 'privacy':
        View::show_header('Privacy');
        echo $Twig->render('legal/privacy.html');
        View::show_footer();
        break;
    
    case 'dmca':
        View::show_header('DMCA');
        echo $Twig->render('legal/dmca.html');
        View::show_footer();
        break;
   
    default:
        View::show_header('404 Not Found');
        error(404);
        View::show_footer();
        break;
}

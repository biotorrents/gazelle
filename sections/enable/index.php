<?php
#declare(strict_types=1);

if (isset($LoggedUser['ID']) || !isset($_GET['token']) || !FEATURE_EMAIL_REENABLE) {
    header('Location: index.php');
    error();
}

if (isset($_GET['token'])) {
    $Err = AutoEnable::handle_token($_GET['token']);
}

View::show_header('Enable Request');
echo $Err; // This will always be set
View::show_footer();

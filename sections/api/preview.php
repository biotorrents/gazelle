<?php

#declare(strict_types=1);

/* AJAX previews, simple stuff */
if (!empty($_POST['AdminComment'])) {
    echo \Gazelle\Text::parse($_POST['AdminComment']);
} else {
    $Content = $_REQUEST['body']; // Don't use URL decode
    echo \Gazelle\Text::parse($Content);
}

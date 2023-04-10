<?php

/* AJAX Previews, simple stuff. */

if (!empty($_POST['message'])) {
    echo \Gazelle\Text::parse($_POST['message']);
}

<?php

/* AJAX Previews, simple stuff. */

if (!empty($_POST['message'])) {
    echo Text::parse($_POST['message']);
}

<?php

if (isset($_SERVER['http_if_modified_since'])) {
    header('Status: 304 Not Modified');
    error();
}

header('Expires: '.date('D, d-M-Y H:i:s \U\T\C', time() + 3600 * 24 * 120)); //120 days
header('Last-Modified: '.date('D, d-M-Y H:i:s \U\T\C', time()));

if (!check_perms('users_view_ips')) {
    error('Access denied.');
}

if (empty($_GET['ip'])) {
    error('Invalid IP address.');
}

die(Tools::geoip($_GET['ip']));

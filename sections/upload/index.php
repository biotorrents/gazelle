<?php
declare(strict_types=1);

enforce_login();
if (!check_perms('site_upload')) {
    error('Please read the site wiki for information on how to become a Member and gain upload privileges.');
}

if ($LoggedUser['DisableUpload']) {
    error('Your upload privileges have been revoked.');
}

// Build the page
if (!empty($_POST['submit'])) {
    require_once 'upload_handle.php';
} else {
    require_once SERVER_ROOT.'/sections/upload/upload.php';
}

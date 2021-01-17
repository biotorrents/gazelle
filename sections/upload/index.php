<?php
declare(strict_types=1);

enforce_login();
if (!check_perms('site_upload')) {
    error(403);
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

<?php
declare(strict_types=1);

$p = $_GET['p'];

switch ($p) {
    case 'privacy':
        require_once 'privacy.php';
        break;
    
    case 'dmca':
        require_once 'dmca.php';
        break;
    
    default:
        error(404);
        break;
}

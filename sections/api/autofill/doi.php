<?php
#declare(strict_types=1);

if (!$_GET['doi']) {
    json_die();
}

print
    json_encode(
        [
            'status' => 'success',
            'response' => ['loadAverage' => sys_getloadavg()]
        ]
    );

<?php

declare(strict_types=1);

#authorize();

print
  json_encode(
      array(
        'status' => 'success',
        'response' => array(
          'loadAverage' => sys_getloadavg()
        )
      )
  );

<?php
declare(strict_types=1);

$DB->query("
UPDATE
  `users_info`
SET
  `AuthKey` =
    MD5(
      CONCAT(
        `AuthKey`, RAND(), '".Users::make_secret()."',
        SHA1(
          CONCAT(
            RAND(), RAND(), '".Users::make_secret()."'
          )
        )
      )
    );
");

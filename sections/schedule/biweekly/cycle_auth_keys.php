<?php
#declare(strict_types=1);

$DB->query("
UPDATE
  `users_info`
SET
  `AuthKey` =
    MD5(
      CONCAT(
        `AuthKey`, RAND(), '".db_string(Users::make_secret())."',
        SHA1(
          CONCAT(
            RAND(), RAND(), '".db_string(Users::make_secret())."'
          )
        )
      )
    );
");

<?php

declare(strict_types=1);

$app = App::go();

$app->dbOld->query("
UPDATE
  `users_info`
SET
  `AuthKey` =
    MD5(
      CONCAT(
        `AuthKey`, RAND(), '".Text::random()."',
        SHA1(
          CONCAT(
            RAND(), RAND(), '".Text::random()."'
          )
        )
      )
    );
");

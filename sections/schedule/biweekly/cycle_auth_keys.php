<?php

declare(strict_types=1);

$app = \Gazelle\App::go();

$app->dbOld->query("
UPDATE
  `users_info`
SET
  `AuthKey` =
    MD5(
      CONCAT(
        `AuthKey`, RAND(), '".\Gazelle\Text::random()."',
        SHA1(
          CONCAT(
            RAND(), RAND(), '".\Gazelle\Text::random()."'
          )
        )
      )
    );
");

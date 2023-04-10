<?php

declare(strict_types=1);

# Quick proof-of-concept
$ENV = ENV::go();
print
  json_encode(
      array(
        'status' => 'success',
        'response' => $ENV->CATS
      )
  );

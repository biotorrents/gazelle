<?php
declare(strict_types=1);


# vomit hashes of secure randomness
echo password_hash(random_bytes(256), PASSWORD_DEFAULT);

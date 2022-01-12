<?php
declare(strict_types=1);

# authorize doesn't work if we're not logged in
enforce_login();
authorize();
logout();

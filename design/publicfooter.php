<?php
declare(strict_types=1);

$ENV = ENV::go();
$year = date('Y');

echo <<<HTML
    </main>
    <footer>
      © {$year} {$ENV->SITE_NAME}
    </footer>
  </body>
</html>
HTML;

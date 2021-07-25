<?php
declare(strict_types=1);

$ENV = ENV::go();

echo <<<HTML
</main>

<footer>
  <a href="https://github.com/biotorrents/gazelle" target="_blank">GitHub</a>
  <a href="https://docs.biotorrents.de" target="_blank">API</a>
  <a href="/legal.php?p=privacy">Privacy</a>
  <a href="/legal.php?p=dmca">DMCA</a>
</footer>

<script src="$ENV->STATIC_SERVER/functions/vendor/instantpage.js" type="module"></script>
</body>

</html>
HTML;

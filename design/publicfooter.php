<?php
declare(strict_types=1);

$ENV = ENV::go();

echo <<<HTML
</main>

<footer>
  <a href="/legal.php?p=privacy">Privacy</a>
  <a href="/legal.php?p=dmca">DMCA</a>
  <a class="external" href="https://github.com/biotorrents/gazelle" target="_blank">GitHub</a>
  <a class="external" href="https://patreon.com/biotorrents" target="_blank">Patreon</a>
</footer>

<script src="$ENV->STATIC_SERVER/functions/vendor/instantpage.js" type="module"></script>
</body>

</html>
HTML;

<?php
declare(strict_types=1);

$ENV = ENV::go();
$twig = Twig::go();

global $user;

echo <<<HTML
<!doctype html>
<html>

<head>
  <title>$PageTitle</title>
  <script defer data-domain="<?= $ENV->SITE_DOMAIN ?>" src="https://stats.torrents.bio/js/plausible.js"></script>
HTML;

echo $twig->render(
    'header/meta-tags.twig',
    [
    'ENV' => $ENV,
    'user' => $user,
    'title' => esc($PageTitle)
  ]
);

# Load JS
$Scripts = array_filter(
    array_merge(
        [
          'vendor/jquery.min',
          'global',
          'ajax.class',
          'storage.class',
          'public',
          'vendor/u2f-api'
      ],
        explode(',', $JSIncludes)
    )
);

foreach ($Scripts as $Script) {
    View::pushAsset(
        "$ENV->STATIC_SERVER/js/$Script.js",
        'script'
    );
}

# Load CSS
$Styles = ['vendor/normalize.min', 'vendor/skeleton.min', 'global', 'public'];
foreach ($Styles as $Style) {
    echo View::pushAsset(
        "$ENV->STATIC_SERVER/css/$Style.css",
        'style'
    );
}

# Fonts
/*
echo View::pushAsset(
# Only Noto Sans available on public pages
"$ENV->STATIC_SERVER/css/assets/fonts/noto/NotoSans-SemiCondensed.woff2",
    'font'
);
*/

echo <<<HTML
</head>

<body>
  <header>
    <span class="left">
      <a href="/login">Log In</a>
HTML;

if ($ENV->OPEN_REGISTRATION) {
    echo '<a href="register.php">Register</a>';
}

echo <<<HTML
      <a href="/about">About</a>
      <a class="external" href="https://docs.torrents.bio" target="_blank">Docs</a>
    </span>

    <span class="right">
      <a href="/privacy">Privacy</a>
      <a href="/dmca">DMCA</a>
      <a class="external" href="https://github.com/biotorrents" target="_blank">GitHub</a>
      <a class="external" href="https://patreon.com/biotorrents" target="_blank">Patreon</a>
    </span>
  </header>

<main>
  <h1 id="logo"></h1>
HTML;

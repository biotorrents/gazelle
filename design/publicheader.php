<?php
declare(strict_types=1);

$ENV = ENV::go();
$twig = Twig::go();

global $LoggedUser;

echo <<<HTML
<!doctype html>
<html>

<head>
  <title>$PageTitle</title>
  <script defer data-domain="<?= $ENV->SITE_DOMAIN ?>" src="https://stats.torrents.bio/js/plausible.js"></script>
HTML;

echo $twig->render(
    'header/meta-tags.html',
    [
    'ENV' => $ENV,
    'LoggedUser' => $LoggedUser,
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
    <a href="login.php">Log In</a>
HTML;

if ($ENV->OPEN_REGISTRATION) {
    echo '<a href="register.php">Register</a>';
}

echo <<<HTML
    <a href="/about">About</a>
    <a class="external" href="https://docs.torrents.bio" target="_blank">Docs</a>
  </header>

<main>
  <h1 id="logo">
    <a href="/" aria-label="Front page"></a>
  </h1>
HTML;

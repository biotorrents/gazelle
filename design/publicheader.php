<?php
declare(strict_types=1);

$ENV = ENV::go();
global $LoggedUser;

echo <<<HTML
<!doctype html>
<html>

<head>
  <title>$PageTitle</title>
HTML;

echo View::commonMeta();
echo "<link href='$ENV->STATIC_SERVER/styles/public.css?v="
     . filemtime(SERVER_ROOT.'/static/styles/public.css')
     . "' rel='stylesheet' type='text/css'>";

# Load JS
$Scripts = array_filter(
    array_merge(
        [
          'vendor/jquery.min',
          'global',
          'ajax.class',
          'cookie.class',
          'storage.class',
          'public',
          'u2f'
      ],
        explode(',', $JSIncludes)
    )
);

foreach ($Scripts as $Script) {
    View::pushAsset(
        "$ENV->STATIC_SERVER/functions/$Script.js",
        'script'
    );
}

# Load CSS
$Styles = ['vendor/normalize', 'vendor/skeleton', 'global', 'public'];
foreach ($Styles as $Style) {
    echo View::pushAsset(
        "$ENV->STATIC_SERVER/styles/$Style.css",
        'style'
    );
}

# Fonts
echo View::pushAsset(
# Only Noto Sans available on public pages
"$ENV->STATIC_SERVER/styles/assets/fonts/noto/NotoSans-SemiCondensed.woff2",
    'font'
);

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
    <a href="/legal.php?p=about">About</a>
    <a class="external" href="https://docs.torrents.bio" target="_blank">Docs</a>
  </header>

<main>
  <h1 id="logo">
    <a href="/" aria-label="Front page"></a>
  </h1>
HTML;

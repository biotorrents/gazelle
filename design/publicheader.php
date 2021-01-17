<?php
declare(strict_types=1);

$ENV = ENV::go();
global $LoggedUser;
?>

<!doctype html>
<html>

<head>
  <title>
    <?= display_str($PageTitle) ?>
  </title>

  <?= View::commonMeta(); ?>

  <link
    href="<?=STATIC_SERVER ?>styles/public.css?v=<?=filemtime(SERVER_ROOT.'/static/styles/public.css')?>"
    rel="stylesheet" type="text/css">

  <?php
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
$Styles = ['global', 'public'];
foreach ($Styles as $Style) {
    echo View::pushAsset(
        "$ENV->STATIC_SERVER/styles/$Style.css",
        'style'
    );
}

# Fonts
echo View::pushAsset(
# Only Noto Sans available on public pages
"$ENV->STATIC_SERVER/styles/assets/fonts/noto/woff2/NotoSans-SemiCondensed.woff2",
    'font'
); ?>
</head>

<body>
  <header>
    <a href="login.php">Log In</a>

    <?php if ($ENV->OPEN_REGISTRATION) { ?>
    <a href="register.php">Register</a>
    <?php } ?>

    <a
      href="mailto:help@biotorrents.de?subject=[TxID <?= strtoupper(bin2hex(random_bytes(2))) ?>] Vague subject lines ignored">Support</a>
  </header>

  <main>
    <h1 id="logo">
      <a href="/" aria-label="Front page"></a>
    </h1>
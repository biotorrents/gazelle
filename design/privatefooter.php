<?php
declare(strict_types=1);

$ENV = ENV::go();
$Sep = '&emsp;';

$LastActive = $LastActive ?? ['LastUpdate' => null, 'IP' => null];

# End <main#content.container>, begin <footer>
# #content is Gazelle, .container is Skeleton
echo $HTML = '</main><footer>';

# Disclaimer
echo $HTML = <<<HTML
<p>
  No data are hosted on $ENV->SITE_NAME's servers.
  All torrents are user-generated content.
  Torrents without a specified license may be protected by copyright.
</p>
HTML;

# Sessions
if (count($UserSessions) > 1) {
    foreach ($UserSessions as $ThisSessionID => $Session) {
        if ($ThisSessionID !== $SessionID) {
            $LastActive = $Session;
            break;
        }
    }
}

# User meta
$LastUpdate = time_diff($LastActive['LastUpdate']);

$IP = (apcu_exists('DBKEY') && $LastActive['IP'] && $LastActive['IP'] !== '0.0.0.0'
    ? (Crypto::decrypt($LastActive['IP']))
    : '[Encrypted]');

if (!empty($LastActive)) {
    echo $HTML = <<<HTML
    <p>
      <a href="user.php?action=sessions">
        <span class="tooltip" title="Manage sessions">
          Last activity:
        </span>
        $LastUpdate

        <span class="tooltip" title="Manage sessions">
          from
          $IP
        </span>
      </a>
    </p>
HTML;
}

# Site meta
$Year = date('Y');
$Load = sys_getloadavg();
  
echo $HTML = <<<HTML
<p>
  &copy;
  $Year
  $ENV->SITE_NAME
  $Sep
  <a href="/canary">Warrant Canary</a>
</p>
HTML;

# Debug
if ($ENV->DEV) {
    /**
     * DebugBar trial, missing important collectors:
     *
     *   - Sphinx
     *   - Ocelot
     *   - MySQL
     *   - Cache
     *
     * Otherwise, nothing of value was lost.
     * @see http://phpdebugbar.com/docs/
     */
    $Debug = Debug::go();
    $Render = $Debug->getJavascriptRenderer();
    echo $Render->render();
}

# Notifications
global $NotificationSpans;
if (!empty($NotificationSpans)) {
    foreach ($NotificationSpans as $Notification) {
        echo "$Notification\n";
    }
}

# Done
echo $HTML = <<<HTML
    </footer>
    <script>hljs.highlightAll();</script>
  </body>
</html>
HTML;

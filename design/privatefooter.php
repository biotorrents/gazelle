<?php
declare(strict_types=1);

$ENV = ENV::go();
$Sep = '&emsp;';
#$Sep = '&ensp;&middot;&ensp;';

# End <div#content>, begin <footer>
# This needs to be <main>, in each page
echo $HTML = '</div></main><footer>';

/*
# Disclaimer
if (!empty($Options['disclaimer'])) {
    echo $HTML = <<<HTML
    <div id="disclaimer_container">
      None of the files shown here are actually hosted on this server.
      The links are provided solely by this site's users.
      These BitTorrent files are meant for the distribution of backup files.
      By downloading the BitTorrent file, you are claiming that you own the original file.
      The administrator of this site ($ENV->SITE_DOMAIN) holds <em>no responsibility</em>
      if these files are misused in any way and cannot be held responsible for what its users post.
    </div>
HTML;
}
*/

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
  Site and design
  &copy;
  $Year
  $ENV->SITE_NAME
  $Sep
  <a href='/sections/legal/canary.txt'>Warrant Canary</a>
</p>
HTML;

# Script meta
$MicroTime = number_format(((microtime(true) - $ScriptStartTime) * 1000), 5);
$Used = Format::get_size(memory_get_usage(true));
$Load = number_format($Load[0], 2).' '.number_format($Load[1], 2).' '.number_format($Load[2], 2);
$Date = date('M d Y');
$Time = date('H:i');

echo $HTML = <<<HTML
<p>
  <strong>Time:</strong>
  $MicroTime ms
  $Sep

  <!--
  <strong>Used:</strong>
  $Used
  $Sep
  -->

  <strong>Load:</strong>
  $Load
  $Sep

  <strong>Date:</strong>
  $Date,
  $Time
</p>
HTML;

if (DEBUG_MODE || check_perms('site_debug')) {
    echo $HTML = <<<HTML
<!-- Begin Debugging -->
<div id="site_debug">
HTML;

    $Debug->perf_table();
    $Debug->flag_table();
    $Debug->error_table();
    $Debug->sphinx_table();
    $Debug->query_table();
    $Debug->cache_table();
    $Debug->vars_table();
    $Debug->ocelot_table();

    echo $HTML = <<<HTML
</div>
<!-- End Debugging -->
HTML;
}

global $NotificationSpans;
if (!empty($NotificationSpans)) {
    foreach ($NotificationSpans as $Notification) {
        echo "$Notification\n";
    }
}

echo $HTML = '</footer></body></html>';

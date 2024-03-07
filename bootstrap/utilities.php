<?php

declare(strict_types=1);


/************
 * util.php *
 ************/

/**
 * send_irc
 *
 * Send a message to an IRC bot listening on SOCKET_LISTEN_PORT
 *
 * @param string $Raw An IRC protocol snippet to send.
 */
function send_irc($Channels = null, $Message = '')
{
    $ENV = Gazelle\ENV::go();

    // Check if IRC is enabled
    if (!$ENV->announceIrc || !$Channels) {
        return false;
    }

    # The fn takes an array or string
    $Dest = [];

    # Quick missed connection fix
    if (is_string($Channels)) {
        $Channels = explode(' ', $Channels);
    }

    # Strip leading #channel hash
    foreach ($Channels as $c) {
        array_push($Dest, preg_replace('/^#/', '', $c));
    }

    # Specific to AB's kana bot
    # https://github.com/anniemaybytes/kana
    $Command =
    implode('-', $Dest)
    . '|%|'
    . html_entity_decode(
        Gazelle\Text::esc($Message),
        ENT_QUOTES
    );

    # Original input sanitization
    $Command = str_replace(array("\n", "\r"), '', $Command);

    # Send the raw echo
    $IRCSocket = fsockopen(SOCKET_LISTEN_ADDRESS, SOCKET_LISTEN_PORT);
    fwrite($IRCSocket, $Command);
    fclose($IRCSocket);
}


/**
 * error
 */
function error(int|string $error = 400, $noHtmlUnused = false, $logUnused = false): void
{
    $app = Gazelle\App::go();

    $app->error($error);
}


/**
 * site_url
 */
function site_url()
{
    $app = Gazelle\App::go();

    return "https://{$app->env->siteDomain}";
}


/******************************
 * classes/paranoia.class.php *
 ******************************/

/**
 * check_paranoia
 */
function check_paranoia($Property, $Paranoia = false, $UserClass = false, $UserID = false)
{
    return true;
}


/**************************
 * classes/time.class.php *
 **************************/

/**
 * time_ago
 */
function time_ago($TimeStamp)
{
    if (!$TimeStamp) {
        return false;
    }
    if (!is_numeric($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
        $TimeStamp = strtotime($TimeStamp);
    }
    return time() - $TimeStamp;
}


/**
 * time_diff
 */
function time_diff(int|string $time, $unusedLevels = 2, $unusedSpan = true, $unusedLowercase = false)
{
    return Carbon\Carbon::parse($time)->diffForHumans();
}


/*************************
 * sql utility functions *
 *************************/

/**
 * time_plus
 */
function time_plus($Offset)
{
    return date('Y-m-d H:i:s', time() + $Offset);
}


/**
 * time_minus
 */
function time_minus($Offset, $Fuzzy = false)
{
    if ($Fuzzy) {
        return date('Y-m-d 00:00:00', time() - $Offset);
    } else {
        return date('Y-m-d H:i:s', time() - $Offset);
    }
}


/**
 * sqltime
 */
function sqltime($timestamp = null)
{
    return Gazelle\App::sqlTime($timestamp);
}

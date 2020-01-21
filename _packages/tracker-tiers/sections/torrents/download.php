<?php

# ...

header('Content-Type: application/x-bittorrent; charset=utf-8');
header('Content-disposition: attachment; filename="'.$FileName.'"');

function add_passkey($Ann)
{
    global $TorrentPass;
    return (is_array($Ann)) ? array_map('add_passkey', $Ann) : $Ann.'/'.$TorrentPass.'/announce';
}

$UserAnnounceURL = ANNOUNCE_URLS[0][0].'/'.$TorrentPass.'/announce';
$UserAnnounceList = array(array_map('add_passkey', ANNOUNCE_URLS[0]), ANNOUNCE_URLS[1]);
#$UserAnnounceList = (sizeof(ANNOUNCE_URLS) === 1 && sizeof(ANNOUNCE_URLS[0]) === 1) ? [] : array_map('add_passkey', ANNOUNCE_URLS);

echo TorrentsDL::get_file($Contents, $UserAnnounceURL, $UserAnnounceList);

define('SKIP_NO_CACHE_HEADERS', 1);

# EOF

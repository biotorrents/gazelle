<?php
declare(strict_types=1);

require SERVER_ROOT.'/sections/torrents/functions.php';

$GroupID = (int) $_GET['id'];
if ($GroupID === 0) {
    error('Bad ID parameter', true);
}

$TorrentDetails = get_group_info($GroupID, true, 0, false);
$TorrentDetails = $TorrentDetails[0];
$Image = $TorrentDetails['WikiImage'];

// Handle no artwork
if (!$Image) {
    $Image = STATIC_SERVER.'common/noartwork/music.png';
}

json_die('success', array(
  'picture' => $Image
));

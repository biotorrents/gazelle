<?php
declare(strict_types=1);

require_once SERVER_ROOT.'/sections/torrents/functions.php';

$GroupID = (int) $_GET['id'];
if ($GroupID === 0) {
    error('Bad ID parameter', true);
}

$TorrentDetails = get_group_info($GroupID, true, 0, false);
$TorrentDetails = $TorrentDetails[0];
$Image = $TorrentDetails['WikiImage'];

// Handle no artwork
if (!$Image) {
    $Image = staticServer.'common/noartwork.png';
}

json_die('success', array(
  'picture' => $Image
));

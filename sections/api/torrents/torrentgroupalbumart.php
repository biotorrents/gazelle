<?php

declare(strict_types=1);


$GroupID = (int) $_GET['id'];
if ($GroupID === 0) {
    error('Bad ID parameter', true);
}

$TorrentDetails = TorrentFunctions::get_group_info($GroupID, true, 0, false);
$TorrentDetails = $TorrentDetails[0];
$Image = $TorrentDetails['WikiImage'];

// Handle no artwork
if (!$Image) {
    $Image = staticServer.'common/noartwork.png';
}

json_die('success', array(
  'picture' => $Image
));

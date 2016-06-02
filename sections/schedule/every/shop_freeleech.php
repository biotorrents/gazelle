<?
//------------- BP Shop Freeleeches -------------------------------------//

$date = date('Y-m-d H:i:s');
$DB->query("
	SELECT DISTINCT t.GroupID, sf.TorrentID
	FROM shop_freeleeches AS sf
	JOIN torrents AS t
		ON sf.TorrentID = t.ID
	WHERE
		sf.ExpiryTime < '".$date."'");

$TorrentIDs = array();
if ($DB->has_results()) {
	while (list($GroupID, $TorrentID) = $DB->next_record()) {
		$TorrentIDs[] = $TorrentID;
		$Cache->delete_value("torrents_details_$GroupID");
		$Cache->delete_value("torrent_group_$GroupID");
	}
	Torrents::freeleech_torrents($TorrentIDs, 0, 0);
	$DB->query("
		DELETE FROM shop_freeleeches
		WHERE ExpiryTime < '".$date."'");
  $Cache->delete_value('shop_freeleech_list');
}

// Also clear site_options for expired freeleech
$DB->query("
	DELETE FROM site_options
	WHERE Comment = 'freeleech'
		AND CAST(Value AS UNSIGNED INTEGER) < " . date('U'));
?>

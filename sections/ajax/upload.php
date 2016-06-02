<?
// echo out the slice of the form needed for the selected upload type ($_GET['section']).


// Include the necessary form class
include(SERVER_ROOT.'/classes/torrent_form.class.php');
$TorrentForm = new TORRENT_FORM();

$GenreTags = $Cache->get_value('genre_tags');
if (!$GenreTags) {
	$DB->query('
		SELECT Name
		FROM tags
		WHERE TagType=\'genre\'
		ORDER BY Name');
	$GenreTags = $DB->collect('Name');
	$Cache->cache_value('genre_tags', $GenreTags, 3600 * 24);
}

$UploadForm = $Categories[$_GET['categoryid']];

switch ($UploadForm) {
	case 'Movies':
		$TorrentForm->movies_form($GenreTags);
		break;

	case 'Anime':
		$TorrentForm->anime_form($_GET['categoryid']);
		break;
	case 'Manga':
		$TorrentForm->manga_form($_GET['categoryid']);
		break;
	case 'Games':
		$TorrentForm->game_form($_GET['categoryid']);
		break;
	case 'Other':
		$TorrentForm->simple_form($_GET['categoryid']);
		break;
	default:
		echo 'Invalid action!';
}

?>

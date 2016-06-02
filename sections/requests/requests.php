<?php
$SphQL = new SphinxqlQuery();
$SphQL->select('id, votes, bounty')->from('requests, requests_delta');

$SortOrders = array(
	'votes' => 'votes',
	'bounty' => 'bounty',
	'lastvote' => 'lastvote',
	'filled' => 'timefilled',
	'year' => 'year',
	'created' => 'timeadded',
	'random' => false);

if (empty($_GET['order']) || !isset($SortOrders[$_GET['order']])) {
	$_GET['order'] = 'created';
}
$OrderBy = $_GET['order'];

if (!empty($_GET['sort']) && $_GET['sort'] === 'asc') {
	$OrderWay = 'asc';
} else {
	$_GET['sort'] = 'desc';
	$OrderWay = 'desc';
}
$NewSort = $_GET['sort'] === 'asc' ? 'desc' : 'asc';

if ($OrderBy === 'random') {
	$SphQL->order_by('RAND()', '');
	unset($_GET['page']);
} else {
	$SphQL->order_by($SortOrders[$OrderBy], $OrderWay);
}

$Submitted = !empty($_GET['submit']);

//Paranoia
if (!empty($_GET['userid'])) {
	if (!is_number($_GET['userid'])) {
		error('User ID must be an integer');
	}
	$UserInfo = Users::user_info($_GET['userid']);
	if (empty($UserInfo)) {
		error('That user does not exist');
	}
	$Perms = Permissions::get_permissions($UserInfo['PermissionID']);
	$UserClass = $Perms['Class'];
}
$BookmarkView = false;

if (empty($_GET['type'])) {
	$Title = 'Requests';
	if (empty($_GET['showall'])) {
		$SphQL->where('visible', 1);
	}
} else {
	switch ($_GET['type']) {
		case 'created':
			if (!empty($UserInfo)) {
				if (!check_paranoia('requestsvoted_list', $UserInfo['Paranoia'], $Perms['Class'], $UserInfo['ID'])) {
					error(403);
				}
				$Title = "Requests created by $UserInfo[Username]";
				$SphQL->where('userid', $UserInfo['ID']);
			} else {
				$Title = 'My requests';
				$SphQL->where('userid', $LoggedUser['ID']);
			}
			break;
		case 'voted':
			if (!empty($UserInfo)) {
				if (!check_paranoia('requestsvoted_list', $UserInfo['Paranoia'], $Perms['Class'], $UserInfo['ID'])) {
					error(403);
				}
				$Title = "Requests voted for by $UserInfo[Username]";
				$SphQL->where('voter', $UserInfo['ID']);
			} else {
				$Title = 'Requests I have voted on';
				$SphQL->where('voter', $LoggedUser['ID']);
			}
			break;
		case 'filled':
			if (!empty($UserInfo)) {
				if (!check_paranoia('requestsfilled_list', $UserInfo['Paranoia'], $Perms['Class'], $UserInfo['ID'])) {
					error(403);
				}
				$Title = "Requests filled by $UserInfo[Username]";
				$SphQL->where('fillerid', $UserInfo['ID']);
			} else {
				$Title = 'Requests I have filled';
				$SphQL->where('fillerid', $LoggedUser['ID']);
			}
			break;
		case 'bookmarks':
			$Title = 'Your bookmarked requests';
			$BookmarkView = true;
			$SphQL->where('bookmarker', $LoggedUser['ID']);
			break;
		default:
			error(404);
	}
}

if ($Submitted && empty($_GET['show_filled'])) {
	$SphQL->where('torrentid', 0);
}

if (!empty($_GET['formats'])) {
	$FormatArray = $_GET['formats'];
	if (count($FormatArray) !== count($Formats)) {
		$FormatNameArray = array();
		foreach ($FormatArray as $Index => $MasterIndex) {
			if (isset($Formats[$MasterIndex])) {
				$FormatNameArray[$Index] = '"' . strtr(Sphinxql::sph_escape_string($Formats[$MasterIndex]), '-.', '  ') . '"';
			}
		}
		if (count($FormatNameArray) >= 1) {
			if (!empty($_GET['formats_strict'])) {
				$SearchString = '(' . implode(' | ', $FormatNameArray) . ')';
			} else {
				$SearchString = '(any | ' . implode(' | ', $FormatNameArray) . ')';
			}
			$SphQL->where_match($SearchString, 'formatlist', false);
		}
	}
}

if (!empty($_GET['media'])) {
	$MediaArray = $_GET['media'];
	if (count($MediaArray) !== count($Media)) {
		$MediaNameArray = array();
		foreach ($MediaArray as $Index => $MasterIndex) {
			if (isset($Media[$MasterIndex])) {
				$MediaNameArray[$Index] = '"' . strtr(Sphinxql::sph_escape_string($Media[$MasterIndex]), '-.', '  ') . '"';
			}
		}

		if (count($MediaNameArray) >= 1) {
			if (!empty($_GET['media_strict'])) {
				$SearchString = '(' . implode(' | ', $MediaNameArray) . ')';
			} else {
				$SearchString = '(any | ' . implode(' | ', $MediaNameArray) . ')';
			}
			$SphQL->where_match($SearchString, 'medialist', false);
		}
	}
}

if (!empty($_GET['bitrates'])) {
	$BitrateArray = $_GET['bitrates'];
	if (count($BitrateArray) !== count($Bitrates)) {
		$BitrateNameArray = array();
		foreach ($BitrateArray as $Index => $MasterIndex) {
			if (isset($Bitrates[$MasterIndex])) {
				$BitrateNameArray[$Index] = '"' . strtr(Sphinxql::sph_escape_string($Bitrates[$MasterIndex]), '-.', '  ') . '"';
			}
		}

		if (count($BitrateNameArray) >= 1) {
			if (!empty($_GET['bitrate_strict'])) {
				$SearchString = '(' . implode(' | ', $BitrateNameArray) . ')';
			} else {
				$SearchString = '(any | ' . implode(' | ', $BitrateNameArray) . ')';
			}
			$SphQL->where_match($SearchString, 'bitratelist', false);
		}
	}
}

if (!empty($_GET['search'])) {
	$SearchString = trim($_GET['search']);

	if ($SearchString !== '') {
		$SearchWords = array('include' => array(), 'exclude' => array());
		$Words = explode(' ', $SearchString);
		foreach ($Words as $Word) {
			$Word = trim($Word);
			// Skip isolated hyphens to enable "Artist - Title" searches
			if ($Word === '-') {
				continue;
			}
			if ($Word[0] === '!' && strlen($Word) >= 2) {
				if (strpos($Word, '!', 1) === false) {
					$SearchWords['exclude'][] = $Word;
				} else {
					$SearchWords['include'][] = $Word;
				}
			} elseif ($Word !== '') {
				$SearchWords['include'][] = $Word;
			}
		}
	}
}

if (!isset($_GET['tags_type']) || $_GET['tags_type'] === '1') {
	$TagType = 1;
	$_GET['tags_type'] = '1';
} else {
	$TagType = 0;
	$_GET['tags_type'] = '0';
}

if (!empty($_GET['tags'])) {
	$SearchTags = array('include' => array(), 'exclude' => array());
	$Tags = explode(',', str_replace('.', '_', $_GET['tags']));
	foreach ($Tags as $Tag) {
		$Tag = trim($Tag);
		if ($Tag[0] === '!' && strlen($Tag) >= 2) {
			if (strpos($Tag, '!', 1) === false) {
				$SearchTags['exclude'][] = $Tag;
			} else {
				$SearchTags['include'][] = $Tag;
			}
		} elseif ($Tag !== '') {
			$SearchTags['include'][] = $Tag;
		}
	}

	$TagFilter = Tags::tag_filter_sph($SearchTags, $TagType);
	$TagNames = $TagFilter['input'];

	if (!empty($TagFilter['predicate'])) {
		$SphQL->where_match($TagFilter['predicate'], 'taglist', false);
	}

} elseif (!isset($_GET['tags_type']) || $_GET['tags_type'] !== '0') {
	$_GET['tags_type'] = 1;
} else {
	$_GET['tags_type'] = 0;
}

if (isset($SearchWords)) {
	$QueryParts = array();
	foreach ($SearchWords['include'] as $Word) {
		$QueryParts[] = Sphinxql::sph_escape_string($Word);
	}
	if (!empty($SearchWords['exclude'])) {
		foreach ($SearchWords['exclude'] as $Word) {
			$QueryParts[] = '!' . Sphinxql::sph_escape_string(substr($Word, 1));
		}
	}
	if (!empty($QueryParts)) {
		$SearchString = implode(' ', $QueryParts);
		$SphQL->where_match($SearchString, '*', false);
	}
}

if (!empty($_GET['filter_cat'])) {
	$CategoryArray = array_keys($_GET['filter_cat']);
	if (count($CategoryArray) !== count($Categories)) {
		foreach ($CategoryArray as $Key => $Index) {
			if (!isset($Categories[$Index - 1])) {
				unset($CategoryArray[$Key]);
			}
		}
		if (count($CategoryArray) >= 1) {
			$SphQL->where('categoryid', $CategoryArray);
		}
	}
}

if (!empty($_GET['releases'])) {
	$ReleaseArray = $_GET['releases'];
	if (count($ReleaseArray) !== count($ReleaseTypes)) {
		foreach ($ReleaseArray as $Index => $Value) {
			if (!isset($ReleaseTypes[$Value])) {
				unset($ReleaseArray[$Index]);
			}
		}
		if (count($ReleaseArray) >= 1) {
			$SphQL->where('releasetype', $ReleaseArray);
		}
	}
}

if (!empty($_GET['requestor'])) {
	if (is_number($_GET['requestor'])) {
		$SphQL->where('userid', $_GET['requestor']);
	} else {
		error(404);
	}
}

if (isset($_GET['year'])) {
	if (is_number($_GET['year']) || $_GET['year'] === '0') {
		$SphQL->where('year', $_GET['year']);
	} else {
		error(404);
	}
}

if (!empty($_GET['page']) && is_number($_GET['page']) && $_GET['page'] > 0) {
	$Page = $_GET['page'];
	$Offset = ($Page - 1) * REQUESTS_PER_PAGE;
	$SphQL->limit($Offset, REQUESTS_PER_PAGE, $Offset + REQUESTS_PER_PAGE);
} else {
	$Page = 1;
	$SphQL->limit(0, REQUESTS_PER_PAGE, REQUESTS_PER_PAGE);
}

$SphQLResult = $SphQL->query();
$NumResults = (int)$SphQLResult->get_meta('total_found');
if ($NumResults > 0) {
	$SphRequests = $SphQLResult->to_array('id');
	if ($OrderBy === 'random') {
		$NumResults = count($SphRequests);
	}
	if ($NumResults > REQUESTS_PER_PAGE) {
		if (($Page - 1) * REQUESTS_PER_PAGE > $NumResults) {
			$Page = 0;
		}
		$PageLinks = Format::get_pages($Page, $NumResults, REQUESTS_PER_PAGE);
	}
}

$CurrentURL = Format::get_url(array('order', 'sort', 'page'));
View::show_header($Title, 'requests');

?>
<div class="thin">
	<div class="header">
		<h2><?=$Title?></h2>
	</div>
	<div class="linkbox">
<?	if (!$BookmarkView) {
		if (check_perms('site_submit_requests')) { ?>
		<a href="requests.php?action=new" class="brackets">New request</a>
		<a href="requests.php?type=created" class="brackets">My requests</a>
<?		}
		if (check_perms('site_vote')) { ?>
		<a href="requests.php?type=voted" class="brackets">Requests I've voted on</a>
<?		} ?>
		<a href="bookmarks.php?type=requests" class="brackets">Bookmarked requests</a>
<?	} else { ?>
		<a href="bookmarks.php?type=torrents" class="brackets">Torrents</a>
		<a href="bookmarks.php?type=artists" class="brackets">Artists</a>
		<a href="bookmarks.php?type=collages" class="brackets">Collections</a>
		<a href="bookmarks.php?type=requests" class="brackets">Requests</a>
<?	} ?>
	</div>
<?	if ($BookmarkView && $NumResults === 0) { ?>
	<div class="box pad" align="center">
		<h2>You have not bookmarked any requests.</h2>
	</div>
<?	} else { ?>
	<form class="search_form" name="requests" action="" method="get">
<?		if ($BookmarkView) { ?>
		<input type="hidden" name="action" value="view" />
		<input type="hidden" name="type" value="requests" />
<?		} elseif (isset($_GET['type'])) { ?>
		<input type="hidden" name="type" value="<?=$_GET['type']?>" />
<?		} ?>
		<input type="hidden" name="submit" value="true" />
<?		if (!empty($_GET['userid']) && is_number($_GET['userid'])) { ?>
		<input type="hidden" name="userid" value="<?=$_GET['userid']?>" />
<?		} ?>
		<div class="box pad">
		<table cellpadding="6" cellspacing="1" border="0" class="layout" width="100%">
			<tr id="search_terms">
				<td class="label"><!--Search terms:--></td>
				<td>
					<input type="search" name="search" size="60" class="inputtext" placeholder="Search terms" value="<? if (isset($_GET['search'])) { echo display_str($_GET['search']); } ?>" />
				</td>
			</tr>
			<tr id="tagfilter">
				<td class="label"><!--Tags (comma-separated):--></td>
				<td>
					<input type="search" name="tags" id="tags" size="49" class="inputtext" placeholder="Tags (comma-separated)" value="<?=!empty($TagNames) ? display_str($TagNames) : ''?>"<? Users::has_autocomplete_enabled('other'); ?> />&nbsp;
					<input type="radio" name="tags_type" id="tags_type0" value="0"<? Format::selected('tags_type', 0, 'checked')?> /><label for="tags_type0"> Any</label>&nbsp;&nbsp;
					<input type="radio" name="tags_type" id="tags_type1" value="1"<? Format::selected('tags_type', 1, 'checked')?> /><label for="tags_type1"> All</label>
				</td>
			</tr>
			<tr id="include_filled">
				<td class="label"><label for="include_filled_box">Include filled:</label></td>
				<td>
					<input type="checkbox" id="include_filled_box" name="show_filled"<? if (!$Submitted || !empty($_GET['show_filled']) || (!$Submitted && !empty($_GET['type']) && $_GET['type'] === 'filled')) { ?> checked="checked"<? } ?> />
				</td>
			</tr>
			<tr id="include_old">
				<td class="label"><label for="include_old_box">Include old:</label></td>
				<td>
					<input type="checkbox" id="include_old_box" name="showall"<? if (!empty($_GET['showall'])) { ?> checked="checked"<? } ?> />
				</td>
			</tr>
<?		/* ?>
			<tr>
				<td class="label">Requested by:</td>
				<td>
					<input type="search" name="requester" size="75" value="<?=display_str($_GET['requester'])?>" />
				</td>
			</tr>
<?		*/ ?>
		</table>
		<table class="layout cat_list">
<?
		$x = 1;
		reset($Categories);
		foreach ($Categories as $CatKey => $CatName) {
			if ($x % 8 === 0 || $x === 1) {
?>
				<tr>
<?			} ?>
					<td>
						<input type="checkbox" name="filter_cat[<?=($CatKey + 1) ?>]" id="cat_<?=($CatKey + 1) ?>" value="1"<? if (isset($_GET['filter_cat'][$CatKey + 1])) { ?> checked="checked"<? } ?> />
						<label for="cat_<?=($CatKey + 1) ?>"><?=$CatName?></label>
					</td>
<?			if ($x % 7 === 0) { ?>
				</tr>
<?
			}
			$x++;
		}
?>
		</table>
		<table class="layout">
			<tr>
				<td colspan="2" class="center">
					<input type="submit" value="Search requests" />
				</td>
			</tr>
		</table>
		</div>
	</form>
<?		if (isset($PageLinks)) { ?>
	<div class="linkbox">
		<?=	$PageLinks?>
	</div>
<?		} ?>
	<table id="request_table" class="request_table border" cellpadding="6" cellspacing="1" border="0" width="100%">
		<tr class="colhead_dark">
			<td class="small cats_col"></td>
			<td style="width: 38%;" class="nobr">
				<strong>Request Name</strong> / <a href="?order=year&amp;sort=<?=($OrderBy === 'year' ? $NewSort : 'desc')?>&amp;<?=$CurrentURL?>"><strong>Year</strong></a>
			</td>
			<td class="nobr">
				<a href="?order=votes&amp;sort=<?=($OrderBy === 'votes' ? $NewSort : 'desc')?>&amp;<?=$CurrentURL?>"><strong>Votes</strong></a>
			</td>
			<td class="nobr">
				<a href="?order=bounty&amp;sort=<?=($OrderBy === 'bounty' ? $NewSort : 'desc')?>&amp;<?=$CurrentURL?>"><strong>Bounty</strong></a>
			</td>
			<td class="nobr">
				<a href="?order=filled&amp;sort=<?=($OrderBy === 'filled' ? $NewSort : 'desc')?>&amp;<?=$CurrentURL?>"><strong>Filled</strong></a>
			</td>
			<td class="nobr">
				<strong>Filled by</strong>
			</td>
			<td class="nobr">
				<strong>Requested by</strong>
			</td>
			<td class="nobr">
				<a href="?order=created&amp;sort=<?=($OrderBy === 'created' ? $NewSort : 'desc')?>&amp;<?=$CurrentURL?>"><strong>Created</strong></a>
			</td>
			<td class="nobr">
				<a href="?order=lastvote&amp;sort=<?=($OrderBy === 'lastvote' ? $NewSort : 'desc')?>&amp;<?=$CurrentURL?>"><strong>Last vote</strong></a>
			</td>
		</tr>
<?
		if ($NumResults === 0) {
			// not viewing bookmarks but no requests found
?>
		<tr class="row">
			<td colspan="8">
				Nothing found!
			</td>
		</tr>
<?		} elseif ($Page === 0) { ?>
		<tr class="row">
			<td colspan="8">
				The requested page contains no matches!
			</td>
		</tr>
<?
		} else {

	$TimeCompare = 1267643718; // Requests v2 was implemented 2010-03-03 20:15:18
	$Requests = Requests::get_requests(array_keys($SphRequests));
	foreach ($Requests as $RequestID => $Request) {
		$SphRequest = $SphRequests[$RequestID];
		$Bounty = $SphRequest['bounty'] * 1024; // Sphinx stores bounty in kB
		$VoteCount = $SphRequest['votes'];

		if ($Request['CategoryID'] == 0) {
			$CategoryName = 'Unknown';
		} else {
			$CategoryName = $Categories[$Request['CategoryID'] - 1];
		}

		if ($Request['TorrentID'] != 0) {
			$IsFilled = true;
			$FillerInfo = Users::user_info($Request['FillerID']);
		} else {
			$IsFilled = false;
		}

		if ($CategoryName != 'Other') {
			$ArtistForm = Requests::get_artists($RequestID);
			$ArtistLink = Artists::display_artists($ArtistForm, true, true);
			$FullName = "$ArtistLink<a href=\"requests.php?action=view&amp;id=$RequestID\"><span ";
			if (!isset($LoggedUser['CoverArt']) || $LoggedUser['CoverArt']) {
				$FullName .= 'onmouseover="getCover(event)" cover="'.ImageTools::process($Request['Image']).'" onmouseleave="ungetCover(event)" ';
			}
			$FullName .= "dir=\"ltr\">$Request[Title]</span></a>";

			$ExtraInfo = '';

			if (!empty($Request['CatalogueNumber'])) {
				$ExtraInfo .= " [$Request[CatalogueNumber]]";
			}

			if (!empty($Request['DLsiteID'])) {
				$ExtraInfo .= " [$Request[DLsiteID]]";
			}
			if ($ExtraInfo) {
				$FullName .= " $ExtraInfo";
			}
		} else {
			$FullName = "<a href=\"requests.php?action=view&amp;id=$RequestID\" dir=\"ltr\">$Request[Title]</a>";
		}
		$Tags = $Request['Tags'];
?>
		<tr class="request">
			<td class="center cats_col">
				<div title="<?=Format::pretty_category($Request['CategoryID'])?>" class="tooltip <?=Format::css_category($Request['CategoryID'])?>"></div>
			</td>
			<td>
				<?=$FullName?>
				<div class="tags">
<?
		$TagList = array();
		foreach ($Request['Tags'] as $TagID => $TagName) {
			$TagList[] = '<a href="?tags='.$TagName.($BookmarkView ? '&amp;type=requests' : '').'">'.display_str($TagName).'</a>';
		}
		$TagList = implode(', ', $TagList);
?>
					<?=$TagList?>
				</div>
			</td>
			<td class="nobr">
				<span id="vote_count_<?=$RequestID?>"><?=number_format($VoteCount)?></span>
<?	 	if (!$IsFilled && check_perms('site_vote')) { ?>
				&nbsp;&nbsp; <a href="javascript:Vote(0, <?=$RequestID?>)" class="brackets"><strong>+</strong></a>
<?		} ?>
			</td>
			<td class="number_column nobr">
				<?=Format::get_size($Bounty)?>
			</td>
			<td class="nobr">
<?		if ($IsFilled) { ?>
				<a href="torrents.php?<?=(strtotime($Request['TimeFilled']) < $TimeCompare ? 'id=' : 'torrentid=') . $Request['TorrentID']?>"><strong><?=time_diff($Request['TimeFilled'], 1)?></strong></a>
<?		} else { ?>
				<strong>No</strong>
<?		} ?>
			</td>
			<td>
<?		if ($IsFilled) { ?>
				<a href="user.php?id=<?=$FillerInfo['ID']?>"><?=$FillerInfo['Username']?></a>
<?		} else { ?>
				&mdash;
<?		} ?>
			</td>
			<td>
				<a href="user.php?id=<?=$Request['UserID']?>"><?=Users::format_username($Request['UserID'], false, false, false)?></a>
			</td>
			<td class="nobr">
				<?=time_diff($Request['TimeAdded'], 1)?>
			</td>
			<td class="nobr">
				<?=time_diff($Request['LastVote'], 1)?>
			</td>
		</tr>
<?
	} // foreach
		} // else
	} // if ($BookmarkView && $NumResults < 1)
?>
	</table>
<? if (isset($PageLinks)) { ?>
	<div class="linkbox">
		<?=$PageLinks?>
	</div>
<? } ?>
</div>
<? View::show_footer(); ?>

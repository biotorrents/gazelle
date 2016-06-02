<?

//******************************************************************************//
//----------------- Take request -----------------------------------------------//
authorize();


if ($_POST['action'] !== 'takenew' && $_POST['action'] !== 'takeedit') {
	error(0);
}

$NewRequest = ($_POST['action'] === 'takenew');

if (!$NewRequest) {
	$ReturnEdit = true;
}

if ($NewRequest) {
	if (!check_perms('site_submit_requests') || $LoggedUser['BytesUploaded'] < 250 * 1024 * 1024) {
		error(403);
	}
} else {
	$RequestID = $_POST['requestid'];
	if (!is_number($RequestID)) {
		error(0);
	}

	$Request = Requests::get_request($RequestID);
	if ($Request === false) {
		error(404);
	}
	$VoteArray = Requests::get_votes_array($RequestID);
	$VoteCount = count($VoteArray['Voters']);
	$IsFilled = !empty($Request['TorrentID']);
	$CategoryName = $Categories[$Request['CategoryID'] - 1];
	$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && ($Request['CategoryID'] === '0' || ($CategoryName === 'Music' && $Year === '0')));
	$CanEdit = ((!$IsFilled && $LoggedUser['ID'] === $Request['UserID'] && $VoteCount < 2) || $ProjectCanEdit || check_perms('site_moderate_requests'));

	if (!$CanEdit) {
		error(403);
	}
}

// Validate
if (empty($_POST['type'])) {
	error(0);
}

$CategoryName = $_POST['type'];
$CategoryID = (array_search($CategoryName, $Categories) + 1);

if (empty($CategoryID)) {
	error(0);
}

if (empty($_POST['title'])) {
	$Err = 'You forgot to enter the title!';
} else {
	$Title = trim($_POST['title']);
}

if (empty($_POST['title_jp'])) {
	$Err = 'You forgot to enter the Japanese title!';
} else {
	$TitleJP = trim($_POST['title_jp']);
}

if (empty($_POST['tags'])) {
	$Err = 'You forgot to enter any tags!';
} else {
	$Tags = trim($_POST['tags']);
}

if ($NewRequest) {
	if (empty($_POST['amount'])) {
		$Err = 'You forgot to enter any bounty!';
	} else {
		$Bounty = trim($_POST['amount']);
		if (!is_number($Bounty)) {
			$Err = 'Your entered bounty is not a number';
		} elseif ($Bounty < 100 * 1024 * 1024) {
			$Err = 'Minimum bounty is 100 MB.';
		}
		$Bytes = $Bounty; //From MB to B
	}
}

if (empty($_POST['image'])) {
	$Image = '';
} else {
	ImageTools::blacklisted($_POST['image']);
	if (preg_match('/'.IMAGE_REGEX.'/', trim($_POST['image'])) > 0) {
			$Image = trim($_POST['image']);
	} else {
		$Err = display_str($_POST['image']).' does not appear to be a valid link to an image.';
	}
}

if (empty($_POST['description'])) {
	$Err = 'You forgot to enter a description.';
} else {
	$Description = trim($_POST['description']);
}

if ($CategoryName != 'Other') {
	if (empty($_POST['artists'])) {
		$Err = 'You did not enter any artists.';
	} else {
		$Artists = $_POST['artists'];
	}

	//Not required
	if (!empty($_POST['cataloguenumber']) && $CategoryName == 'Movies') {
		$CatalogueNumber = trim($_POST['cataloguenumber']);
	} else {
		$CatalogueNumber = '';
	}
	if (!empty($_POST['dlsiteid']) && $CategoryName == 'Games') {
		$DLSiteID = trim($_POST['dlsiteid']);
	} else {
		$DLSiteID = '';
	}

}

// GroupID
if (!empty($_POST['groupid'])) {
  $GroupID = $_POST['groupid'];
  if (is_number($GroupID)) {
    $DB->query("
      SELECT CategoryID
      FROM torrents_group
      WHERE ID = '$GroupID'");
    if (!$DB->has_results()) {
      $Err = 'The torrent group, if entered, must correspond to a torrent group on the site.';
    } else {
      if ($CategoryID != $DB->to_array()[0]['CategoryID']) {
        $Err = 'The category of the specified torrent group does not match the category of your request.';
      }
    }
  } else {
    $Err = 'The torrent group, if entered, must correspond to a torrent group on the site.';
  }
} elseif (isset($_POST['groupid']) && $_POST['groupid'] === '0') {
  $GroupID = 0;
}

//For refilling on error
if ($CategoryName != 'Other') {
	$ArtistNames = array();
	$ArtistForm = array();
	for ($i = 0; $i < count($Artists); $i++) {
		if (trim($Artists[$i]) !== '') {
			if (!in_array($Artists[$i], $ArtistNames)) {
				$ArtistForm[] = array('name' => trim($Artists[$i]));
				$ArtistNames[] = trim($Artists[$i]);
			}
		}
	}
	if (!isset($ArtistNames[0])) {
		unset($ArtistForm);
	}
}

if (!empty($Err)) {
	error($Err);
	$Div = $_POST['unit'] === 'mb' ? 1024 * 1024 : 1024 * 1024 * 1024;
	$Bounty /= $Div;
	include(SERVER_ROOT.'/sections/requests/new_edit.php');
	die();
}

//Databasify the input
/*if ($CategoryName === 'Music') {
	if (empty($AllBitrates)) {
		foreach ($BitrateArray as $Index => $MasterIndex) {
			if (array_key_exists($Index, $Bitrates)) {
				$BitrateArray[$Index] = $Bitrates[$MasterIndex];
			} else {
				//Hax
				error(0);
			}
		}
		$BitrateList = implode('|', $BitrateArray);
	} else {
		$BitrateList = 'Any';
	}

	if (empty($AllFormats)) {
		foreach ($FormatArray as $Index => $MasterIndex) {
			if (array_key_exists($Index, $Formats)) {
				$FormatArray[$Index] = $Formats[$MasterIndex];
			} else {
				//Hax
				error(0);
			}
		}
		$FormatList = implode('|', $FormatArray);
	} else {
		$FormatList = 'Any';
	}

	if (empty($AllMedia)) {
		foreach ($MediaArray as $Index => $MasterIndex) {
			if (array_key_exists($Index, $Media)) {
				$MediaArray[$Index] = $Media[$MasterIndex];
			} else {
				//Hax
				error(0);
			}
		}
		$MediaList = implode('|', $MediaArray);
	} else {
		$MediaList = 'Any';
	}

	$LogCue = '';
	if ($NeedLog) {
		$LogCue .= 'Log';
		if ($MinLogScore > 0) {
			if ($MinLogScore >= 100) {
				$LogCue .= ' (100%)';
			} else {
				$LogCue .= ' (>= '.$MinLogScore.'%)';
			}
		}
	}
	if ($NeedCue) {
		if ($LogCue !== '') {
			$LogCue .= ' + Cue';
		} else {
			$LogCue = 'Cue';
		}
	}
}*/

if (!isset($GroupID)) $GroupID = '';

//Query time!
if ($NewRequest) {
	$DB->query('
		INSERT INTO requests (
			UserID, TimeAdded, LastVote, CategoryID, Title, TitleJP, Image, Description,
			CatalogueNumber, DLSiteID, Visible, GroupID)
		VALUES
			('.$LoggedUser['ID'].", '".sqltime()."', '".sqltime()."', $CategoryID, '".db_string($Title)."', '".db_string($TitleJP)."', '".db_string($Image)."', '".db_string($Description)."',
					'".db_string($CatalogueNumber)."', '".db_string($DLSiteID)."', '1', '$GroupID')");

	$RequestID = $DB->inserted_id();

} else {
	$DB->query("
		UPDATE requests
		SET CategoryID = $CategoryID,
			Title = '".db_string($Title)."',
			TitleJP = '".db_string($TitleJP)."',
			Image = '".db_string($Image)."',
			Description = '".db_string($Description)."',
			CatalogueNumber = '".db_string($CatalogueNumber)."',
			DLSiteID = '".db_string($DLSiteID)."'
		WHERE ID = $RequestID");

	// We need to be able to delete artists / tags
	$DB->query("
		SELECT ArtistID
		FROM requests_artists
		WHERE RequestID = $RequestID");
	$RequestArtists = $DB->to_array();
	foreach ($RequestArtists as $RequestArtist) {
		$Cache->delete_value("artists_requests_".$RequestArtist['ArtistID']);
	}
	$DB->query("
		DELETE FROM requests_artists
		WHERE RequestID = $RequestID");
	$Cache->delete_value("request_artists_$RequestID");
}

if ($GroupID) {
	$Cache->delete_value("requests_group_$GroupID");
}

/*
 * Multiple Artists!
 * For the multiple artists system, we have 3 steps:
 * 1. See if each artist given already exists and if it does, grab the ID.
 * 2. For each artist that didn't exist, create an artist.
 * 3. Create a row in the requests_artists table for each artist, based on the ID.
 */
if (isset($CategoryName) && $CategoryName != "Other" && isset($ArtistForm)) {
	foreach ($ArtistForm as $Num => $Artist) {
		//1. See if each artist given already exists and if it does, grab the ID.
		$DB->query("
			SELECT
				ArtistID,
				Name
			FROM artists_group
			WHERE Name = '".db_string($Artist['name'])."'");

		list($ArtistID, $ArtistName) = $DB->next_record(MYSQLI_NUM, false);
		$ArtistForm[$Num] = array('name' => $ArtistName, 'id' => $ArtistID);

		if (!$ArtistID) {
			//2. For each artist that didn't exist, create an artist.
			$DB->query("
				INSERT INTO artists_group (Name)
				VALUES ('".db_string($Artist['name'])."')");
			$ArtistID = $DB->inserted_id();

			$Cache->increment('stats_artist_count');

			$ArtistForm[$Num] = array('id' => $ArtistID, 'name' => $Artist['name']);
		}
	}


	//3. Create a row in the requests_artists table for each artist, based on the ID.
	foreach ($ArtistForm as $Num => $Artist) {
		$DB->query("
			INSERT IGNORE INTO requests_artists
				(RequestID, ArtistID)
			VALUES
				($RequestID, ".$Artist['id'].")");
		$Cache->delete_value('artists_requests_'.$Artist['id']);
	}
	//End Music only

} else {
	//Not a music request anymore, delete music only fields.
	if (!$NewRequest) {
		$DB->query("
			SELECT ArtistID
			FROM requests_artists
			WHERE RequestID = $RequestID");
		$OldArtists = $DB->collect('ArtistID');
		foreach ($OldArtists as $ArtistID) {
			if (empty($ArtistID)) {
				continue;
			}
			//Get a count of how many groups or requests use the artist ID
			$DB->query("
				SELECT COUNT(ag.ArtistID)
				FROM artists_group AS ag
					LEFT JOIN requests_artists AS ra ON ag.ArtistID = ra.ArtistID
				WHERE ra.ArtistID IS NOT NULL
					AND ag.ArtistID = '$ArtistID'");
			list($ReqCount) = $DB->next_record();
			$DB->query("
				SELECT COUNT(ag.ArtistID)
				FROM artists_group AS ag
					LEFT JOIN torrents_artists AS ta ON ag.ArtistID = ta.ArtistID
				WHERE ta.ArtistID IS NOT NULL
					AND ag.ArtistID = '$ArtistID'");
			list($GroupCount) = $DB->next_record();
			if (($ReqCount + $GroupCount) == 0) {
				//The only group to use this artist
				Artists::delete_artist($ArtistID);
			} else {
				//Not the only group, still need to clear cache
				$Cache->delete_value("artists_requests_$ArtistID");
			}
		}
		$DB->query("
			DELETE FROM requests_artists
			WHERE RequestID = $RequestID");
		$Cache->delete_value("request_artists_$RequestID");
	}
}

//Tags
if (!$NewRequest) {
	$DB->query("
		DELETE FROM requests_tags
		WHERE RequestID = $RequestID");
}

$Tags = array_unique(explode(',', $Tags));
foreach ($Tags as $Index => $Tag) {
	$Tag = Misc::sanitize_tag($Tag);
	$Tag = Misc::get_alias_tag($Tag);
	$Tags[$Index] = $Tag; //For announce
	$DB->query("
		INSERT INTO tags
			(Name, UserID)
		VALUES
			('$Tag', ".$LoggedUser['ID'].")
		ON DUPLICATE KEY UPDATE
			Uses = Uses + 1");

	$TagID = $DB->inserted_id();

	$DB->query("
		INSERT IGNORE INTO requests_tags
			(TagID, RequestID)
		VALUES
			($TagID, $RequestID)");
}

if ($NewRequest) {
	//Remove the bounty and create the vote
	$DB->query("
		INSERT INTO requests_votes
			(RequestID, UserID, Bounty)
		VALUES
			($RequestID, ".$LoggedUser['ID'].', '.($Bytes * (1 - $RequestTax)).')');

	$DB->query("
		UPDATE users_main
		SET Uploaded = (Uploaded - $Bytes)
		WHERE ID = ".$LoggedUser['ID']);
	$Cache->delete_value('user_stats_'.$LoggedUser['ID']);



	if ($CategoryName != 'Other') {
		$Announce = "\"$Title\"".(isset($ArtistForm)?(' - '.Artists::display_artists($ArtistForm, false, false)):'').' '.site_url()."requests.php?action=view&id=$RequestID - ".implode(' ', $Tags);
	} else {
		$Announce = "\"$Title\" - ".site_url()."requests.php?action=view&id=$RequestID - ".implode(' ', $Tags);
	}
	send_irc('PRIVMSG '.BOT_REQUEST_CHAN.' '.$Announce);

} else {
	$Cache->delete_value("request_$RequestID");
	$Cache->delete_value("request_artists_$RequestID");
}

Requests::update_sphinx_requests($RequestID);

header("Location: requests.php?action=view&id=$RequestID");
?>

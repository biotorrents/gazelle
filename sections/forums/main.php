<?
$LastRead = Forums::get_last_read($Forums);
View::show_header('Forums');
?>
<div class="thin">
	<h2>Forums</h2>
	<div class="forum_list">
<?

$LastCategoryID = 0;
$OpenTable = false;
foreach ($Forums as $Forum) {
	list($ForumID, $CategoryID, $ForumName, $ForumDescription, $MinRead, $MinWrite, $MinCreate, $NumTopics, $NumPosts, $LastPostID, $LastAuthorID, $LastTopicID, $LastTime, $SpecificRules, $LastTopic, $Locked, $Sticky) = array_values($Forum);
	if (!Forums::check_forumperm($ForumID)) {
		continue;
	}
	// No
/*
	if ($ForumID == DONOR_FORUM) {
		$ForumDescription = Donations::get_forum_description();
	}
	$Tooltip = $ForumID == DONOR_FORUM ? 'tooltip_gold' : 'tooltip';
*/
	$Tooltip = 'tooltip';

	$ForumDescription = display_str($ForumDescription);

	if ($CategoryID != $LastCategoryID) {
		$LastCategoryID = $CategoryID;
		if ($OpenTable) { ?>
	</table>
<? } ?>
<h3><?=$ForumCats[$CategoryID]?></h3>
	<table class="forum_index">
		<tr class="colhead">
			<td style="width: 2%;"></td>
			<td style="width: 25%;">Forum</td>
			<td>Last Post</td>
			<td style="width: 7%;">Topics</td>
			<td style="width: 7%;">Posts</td>
		</tr>
<?
		$OpenTable = true;
	}

	$Read = Forums::is_unread($Locked, $Sticky, $LastPostID, $LastRead, $LastTopicID, $LastTime) ? 'unread' : 'read';
/* Removed per request, as distracting
	if ($Locked) {
		$Read .= '_locked';
	}
	if ($Sticky) {
		$Read .= '_sticky';
	}
*/
?>
	<tr class="row">
		<td class="<?=$Read?> <?=$Tooltip?>" title="<?=ucfirst($Read)?>"></td>
		<td>
			<h4 class="min_padding">
				<a class="<?=$Tooltip?>" href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>" title="<?=display_str($ForumDescription)?>"><?=display_str($ForumName)?></a>
			</h4>
		</td>
<? if ($NumPosts == 0) { ?>
		<td>
			There are no topics here.<?=(($MinCreate <= $LoggedUser['Class']) ? ' <a href="forums.php?action=new&amp;forumid='.$ForumID.'">Create one!</a>' : '')?>
		</td>
		<td class="number_column">0</td>
		<td class="number_column">0</td>
<? } else { ?>
		<td>
			<span style="float: left;" class="last_topic">
				<a href="forums.php?action=viewthread&amp;threadid=<?=$LastTopicID?>" class="tooltip" data-title-plain="<?=display_str($LastTopic)?>"><?=display_str(Format::cut_string($LastTopic, 50, 1))?></a>
			</span>
<? if (!empty($LastRead[$LastTopicID])) { ?>
      <a class="<?=$Tooltip?> last_read" title="Jump to last read" href="forums.php?action=viewthread&amp;threadid=<?=$LastTopicID?>&amp;page=<?=$LastRead[$LastTopicID]['Page']?>#post<?=$LastRead[$LastTopicID]['PostID']?>">
        <svg width="15" height="11"><polygon points="0,3 0,8 8,8 8,11 15,5.5 8,0 8,3"/></svg>
      </a>
<? } ?>
			<span style="float: right;" class="last_poster">by <?=Users::format_username($LastAuthorID, false, false, false)?> <?=time_diff($LastTime, 1)?></span>
		</td>
		<td class="number_column"><?=number_format($NumTopics)?></td>
		<td class="number_column"><?=number_format($NumPosts)?></td>
<? } ?>
	</tr>
<? } ?>
	</table>
	</div>
	<div class="linkbox"><a href="forums.php?action=catchup&amp;forumid=all&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Catch up</a></div>
</div>

<? View::show_footer(); ?>

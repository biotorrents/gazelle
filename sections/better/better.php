<?
//Include the header
View::show_header('Better');
?>
<div class="thin">
	<h3 id="general">Pursuit of Perfection</h3>
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
		<p>Here at <?=SITE_NAME?>, we believe that there's always room for improvement. To aid our effort in the pursuit of perfection, we've put together a few simple lists that can help you build ratio and help us improve our overall quality. Most lists feature 100 torrents at a time and update every 15 minutes.</p>
	</div>
	<h3 id="lists">Lists</h3>
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
		<table width="100%">
			<tr class="colhead">
				<td style="width: 150px;">Method</td>
				<td style="width: 400px;">Additional Information</td>
			</tr>
			<tr class="row">
				<td class="nobr">
					<a href="better.php?method=screenshots">Screenshots</a>
				</td>
				<td class="nobr">
          When a torrent doesn't have enough screenshots
				</td>
			</tr>
			<tr class="row">
				<td class="nobr">
					<a href="better.php?method=encode">Encode</a>
				</td>
				<td class="nobr">
          When a high quality BD or ISO is available and we don't have any encodes of it
				</td>
			</tr>
			<tr class="row">
				<td class="nobr">
					<a href="better.php?method=snatch">Snatch</a>
				</td>
				<td class="nobr">
					Torrents you've already downloaded that can be encoded.
				</td>
			</tr>
			<tr class="row">
				<td class="nobr">
					<a href="better.php?method=upload">Upload</a>
				</td>
				<td class="nobr">
					Torrents you've uploaded that could be improved.
				</td>
			</tr>
			<tr class="row">
				<td class="nobr">
					<a href="better.php?method=tags&amp;filter=all">Tags</a>
				</td>
				<td class="nobr">
					Torrents that have been marked as having "Very bad tags" or "No tags at all".
				</td>
			</tr>
			<tr class="row">
				<td class="nobr">
					<a href="better.php?method=folders">Folder names</a>
				</td>
				<td class="nobr">
					Torrents that have been marked as having "Very bad folder names" or "No folder names at all".
				</td>
			</tr>
			<tr class="row">
				<td class="nobr">
					<a href="better.php?method=files">File names</a>
				</td>
				<td class="nobr">
					Torrents that have been marked as having "Bad File Names".
				</td>
			</tr>

		</table>
	</div>
</div>
<? View::show_footer(); ?>

<?php
#declare(strict_types=1);

declare(strict_types=1);


/**
 * main torrent search interface
 */

$app = \Gazelle\App::go();

# it's actually way better if this uses GET
$get = Http::request("get");

# workaround for main navigation search
$get["search"] ??= null;
if ($get["search"]) {
    $get["simpleSearch"] = $get["search"];
}


/** torrent search handling */


# collect the query
$searchTerms = [
    "simpleSearch" => $get["simpleSearch"] ?? null,
    "complexSearch" => $get["complexSearch"] ?? null,

    "numbers" => $get["numbers"] ?? null,
    "year" => $get["year"] ?? null,

    "location" => $get["location"] ?? null,
    "creator" => $get["creator"] ?? null,

    "description" => $get["description"] ?? null,
    "fileList" => $get["fileList"] ?? null,

    "platforms" => $get["platforms"] ?? [],
    "formats" => $get["formats"] ?? [],
    "archives" => $get["archives"] ?? [],

    "scopes" => $get["scopes"] ?? [],
    "alignment" => $get["alignment"] ?? null,
    "leechStatus" => $get["leechStatus"] ?? null,
    "licenses" => $get["licenses"] ?? [],

    "sizeMin" => $get["sizeMin"] ?? null,
    "sizeMax" => $get["sizeMax"] ?? null,
    "sizeUnit" => $get["sizeUnit"] ?? null,

    "categories" => $get["categories"] ?? [],
    "tagList" => $get["tagList"] ?? [],
    "tagsType" => $get["tagsType"] ?? null,

    "orderBy" => $get["orderBy"] ?? "timeAdded",
    "orderWay" => $get["orderWay"] ?? "desc",
    "groupResults" => $get["groupResults"] ?? $app->user->siteOptions["torrentGrouping"],

    "page" => $get["page"] ?? 1,

    "openaiContent" => $get["openaiContent"] ?? $app->user->siteOptions["openaiContent"],
];

# build query string (saving/sharing)
foreach ($get as $key => $value) {
    if (empty($value)) {
        unset($get[$key]);
    }
}

$get["page"] ??= null;
unset($get["page"]);

$queryString = http_build_query($get);
#!d($queryString);

# search manticore
$manticore = new Gazelle\Manticore();
$searchResults = $manticore->search("collections", $get);
$resultCount = count($searchResults);


/** pagination */


$pagination = [];

# resultCount and pageSize
$pagination["resultCount"] = count($searchResults);
$pagination["pageSize"] = $app->user->extra["siteOptions"]["searchPagination"] ?? 20;

# current page
$pagination["currentPage"] = intval($searchTerms["page"] ?? 1);
if (empty($pagination["currentPage"]) || $pagination["currentPage"] !== abs($pagination["currentPage"])) {
    $pagination["currentPage"] = 1;
}

# last page
$pagination["lastPage"] = ceil($pagination["resultCount"] / $pagination["pageSize"]);
if ($pagination["currentPage"] > $pagination["lastPage"]) {
    $pagination["currentPage"] = $pagination["lastPage"];
}

# previous page
$pagination["previousPage"] = $pagination["currentPage"] - 1;
if (empty($pagination["previousPage"]) || abs($pagination["previousPage"]) !== $pagination["previousPage"]) {
    $pagination["previousPage"] = 1;
}

# next page
$pagination["nextPage"] = $pagination["currentPage"] + 1;

# first page
$pagination["firstPage"] = 1;

# offset and limit
$pagination["offset"] = intval(($pagination["currentPage"] - 1) * $pagination["pageSize"]);
$pagination["limit"] = $pagination["offset"] + $pagination["pageSize"];

if ($pagination["limit"] > $pagination["resultCount"]) {
    $pagination["limit"] = $pagination["resultCount"];
}


/** torrent group info */


# Torrents::get_groups
# this is slow, only do the current page
$app->debug["time"]->startMeasure("browse", "get collages");

$collageIds = array_column($searchResults, "id");
$collageIds = array_slice($collageIds, $pagination["offset"], $pagination["pageSize"]);

$collages = [];
foreach ($collageIds as $collageId) {
    $collages[] = new Collages($collageId);
}

$app->debug["time"]->stopMeasure("browse", "get collages");


/** tags */


$query = "select name from tags where tagType = 'genre' order by name";
$ref = $app->dbNew->multi($query, []);
$officialTags = array_column($ref, "name");


/** twig template */

$app->twig->display("collages/browse.twig", [
    "title" => "Browse collages",
    "js" => ["vendor/tom-select.base.min", "browse"],
    "css" => ["vendor/tom-select.bootstrap5.min"],

    "categories" => $app->env->collageCategories,

    "searchResults" => $searchResults,
    "collages" => $collages,

    "bookmarks" => Bookmarks::all_bookmarks('torrent'),
    "officialTags" => $officialTags,

    "searchTerms" => $searchTerms,
    "pagination" => $pagination,
    "queryString" => $queryString,
]);

exit;

/*********** */
$app = \Gazelle\App::go();

define('COLLAGES_PER_PAGE', 25);

list($Page, $Limit) = Format::page_limit(COLLAGES_PER_PAGE);

$OrderVals = array('Time', 'Name', 'Subscribers', 'Torrents', 'Updated');
$WayVals = array('Ascending', 'Descending');
$OrderTable = array('Time' => 'ID', 'Name' => 'c.Name', 'Subscribers' => 'c.Subscribers', 'Torrents' => 'NumTorrents', 'Updated' => 'c.Updated');
$WayTable = array('Ascending' => 'ASC', 'Descending' => 'DESC');

// Are we searching in bodies, or just names?
if (!empty($_GET['type'])) {
    $Type = $_GET['type'];
    if (!in_array($Type, array('c.name', 'description'))) {
        $Type = 'c.name';
    }
} else {
    $Type = 'c.name';
}

if (!empty($_GET['search'])) {
    // What are we looking for? Let's make sure it isn't dangerous
    $Search = db_string(trim($_GET['search']));
    // Break search string down into individual words
    $Words = explode(' ', $Search);
}

if (!empty($_GET['tags'])) {
    $Tags = explode(',', db_string(trim($_GET['tags'])));
    foreach ($Tags as $ID => $Tag) {
        $Tags[$ID] = Misc::sanitize_tag($Tag);
    }
}

if (!empty($_GET['cats'])) {
    $Categories = $_GET['cats'];
    foreach ($Categories as $Cat => $Accept) {
        if (empty($CollageCats[$Cat]) || !$Accept) {
            unset($Categories[$Cat]);
        }
    }
    $Categories = array_keys($Categories);
} else {
    $Categories = array(1, 2, 3, 4, 5, 6, 7);
}

// Ordering
if (!empty($_GET['order_by']) && !empty($OrderTable[$_GET['order_by']])) {
    $Order = $OrderTable[$_GET['order_by']];
} else {
    $Order = 'ID';
}

if (!empty($_GET['order_way']) && !empty($WayTable[$_GET['order_way']])) {
    $Way = $WayTable[$_GET['order_way']];
} else {
    $Way = 'DESC';
}

$BookmarkView = !empty($_GET['bookmarks']);

if ($BookmarkView) {
    $Categories[] = 0;
    $BookmarkJoin = 'INNER JOIN bookmarks_collages AS bc ON c.ID = bc.CollageID';
} else {
    $BookmarkJoin = '';
}

$BaseSQL = $SQL = "
  SELECT
    SQL_CALC_FOUND_ROWS
    c.ID,
    c.Name,
    c.NumTorrents,
    c.TagList,
    c.CategoryID,
    c.UserID,
    c.Subscribers,
    c.Updated
  FROM collages AS c
    $BookmarkJoin
  WHERE Deleted = '0'";

if ($BookmarkView) {
    $SQL .= " AND bc.UserID = '" . $app->user->core['id'] . "'";
}

if (!empty($Search)) {
    $SQL .= " AND $Type LIKE '%";
    $SQL .= implode("%' AND $Type LIKE '%", $Words);
    $SQL .= "%'";
}

if (isset($_GET['tags_type']) && $_GET['tags_type'] === '0') { // Any
    $_GET['tags_type'] = '0';
} else { // All
    $_GET['tags_type'] = '1';
}

if (!empty($Tags)) {
    $SQL.= " AND (TagList LIKE '%";
    if ($_GET['tags_type'] === '0') {
        $SQL .= implode("%' OR TagList LIKE '%", $Tags);
    } else {
        $SQL .= implode("%' AND TagList LIKE '%", $Tags);
    }
    $SQL .= "%')";
}

if (!empty($_GET['userid'])) {
    $UserID = $_GET['userid'];
    if (!is_numeric($UserID)) {
        error(404);
    }
    $User = User::user_info($UserID);
    $Perms = Permissions::get_permissions($User['PermissionID']);
    $UserClass = $Perms['Class'];

    $UserLink = '<a href="user.php?id='.$UserID.'">'.$User['Username'].'</a>';
    if (!empty($_GET['contrib'])) {
        if (!check_paranoia('collagecontribs', $User['Paranoia'], $UserClass, $UserID)) {
            error(403);
        }
        $app->dbOld->query("
      SELECT DISTINCT CollageID
      FROM collages_torrents
      WHERE UserID = $UserID");
        $CollageIDs = $app->dbOld->collect('CollageID');
        if (empty($CollageIDs)) {
            $SQL .= " AND 0";
        } else {
            $SQL .= " AND c.ID IN(".db_string(implode(',', $CollageIDs)).')';
        }
    } else {
        if (!check_paranoia('collages', $User['Paranoia'], $UserClass, $UserID)) {
            error(403);
        }
        $SQL .= " AND UserID = '".$_GET['userid']."'";
    }
    $Categories[] = 0;
}

if (!empty($Categories)) {
    $SQL .= " AND CategoryID IN(".db_string(implode(',', $Categories)).')';
}

if (isset($_GET['action']) && $_GET['action'] === 'mine') {
    $SQL = $BaseSQL;
    $SQL .= "
    AND c.UserID = '".$app->user->core['id']."'
    AND c.CategoryID = 0";
}

$SQL .= "
  ORDER BY $Order $Way
  LIMIT $Limit";
$app->dbOld->query($SQL);
$Collages = $app->dbOld->to_array();
$app->dbOld->query('SELECT FOUND_ROWS()');
list($NumResults) = $app->dbOld->next_record();

View::header(($BookmarkView) ? 'Your bookmarked collections' : 'Collections');
?>

<div>
  <div class="header">
    <?php if ($BookmarkView) { ?>
    <h2>Your bookmarked collections</h2>
    <?php } else { ?>
    <h2>Collections<?=(!empty($UserLink) ? (isset($CollageIDs) ? " with contributions by $UserLink" : " started by $UserLink") : '')?>
    </h2>
    <?php } ?>
  </div>
  <?php if (!$BookmarkView) { ?>
  <div class="box pad">
    <form class="search_form" name="collages" action="" method="get">
      <div>
        <input type="hidden" name="action" value="search">
      </div>

      <table cellpadding="6" cellspacing="1" border="0" class="skeletonFix" width="100%">
        <tr id="search_terms">
          <td class="label"></td>
          <td>
            <input type="search" name="search" size="60" placeholder="Search terms"
              value="<?=(!empty($_GET['search']) ? \Gazelle\Text::esc($_GET['search']) : '')?>">
          </td>
        </tr>

        <tr id="tagfilter">
          <td class="label"></td>
          <td>
            <input type="text" id="tags" name="tags" size="60" placeholder="Tags (comma-separated)"
              value="<?=(!empty($_GET['tags']) ? \Gazelle\Text::esc($_GET['tags']) : '')?>">
            &ensp;
            <input type="radio" name="tags_type" id="tags_type0" value="0" <?Format::selected(
    'tags_type',
    0,
    'checked'
)?> />

            <label for="tags_type0"> Any</label>&nbsp;&nbsp;
            <input type="radio" name="tags_type" id="tags_type1" value="1" <?Format::selected(
                'tags_type',
                1,
                'checked'
            )?> />

            <label for="tags_type1"> All</label>
          </td>
        </tr>

        <tr id="categories">
          <td class="label">Categories</td>
          <td>
            <?php foreach ($CollageCats as $ID => $Cat) { ?>
            <input type="checkbox" value="1" name="cats[<?=$ID?>]"
              id="cats_<?=$ID?>" <?php if (in_array($ID, $Categories)) {
                  echo ' checked="checked"' ;
              } ?> />
            <label for="cats_<?=$ID?>"><?=$Cat?></label>&nbsp;&nbsp;
            <?php } ?>
          </td>
        </tr>
        <tr id="search_name_description">
          <td class="label">Search In</td>
          <td>
            <input type="radio" name="type" value="c.name" <?php if ($Type==='c.name') {
                echo 'checked="checked" ' ;
            }
      ?>/> Names&nbsp;&nbsp;
            <input type="radio" name="type" value="description" <?php if ($Type==='description') {
                echo 'checked="checked" ' ;
            } ?>> Descriptions
          </td>
        </tr>
        <tr id="order_by">
          <td class="label">Order By</td>
          <td>
            <select name="order_by" class="ft_order_by">
              <?php foreach ($OrderVals as $Cur) { ?>
              <option value="<?=$Cur?>" <?php if (isset($_GET['order_by']) && $_GET['order_by']===$Cur || (!isset($_GET['order_by']) && $Cur==='Time')) {
                  echo ' selected="selected"' ;
              } ?>><?=$Cur?>
              </option>
              <?php } ?>
            </select>
            <select name="order_way" class="ft_order_way">
              <?php foreach ($WayVals as $Cur) { ?>
              <option value="<?=$Cur?>" <?php if (isset($_GET['order_way']) && $_GET['order_way']===$Cur || (!isset($_GET['order_way']) &&
                $Cur==='Descending')) {
                  echo ' selected="selected"' ;
              } ?>><?=$Cur?>
              </option>
              <?php } ?>
            </select>
          </td>
        </tr>
        <tr>
          <td colspan="2" class="center">
            <input type="submit" class="button-primary" value="Search">
          </td>
        </tr>
      </table>
    </form>
  </div>
  <?php } // if (!$BookmarkView)?>
  <div class="linkbox">
    <?php
  if (!$BookmarkView) {
      if (check_perms('site_collages_create')) {
          ?>
    <a href="collages.php?action=new" class="brackets">New collection</a>
    <?php
      }
      if (check_perms('site_collages_personal')) {
          $app->dbOld->query("
        SELECT ID
        FROM collages
        WHERE UserID = '{$app->user->core['id']}'
          AND CategoryID = '0'
          AND Deleted = '0'");
          $CollageCount = $app->dbOld->record_count();

          if ($CollageCount === 1) {
              list($CollageID) = $app->dbOld->next_record(); ?>
    <a href="collages.php?id=<?=$CollageID?>"
      class="brackets">Personal collection</a>
    <?php
          } elseif ($CollageCount > 1) { ?>
    <a href="collages.php?action=mine" class="brackets">Personal collections</a>
    <?php
          }
      }
      if (check_perms('site_collages_subscribe')) {
          ?>
    <a href="userhistory.php?action=subscribed_collages" class="brackets">Subscribed collections</a>
    <?php
      } ?>
    <a href="bookmarks.php?type=collages" class="brackets">Bookmarked collections</a>
    <?php if (check_perms('site_collages_recover')) { ?>
    <a href="collages.php?action=recover" class="brackets">Recover collection</a>
    <?php
    }
      if (check_perms('site_collages_create') || check_perms('site_collages_personal') || check_perms('site_collages_recover')) {
          ?>
    <br>
    <?php
      } ?>
    <a href="collages.php?userid=<?=$app->user->core['id']?>"
      class="brackets">Collections you started</a>
    <a href="collages.php?userid=<?=$app->user->core['id']?>&amp;contrib=1"
      class="brackets">Collections you contributed to</a>
    <br><br>
    <?php
  } else { ?>
    <a href="bookmarks.php?type=torrents" class="brackets">Torrents</a>
    <a href="bookmarks.php?type=artists" class="brackets">Artists</a>
    <a href="bookmarks.php?type=collages" class="brackets">Collections</a>
    <a href="bookmarks.php?type=requests" class="brackets">Requests</a>
    <br>
    <?php
  }
  $Pages = Format::get_pages($Page, $NumResults, COLLAGES_PER_PAGE, 9);
echo $Pages;
?>
  </div>
  <?php if (count($Collages) === 0) { ?>
  <div class="box pad" align="center">
    <?php if ($BookmarkView) { ?>
    <h2>You have not bookmarked any collections.</h2>
    <?php } else { ?>
    <h2>Your search did not match anything.</h2>
    <p>Make sure all names are spelled correctly, or try making your search less specific.</p>
    <?php } ?>
  </div>
  <!--box-->
</div>
<!--content-->
<?php View::footer();
      error();
  }
?>
<table width="100%" id="collage_table" class="collage_table box">
  <tr class="colhead">
    <td>Category</td>
    <td>Collection</td>
    <td>Torrents</td>
    <td>Subscribers</td>
    <td>Updated</td>
    <td>Author</td>
  </tr>
  <?php
foreach ($Collages as $Collage) {
    list($ID, $Name, $NumTorrents, $TagList, $CategoryID, $UserID, $Subscribers, $Updated) = $Collage;
    $TorrentTags = new Tags($TagList);

    // Print results?>
  <tr
    class="row<?=($BookmarkView) ? " bookmark_$ID" : ''; ?>">
    <td class="center">
      <a
        href="collages.php?action=search&amp;cats[<?=(int)$CategoryID?>]=1"><?=$CollageCats[(int)$CategoryID]?></a>
    </td>
    <td>
      <a class="torrentTitle" id="collage_name"
        href="collages.php?id=<?=$ID?>"><?=$Name?></a>
      <?php if ($BookmarkView) { ?>
      <span class="u-pull-right">
        <a href="#"
          onclick="Unbookmark('collage', <?=$ID?>, ''); return false;"
          class="brackets">Remove bookmark</a>
      </span>
      <?php } ?>
      <div class="tags"><?=$TorrentTags->format('collages.php?action=search&amp;tags=')?>
      </div>
    </td>
    <td class="number_column"><?=\Gazelle\Text::float((int)$NumTorrents)?>
    </td>
    <td class="number_column"><?=\Gazelle\Text::float((int)$Subscribers)?>
    </td>
    <td class="nobr"><?=time_diff(intval($Updated))?>
    </td>
    <td><?=User::format_username($UserID, false, false, false)?>
    </td>
  </tr>
  <?php
}
?>
</table>
<div class="linkbox"><?=$Pages?>
</div>
</div>
<?php View::footer();

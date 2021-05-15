<?php
#declare(strict_types=1);

# todo: Go through line by line
if (isset($LoggedUser['PostsPerPage'])) {
    $PerPage = $LoggedUser['PostsPerPage'];
} else {
    $PerPage = POSTS_PER_PAGE;
}

// We have to iterate here because if one is empty it breaks the query
$TopicIDs = [];
foreach ($Forums as $Forum) {
    if (!empty($Forum['LastPostTopicID'])) {
        $TopicIDs[] = $Forum['LastPostTopicID'];
    }
}

// Now if we have IDs' we run the query
if (!empty($TopicIDs)) {
    $DB->query("
    SELECT
      l.TopicID,
      l.PostID,
      CEIL(
        (
          SELECT COUNT(p.ID)
          FROM forums_posts AS p
          WHERE p.TopicID = l.TopicID
            AND p.ID <= l.PostID
        ) / $PerPage
      ) AS Page
    FROM forums_last_read_topics AS l
    WHERE l.TopicID IN(".implode(',', $TopicIDs).")
      AND l.UserID = '$LoggedUser[ID]'");
    $LastRead = $DB->to_array('TopicID', MYSQLI_ASSOC);
} else {
    $LastRead = [];
}

$DB->query("
  SELECT RestrictedForums
  FROM users_info
  WHERE UserID = ".$LoggedUser['ID']);
list($RestrictedForums) = $DB->next_record();
$RestrictedForums = explode(',', $RestrictedForums);
$PermittedForums = array_keys($LoggedUser['PermittedForums']);

$JsonCategories = [];
$JsonCategory = [];
$JsonForums = [];
foreach ($Forums as $Forum) {
    list($ForumID, $CategoryID, $ForumName, $ForumDescription, $MinRead, $MinWrite, $MinCreate, $NumTopics, $NumPosts, $LastPostID, $LastAuthorID, $LastTopicID, $LastTime, $SpecificRules, $LastTopic, $Locked, $Sticky) = array_values($Forum);
    if ($LoggedUser['CustomForums'][$ForumID] != 1
      && ($MinRead > $LoggedUser['Class']
      || array_search($ForumID, $RestrictedForums) !== false)
  ) {
        continue;
    }
    $ForumDescription = display_str($ForumDescription);

    if ($CategoryID != $LastCategoryID) {
        if (!empty($JsonForums) && !empty($JsonCategory)) {
            $JsonCategory['forums'] = $JsonForums;
            $JsonCategories[] = $JsonCategory;
        }
        $LastCategoryID = $CategoryID;
        $JsonCategory = array(
      'categoryID' => (int)$CategoryID,
      'categoryName' => $ForumCats[$CategoryID]
    );
        $JsonForums = [];
    }

    if ((!$Locked || $Sticky)
      && $LastPostID != 0
      && ((empty($LastRead[$LastTopicID]) || $LastRead[$LastTopicID]['PostID'] < $LastPostID)
        && strtotime($LastTime) > $LoggedUser['CatchupTime'])
  ) {
        $Read = 'unread';
    } else {
        $Read = 'read';
    }
    $UserInfo = Users::user_info($LastAuthorID);

    $JsonForums[] = array(
    'forumId' => (int)$ForumID,
    'forumName' => $ForumName,
    'forumDescription' => $ForumDescription,
    'numTopics' => (float)$NumTopics,
    'numPosts' => (float)$NumPosts,
    'lastPostId' => (float)$LastPostID,
    'lastAuthorId' => (float)$LastAuthorID,
    'lastPostAuthorName' => $UserInfo['Username'],
    'lastTopicId' => (float)$LastTopicID,
    'lastTime' => $LastTime,
    'specificRules' => $SpecificRules,
    'lastTopic' => display_str($LastTopic),
    'read' => $Read === 1,
    'locked' => $Locked === 1,
    'sticky' => $Sticky === 1
  );
}
// ...And an extra one to catch the last category.
if (!empty($JsonForums) && !empty($JsonCategory)) {
    $JsonCategory['forums'] = $JsonForums;
    $JsonCategories[] = $JsonCategory;
}

echo json_encode(
    array(
    'status' => 'success',
    'response' => array(
      'categories' => $JsonCategories
    )
  )
);

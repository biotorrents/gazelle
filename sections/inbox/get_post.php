<?php

#declare(strict_types=1);

$app = App::go();

// todo: make this use the cache version of the thread, save the db query

/*********************************************************************\
//--------------Get Post--------------------------------------------//

This gets the raw BBCode of a post. It's used for editing and
quoting posts.

It gets called if $_GET['action'] == 'get_post'. It requires
$_GET['post'], which is the ID of the post.

\*********************************************************************/

// Quick SQL injection check
if (!$_GET['post'] || !is_numeric($_GET['post'])) {
    error(0);
}

// Variables for database input
$PostID = $_GET['post'];

// Message is selected providing the user quoting is one of the two people in the thread
$app->dbOld->query("
  SELECT m.Body
  FROM pm_messages AS m
    JOIN pm_conversations_users AS u ON m.ConvID = u.ConvID
  WHERE m.ID = '$PostID'
    AND u.UserID = ".$app->userNew->core['id']);
list($Body) = $app->dbOld->next_record(MYSQLI_NUM);
$Body = apcu_exists('DBKEY') ? Crypto::decrypt($Body) : '[Encrypted]';

// This gets sent to the browser, which echoes it wherever

echo trim($Body);

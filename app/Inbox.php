<?php

#declare(strict_types=1);


/**
 * THIS IS GOING AWAY
 */

class Inbox
{
    /**
     * getMessagesForUser
     * 
     * Returns an array of messages for a user.
     * 
     * @param int|string $userId
     * @return ?array
     */
    public static function getMessagesForUser($userId): ?array
    {
        $app = \Gazelle\App::go();

        $query = "
            select * from pm_conversations
            left join pm_conversations_users on pm_conversations_users.conversationId = pm_conversations.id AND pm_conversations_users.userId = ?
            left join users_main on users_main.userId = pm_conversations_users.userId
            join pm_messages on pm_conversations.id = pm_messages.conversationId
            where pm_conversations_users.userId = ?
        ";
        return $app->dbNew->multi($query, [ $userId, $userId ]);

        $sql = "
  SELECT
    SQL_CALC_FOUND_ROWS
    c.ID,
    c.Subject,
    cu.Unread,
    cu.Sticky,
    cu.ForwardedTo,
    cu2.UserID,";
$sql .= $Section === 'sentbox' ? ' cu.SentDate ' : ' cu.ReceivedDate ';
$sql .= "AS Date
  FROM pm_conversations AS c
    LEFT JOIN pm_conversations_users AS cu ON cu.ConvID = c.ID AND cu.UserID = '$UserID'
    LEFT JOIN pm_conversations_users AS cu2 ON cu2.ConvID = c.ID AND cu2.UserID != '$UserID' AND cu2.ForwardedTo = 0
    LEFT JOIN users_main AS um ON um.ID = cu2.UserID";

if (!empty($_GET['search']) && $_GET['searchtype'] === 'message') {
    $sql .= ' JOIN pm_messages AS m ON c.ID = m.ConvID';
}


        # database query
        $query = "select * from messages where userId = ?";
        return $app->db->get($query, [ $userId ]);
    }



    /*
     * Get the link to a user's inbox.
     * This is what handles the ListUnreadPMsFirst setting
     *
     * @param string - whether the inbox or sentbox should be loaded
     * @return string - the URL to a user's inbox
     */
    public static function get_inbox_link($WhichBox = 'inbox')
    {
        $app = \Gazelle\App::go();

        $ListFirst = isset($app->user->extra['ListUnreadPMsFirst']) ? $app->user->extra['ListUnreadPMsFirst'] : false;

        if ($WhichBox === 'inbox') {
            if ($ListFirst) {
                $InboxURL = 'inbox.php?sort=unread';
            } else {
                $InboxURL = 'inbox.php';
            }
        } else {
            if ($ListFirst) {
                $InboxURL = 'inbox.php?action=sentbox&amp;sort=unread';
            } else {
                $InboxURL = 'inbox.php?action=sentbox';
            }
        }
        return $InboxURL;
    }
}

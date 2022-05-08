<?php
#declare(strict_types=1);

class Collages
{
    public static function increase_subscriptions($CollageID)
    {
        $QueryID = G::$db->get_query_id();
        G::$db->prepared_query("
        UPDATE
          `collages`
        SET
          `Subscribers` = `Subscribers` + 1
        WHERE
          `ID` = '$CollageID'
        ");
        G::$db->set_query_id($QueryID);
    }

    public static function decrease_subscriptions($CollageID)
    {
        $QueryID = G::$db->get_query_id();
        G::$db->prepared_query("
        UPDATE
          `collages`
        SET
          `Subscribers` = IF(
            `Subscribers` < 1,
            0,
            `Subscribers` - 1
          )
        WHERE
          `ID` = '$CollageID'
        ");
        G::$db->set_query_id($QueryID);
    }

    public static function create_personal_collage()
    {
        G::$db->prepared_query("
        SELECT
          COUNT(`ID`)
        FROM
          `collages`
        WHERE
          `UserID` = '".G::$user['ID']."' AND `CategoryID` = '0' AND `Deleted` = '0'
        ");
        list($CollageCount) = G::$db->next_record();

        if ($CollageCount >= G::$user['Permissions']['MaxCollages']) {
            // todo: Fix this, the query was for COUNT(ID), so I highly doubt that this works... - Y
            list($CollageID) = G::$db->next_record();
            Http::redirect("collage.php?id=$CollageID");
            error();
        }

        $NameStr = db_string(G::$user['Username']."'s personal collage".($CollageCount > 0 ? ' no. '.($CollageCount + 1) : ''));
        $Description = db_string('Personal collage for '.G::$user['Username'].'. The first 5 albums will appear on his or her [url='.site_url().'user.php?id= '.G::$user['ID'].']profile[/url].');

        G::$db->prepared_query("
        INSERT INTO `collages`(
          `Name`,
          `Description`,
          `CategoryID`,
          `UserID`
        )
        VALUES(
          '$NameStr',
          '$Description',
          '0',
          ".G::$user['ID']."
        )
        ");
          
        $CollageID = G::$db->inserted_id();
        Http::redirect("collage.php?id=$CollageID");
        error();
    }
}

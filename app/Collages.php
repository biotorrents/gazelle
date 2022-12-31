<?php

#declare(strict_types=1);

/**
 * Collages
 */
class Collages
{
    /**
     * increase_subscriptions
     */
    public static function increase_subscriptions($CollageID)
    {
        $app = App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->prepared_query("
        UPDATE
          `collages`
        SET
          `Subscribers` = `Subscribers` + 1
        WHERE
          `ID` = '$CollageID'
        ");
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * decrease_subscriptions
     */
    public static function decrease_subscriptions($CollageID)
    {
        $app = App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->prepared_query("
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
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * create_personal_collage
     */
    public static function create_personal_collage()
    {
        $app = App::go();

        $app->dbOld->prepared_query("
        SELECT
          COUNT(`ID`)
        FROM
          `collages`
        WHERE
          `UserID` = '".$app->userNew['ID']."' AND `CategoryID` = '0' AND `Deleted` = '0'
        ");
        list($CollageCount) = $app->dbOld->next_record();

        if ($CollageCount >= $app->userNew['Permissions']['MaxCollages']) {
            // todo: Fix this, the query was for COUNT(ID), so I highly doubt that this works... - Y
            list($CollageID) = $app->dbOld->next_record();
            Http::redirect("collage.php?id=$CollageID");
            error();
        }

        $NameStr = db_string($app->userNew['Username']."'s personal collage".($CollageCount > 0 ? ' no. '.($CollageCount + 1) : ''));
        $Description = db_string('Personal collage for '.$app->userNew['Username'].'. The first 5 albums will appear on his or her [url='.site_url().'user.php?id= '.$app->userNew['ID'].']profile[/url].');

        $app->dbOld->prepared_query("
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
          ".$app->userNew['ID']."
        )
        ");

        $CollageID = $app->dbOld->inserted_id();
        Http::redirect("collage.php?id=$CollageID");
        error();
    }
}

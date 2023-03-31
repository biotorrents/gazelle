<?php

#declare(strict_types=1);


/**
 * RevisionHistory
 */

class RevisionHistory
{
    /**
     * get_revision_history
     *
     * Read the revision history of an artist or torrent page
     * @param string $Page artists or torrents
     * @param in $PageID
     * @return array
     */
    public static function get_revision_history($Page, $PageID)
    {
        $app = \Gazelle\App::go();

        $Table = ($Page == 'artists') ? 'wiki_artists' : 'wiki_torrents';
        $QueryID = $app->dbOld->get_query_id();

        $app->dbOld->query("
        SELECT
          RevisionID,
          Summary,
          Time,
          UserID
        FROM $Table
          WHERE PageID = $PageID
          ORDER BY RevisionID DESC");

        $Ret = $app->dbOld->to_array();
        $app->dbOld->set_query_id($QueryID);

        return $Ret;
    }
}

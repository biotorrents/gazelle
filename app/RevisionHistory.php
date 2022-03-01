<?php
#declare(strict_types=1);

class RevisionHistory
{
    /**
     * Read the revision history of an artist or torrent page
     * @param string $Page artists or torrents
     * @param in $PageID
     * @return array
     */
    public static function get_revision_history($Page, $PageID)
    {
        $Table = ($Page == 'artists') ? 'wiki_artists' : 'wiki_torrents';
        $QueryID = G::$db->get_query_id();

        G::$db->query("
        SELECT
          RevisionID,
          Summary,
          Time,
          UserID
        FROM $Table
          WHERE PageID = $PageID
          ORDER BY RevisionID DESC");

        $Ret = G::$db->to_array();
        G::$db->set_query_id($QueryID);
        return $Ret;
    }
}

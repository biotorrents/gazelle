<?php

#declare(strict_types=1);


/**
 * MASS_USER_BOOKMARKS_EDITOR
 *
 * This class helps with mass-editing bookmarked torrents.
 * It can later be used for other bookmark tables.
 */
class MASS_USER_BOOKMARKS_EDITOR extends MASS_USER_TORRENTS_EDITOR
{
    /**
     * __construct
     */
    public function __construct($table = "bookmarks_torrents")
    {
        $this->set_table($table);
    }


    /**
     * query_and_clear_cache
     *
     * Runs a SQL query and clears the cache key.
     * $app->cacheNew->delete didn't always work,
     * but setting the key to null, did. (?)
     *
     * @param string $sql
     */
    protected function query_and_clear_cache($sql)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        if (is_string($sql) && $app->dbOld->query($sql)) {
            $app->cacheNew->delete('bookmarks_group_ids_' . $app->user->core["id"]);
        }
        $app->dbOld->set_query_id($QueryID);
    }


    /**
     * mass_remove
     *
     * Uses (checkboxes) $_POST['remove'] to delete entries.
     * Uses an IN() to match multiple items in one query.
     */
    public function mass_remove()
    {
        $app = \Gazelle\App::go();

        $SQL = [];
        foreach ($_POST['remove'] as $GroupID => $K) {
            if (is_numeric($GroupID)) {
                $SQL[] = sprintf('%d', $GroupID);
            }
        }

        if (!empty($SQL)) {
            $SQL = sprintf(
                '
            DELETE FROM %s
              WHERE UserID = %d
              AND GroupID IN (%s)',
                $this->Table,
                $app->user->core["id"],
                implode(', ', $SQL)
            );
            $this->query_and_clear_cache($SQL);
        }
    }


    /**
     * mass_update
     *
     * Uses $_POST['sort'] values to update the DB.
     */
    public function mass_update()
    {
        $app = \Gazelle\App::go();

        $SQL = [];
        foreach ($_POST['sort'] as $GroupID => $Sort) {
            if (is_numeric($Sort) && is_numeric($GroupID)) {
                $SQL[] = sprintf('(%d, %d, %d)', $GroupID, $Sort, $app->user->core["id"]);
            }
        }

        if (!empty($SQL)) {
            $SQL = sprintf(
                '
            INSERT INTO %s
              (GroupID, Sort, UserID)
            VALUES
              %s
            ON DUPLICATE KEY UPDATE
              Sort = VALUES (Sort)',
                $this->Table,
                implode(', ', $SQL)
            );
            $this->query_and_clear_cache($SQL);
        }
    }
} # class

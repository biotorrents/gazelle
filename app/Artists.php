<?php

#declare(strict_types=1);


/**
 * Artists
 */

class Artists
{
    /**
     * Given an array of GroupIDs, return their associated artists.
     *
     * @param array $GroupIDs
     * @return an array of the following form:
     *  GroupID => {
     *    [ArtistType] => {
     *      id, name, aliasid
     *    }
     *  }
     *
     * ArtistType is an int. It can be:
     * 1 => Main artist
     * 2 => Guest artist
     * 4 => Composer
     * 5 => Conductor
     * 6 => DJ
     */
    public static function get_artists($GroupIDs)
    {
        $app = \Gazelle\App::go();

        $Results = [];
        $dbs = [];

        foreach ($GroupIDs as $GroupID) {
            if (!is_numeric($GroupID)) {
                continue;
            }

            $creators = $app->cache->get('groups_artists_'.$GroupID);
            if (is_array($creators)) {
                $Results[$GroupID] = $creators;
            } else {
                $dbs[] = $GroupID;
            }
        }

        if (count($dbs) > 0) {
            $IDs = implode(',', $dbs);
            if (empty($IDs)) {
                $IDs = 'null';
            }

            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->prepared_query("
            SELECT
              ta.`GroupID`,
              ta.`ArtistID`,
              ag.`Name`
            FROM
              `torrents_artists` AS ta
            JOIN `artists_group` AS ag
            ON
              ta.`ArtistID` = ag.`ArtistID`
            WHERE
              ta.`GroupID` IN($IDs)
            ORDER BY
              ta.`GroupID` ASC,
              ag.`Name` ASC;
            ");

            while (list($GroupID, $creatorID, $creatorName) = $app->dbOld->next_record(MYSQLI_BOTH, false)) {
                $Results[$GroupID][] = array('id' => $creatorID, 'name' => $creatorName);
                $New[$GroupID][] = array('id' => $creatorID, 'name' => $creatorName);
            }

            $app->dbOld->set_query_id($QueryID);
            foreach ($dbs as $GroupID) {
                if (isset($New[$GroupID])) {
                    $app->cache->set("groups_artists_$GroupID", $New[$GroupID]);
                } else {
                    $app->cache->set("groups_artists_$GroupID", []);
                }
            }

            $Missing = array_diff($GroupIDs, array_keys($Results));
            if (!empty($Missing)) {
                $Results += array_fill_keys($Missing, []);
            }
        }
        return $Results;
    }

    /**
     * Convenience function for get_artists, when you just need one group.
     *
     * @param int $GroupID
     * @return array - see get_artists
     */
    public static function get_artist($GroupID)
    {
        $Results = Artists::get_artists(array($GroupID));
        return $Results[$GroupID];
    }

    /**
     * Format an array of artists for display.
     * todo: Revisit the logic of this, see if we can helper-function the copypasta.
     *
     * @param array Artists an array of the form output by get_artists
     * @param boolean $MakeLink if true, the artists will be links, if false, they will be text.
     * @param boolean $IncludeHyphen FEATURE REMOVED, ARGUMENT KEPT FOR COMPATIBILITY
     * @param $Escape if true, output will be escaped. Think carefully before setting it false.
     */
    public static function display_artists($creators, $MakeLink = true, $IncludeHyphen = true, $Escape = true)
    {
        if (!empty($creators)) {
            $ampersand = ($Escape) ? ' &amp; ' : ' & ';
            $link = '';

            switch (count($creators)) {
                case 0:
                    break;

                case 4:
                    $link .= Artists::display_artist($creators[2], $MakeLink, $Escape). ", ";
                    // no break

                case 3:
                    $link .= Artists::display_artist($creators[2], $MakeLink, $Escape). ", ";
                    // no break

                case 2:
                    $link .= Artists::display_artist($creators[1], $MakeLink, $Escape). ", ";
                    // no break

                case 1:
                    $link .= Artists::display_artist($creators[0], $MakeLink, $Escape);
                    break;

                default:
                    $link = Artists::display_artist($creators[0], $MakeLink, $Escape).' et al.';
            }

            return $link;
        } else {
            return '';
        }
    }

    /**
     * Formats a single artist name.
     *
     * @param array $creator an array of the form ('id' => ID, 'name' => Name)
     * @param boolean $MakeLink If true, links to the artist page.
     * @param boolean $Escape If false and $MakeLink is false, returns the unescaped, unadorned artist name.
     * @return string Formatted artist name.
     */
    public static function display_artist($creator, $MakeLink = true, $Escape = true)
    {
        if ($MakeLink && !$Escape) {
            error('Invalid parameters to Artists::display_artist()');
        } elseif ($MakeLink) {
            return '<a href="/artist.php?id='.$creator['id'].'">'.\Gazelle\Text::esc($creator['name']).'</a>';
        } elseif ($Escape) {
            return \Gazelle\Text::esc($creator['name']);
        } else {
            return $creator['name'];
        }
    }

    /**
     * Deletes an artist and their requests, wiki, and tags.
     * Does NOT delete their torrents.
     *
     * @param int $creatorID
     */
    public static function delete_artist($creatorID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->prepared_query("
        SELECT
          `NAME`
        FROM
          `artists_group`
        WHERE
          `ArtistID` = $creatorID
        ");
        list($Name) = $app->dbOld->next_record(MYSQLI_NUM, false);

        // Delete requests
        $app->dbOld->prepared_query("
        SELECT
          `RequestID`
        FROM
          `requests_artists`
        WHERE
          `ArtistID` = $creatorID AND `ArtistID` != 0
        ");

        $Requests = $app->dbOld->to_array();
        foreach ($Requests as $Request) {
            list($RequestID) = $Request;
            $app->dbOld->prepared_query("
            DELETE
            FROM
              `requests`
            WHERE
              `ID` = '$RequestID'
            ");

            $app->dbOld->prepared_query("
            DELETE
            FROM
              `requests_votes`
            WHERE
              `RequestID` = '$RequestID'
            ");

            $app->dbOld->prepared_query("
            DELETE
            FROM
              `requests_tags`
            WHERE
              `RequestID` = '$RequestID'
            ");

            $app->dbOld->prepared_query("
            DELETE
            FROM
              `requests_artists`
            WHERE
              `RequestID` = '$RequestID'
            ");
        }

        // Delete artist
        $app->dbOld->prepared_query("
        DELETE
        FROM
          `artists_group`
        WHERE
          `ArtistID` = '$creatorID'
        ");
        $app->cache->decrement('stats_artist_count');

        // Delete wiki revisions
        $app->dbOld->prepared_query("
        DELETE
        FROM
          `wiki_artists`
        WHERE
          `PageID` = '$creatorID'
        ");

        // Delete tags
        $app->dbOld->prepared_query("
        DELETE
        FROM
          `artists_tags`
        WHERE
          `ArtistID` = '$creatorID'
        ");

        // Delete artist comments, subscriptions and quote notifications
        Comments::delete_page('artist', $creatorID);
        $app->cache->delete("artist_$creatorID");
        $app->cache->delete("artist_groups_$creatorID");

        // Record in log
        if (!empty($app->user->core['username'])) {
            $Username = $app->user->core['username'];
        } else {
            $Username = 'System';
        }

        Misc::write_log("Artist $creatorID ($Name) was deleted by $Username");
        $app->dbOld->set_query_id($QueryID);
    }

    /**
     * Remove LRM (left-right-marker) and trims, because people copypaste carelessly.
     * If we don't do this, we get seemingly duplicate artist names.
     * todo: make stricter, e.g., on all whitespace characters or Unicode normalisation
     *
     * @param string $creatorName
     */
    public static function normalise_artist_name($creatorName)
    {
        # \u200e is &lrm;
        $creatorName = trim($creatorName);

        $creatorName = preg_replace("/^(\xE2\x80\x8E)+/", "", $creatorName);
        $creatorName = preg_replace("/(\xE2\x80\x8E)+$/", "", $creatorName);
        $creatorName = trim(preg_replace("/ +/", " ", $creatorName));

        return $creatorName;
    }
} # class

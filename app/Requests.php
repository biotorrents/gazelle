<?php

#declare(strict_types=1);


/**
 * Requests
 */

class Requests
{
    /**
     * update_sphinx_requests
     *
     * Update the sphinx requests delta table for a request.
     *
     * @param $RequestID
     */
    public static function update_sphinx_requests($RequestID)
    {
        $app = \Gazelle\App::go();

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT REPLACE(t.Name, '.', '_')
        FROM tags AS t
          JOIN requests_tags AS rt ON t.ID = rt.TagID
          WHERE rt.RequestID = $RequestID");

        $TagList = $app->dbOld->collect(0, false);
        $TagList = db_string(implode(' ', $TagList));

        $app->dbOld->query("
        REPLACE INTO sphinx_requests_delta (
          ID, UserID, TimeAdded, LastVote, CategoryID, Title, TagList,
          CatalogueNumber, FillerID, TorrentID,
          TimeFilled, Visible, Votes, Bounty)
        SELECT
          ID, r.UserID, UNIX_TIMESTAMP(TimeAdded) AS TimeAdded,
          UNIX_TIMESTAMP(LastVote) AS LastVote, CategoryID, Title, '$TagList',
          CatalogueNumber, FillerID, TorrentID,
          UNIX_TIMESTAMP(TimeFilled) AS TimeFilled, Visible,
          COUNT(rv.UserID) AS Votes, SUM(rv.Bounty) >> 10 AS Bounty
        FROM requests AS r
          LEFT JOIN requests_votes AS rv ON rv.RequestID = r.ID
        WHERE ID = $RequestID
          GROUP BY r.ID");

        $app->dbOld->query("
        UPDATE sphinx_requests_delta
        SET ArtistList = (
          SELECT GROUP_CONCAT(ag.Name SEPARATOR ' ')
          FROM requests_artists AS ra
            JOIN artists_group AS ag ON ag.ArtistID = ra.ArtistID
          WHERE ra.RequestID = $RequestID
            GROUP BY NULL
        )
          WHERE ID = $RequestID");

        $app->dbOld->set_query_id($QueryID);
        $app->cache->delete("request_$RequestID");
    }


    /**
     * get_requests
     *
     * Function to get data from an array of $RequestIDs. Order of keys doesn't matter (let's keep it that way).
     *
     * @param array $RequestIDs
     * @param boolean $Return if set to false, data won't be returned (ie. if we just want to prime the cache.)
     * @return The array of requests.
     * Format: array(RequestID => Associative array)
     * To see what's exactly inside each associate array, peek inside the function. It won't bite.
     */
    // In places where the output from this is merged with sphinx filters, it will be in a different order.
    public static function get_requests($RequestIDs, $Return = true)
    {
        $app = \Gazelle\App::go();

        $Found = $NotFound = array_fill_keys($RequestIDs, false);
        // Try to fetch the requests from the cache first.
        foreach ($RequestIDs as $i => $RequestID) {
            if (!is_numeric($RequestID)) {
                unset($RequestIDs[$i], $Found[$GroupID], $NotFound[$GroupID]);
                continue;
            }

            $Data = $app->cache->get("request_$RequestID");
            if (!empty($Data)) {
                unset($NotFound[$RequestID]);
                $Found[$RequestID] = $Data;
            }
        }

        // Make sure there's something in $RequestIDs, otherwise the SQL will break
        if (count($RequestIDs) === 0) {
            return [];
        }
        $IDs = implode(',', array_keys($NotFound));

        /*
         * Don't change without ensuring you change everything else that uses get_requests()
         */

        if (count($NotFound) > 0) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT
              ID,
              UserID,
              TimeAdded,
              LastVote,
              CategoryID,
              Title,
              Title2,
              TitleJP,
              Image,
              Description,
              CatalogueNumber,
              FillerID,
              TorrentID,
              TimeFilled,
              GroupID
            FROM requests
              WHERE ID IN ($IDs)
              ORDER BY ID");

            $Requests = $app->dbOld->to_array(false, MYSQLI_ASSOC, true);
            $Tags = self::get_tags($app->dbOld->collect('ID', false));

            foreach ($Requests as $Request) {
                $Request['AnonymousFill'] = false;
                if ($Request['FillerID']) {
                    $app->dbOld->query("
                    SELECT Anonymous
                    FROM torrents
                      WHERE ID = ".$Request['TorrentID']);

                    list($Anonymous) = $app->dbOld->next_record();
                    if ($Anonymous) {
                        $Request['AnonymousFill'] = true;
                    }
                }

                unset($NotFound[$Request['ID']]);
                $Request['Tags'] = isset($Tags[$Request['ID']]) ? $Tags[$Request['ID']] : [];
                $Found[$Request['ID']] = $Request;
                $app->cache->set('request_'.$Request['ID'], $Request, 0);
            }
            $app->dbOld->set_query_id($QueryID);

            // Orphan requests. There shouldn't ever be any
            if (count($NotFound) > 0) {
                foreach (array_keys($NotFound) as $GroupID) {
                    unset($Found[$GroupID]);
                }
            }
        }

        if ($Return) { // If we're interested in the data, and not just caching it
            return $Found;
        }
    }


    /**
     * get_request
     *
     * Return a single request. Wrapper for get_requests
     *
     * @param int $RequestID
     * @return request array or false if request doesn't exist. See get_requests for a description of the format
     */
    public static function get_request($RequestID)
    {
        $Request = self::get_requests(array($RequestID));
        if (isset($Request[$RequestID])) {
            return $Request[$RequestID];
        }
        return false;
    }


    /**
     * get_artists
     */
    public static function get_artists($RequestID)
    {
        $app = \Gazelle\App::go();

        $Artists = $app->cache->get("request_artists_$RequestID");
        if (is_array($Artists)) {
            $Results = $Artists;
        } else {
            $Results = [];
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT
              ra.ArtistID,
              ag.Name
            FROM requests_artists AS ra
              JOIN artists_group AS ag ON ra.ArtistID = ag.ArtistID
            WHERE ra.RequestID = $RequestID
              ORDER BY ag.Name ASC;");

            $ArtistRaw = $app->dbOld->to_array();
            $app->dbOld->set_query_id($QueryID);

            foreach ($ArtistRaw as $ArtistRow) {
                list($ArtistID, $ArtistName) = $ArtistRow;
                $Results[] = array('id' => $ArtistID, 'name' => $ArtistName);
            }
            $app->cache->set("request_artists_$RequestID", $Results);
        }
        return $Results;
    }


    /**
     * get_tags
     */
    public static function get_tags($RequestIDs)
    {
        $app = \Gazelle\App::go();

        if (empty($RequestIDs)) {
            return [];
        }

        if (is_array($RequestIDs)) {
            $RequestIDs = implode(',', $RequestIDs);
        }

        $QueryID = $app->dbOld->get_query_id();
        $app->dbOld->query("
        SELECT
          rt.RequestID,
          rt.TagID,
          t.Name
        FROM requests_tags AS rt
          JOIN tags AS t ON rt.TagID = t.ID
        WHERE rt.RequestID IN ($RequestIDs)
          ORDER BY rt.TagID ASC");

        $Tags = $app->dbOld->to_array(false, MYSQLI_NUM, false);
        $app->dbOld->set_query_id($QueryID);

        $Results = [];
        foreach ($Tags as $TagsRow) {
            list($RequestID, $TagID, $TagName) = $TagsRow;
            $Results[$RequestID][$TagID] = $TagName;
        }
        return $Results;
    }


    /**
     * get_votes_array
     */
    public static function get_votes_array($RequestID)
    {
        $app = \Gazelle\App::go();

        $RequestVotes = $app->cache->get("request_votes_$RequestID");
        if (!is_array($RequestVotes)) {
            $QueryID = $app->dbOld->get_query_id();
            $app->dbOld->query("
            SELECT
              rv.UserID,
              rv.Bounty,
              u.Username
            FROM requests_votes AS rv
              LEFT JOIN users_main AS u ON u.ID = rv.UserID
            WHERE rv.RequestID = $RequestID
              ORDER BY rv.Bounty DESC");

            if (!$app->dbOld->has_results()) {
                return array(
                    'TotalBounty' => 0,
                    'Voters' => []);
            }
            $Votes = $app->dbOld->to_array();

            $RequestVotes = [];
            $RequestVotes['TotalBounty'] = array_sum($app->dbOld->collect('Bounty'));

            foreach ($Votes as $Vote) {
                list($UserID, $Bounty, $Username) = $Vote;
                $VoteArray = [];
                $VotesArray[] = array('UserID' => $UserID, 'Username' => $Username, 'Bounty' => $Bounty);
            }

            $RequestVotes['Voters'] = $VotesArray;
            $app->cache->set("request_votes_$RequestID", $RequestVotes);
            $app->dbOld->set_query_id($QueryID);
        }
        return $RequestVotes;
    }
} # class

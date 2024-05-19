<?php

declare(strict_types=1);


/**
 * Gazelle\Requests
 */

namespace Gazelle;

class Requests extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?int $id = null; # primary key
    public string $type = "requests"; # database table
    public ?RecursiveCollection $attributes = null;
    public ?RecursiveCollection $relationships = null;

    # ["database" => "display"]
    protected array $maps = [
        "uuid" => "uuid",
        "ID" => "id",
        "UserID" => "userId",
        #"TimeAdded" => "createdAt",
        "LastVote" => "lastVote",
        "CategoryID" => "categoryId",
        "Title" => "title",
        "Title2" => "subject",
        "TitleJP" => "object",
        "Image" => "picture",
        "Description" => "description",
        "CatalogueNumber" => "identifier",
        "DLsiteID" => null,
        "FillerID" => "fillerId",
        "TorrentID" => "torrentId",
        "TimeFilled" => "filledAt",
        "Visible" => "isVisible",
        "GroupID" => "groupId",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];

    # cache settings
    private string $cachePrefix = "requests:";
    private string $cacheDuration = "1 hour";


    /**
     * read
     *
     * @param int|string $identifier
     * @return void
     */
    public function read(string|int $identifier = null): void
    {
        $app = App::go();

        parent::read($identifier);

        # get the voteCount
        $query = "select count(*) from requests_votes where requestId = ?";
        $this->attributes->voteCount = $app->dbNew->single($query, [$this->id]);

        # get the bounty
        $query = "select sum(bounty) from requests_votes where requestId = ?";
        $this->attributes->bounty = $app->dbNew->single($query, [$this->id]);
    }


    /**
     * relationships
     */
    public function relationships(): void
    {
        $app = App::go();

        $this->relationships = new RecursiveCollection([
            #"user" => $app->user->readProfile($this->attributes->userId),
            "creators" => $this->getCreators(),
            "tags" => $this->getTags(),
            "votes" => $this->getVotes(),
        ]);
    }


    /**
     * getCreators
     *
     * Gets the creators associated with a request.
     *
     * @return array
     */
    public function getCreators(): array
    {
        $app = App::go();

        $query = "
            select requests_artists.artistId, artists_group.name from requests_artists
            join artists_group on requests_artists.artistId = artists_group.artistId
            where requests_artists.requestId = ?
        ";
        $ref = $app->dbNew->multi($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = [
                "id" => $row["artistId"],
                "name" => $row["name"],
            ];
        }

        return $data;
    }


    /**
     * getTags
     *
     * Gets the tags associated with a request.
     *
     * @return array
     */
    public function getTags(): array
    {
        $app = App::go();

        $query = "
            select requests_tags.tagId, tags.name from requests_tags
            join tags on requests_tags.tagId = tags.id
            where requests_tags.requestId = ?
        ";
        $ref = $app->dbNew->multi($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = [
                "id" => $row["tagId"],
                "name" => $row["name"],
            ];
        }

        return $data;
    }


    /**
     * getVotes
     */
    public function getVotes()
    {
        $app = App::go();

        $query = "
            select requests_votes.userId, requests_votes.bounty, users.username from requests_votes
            join users on requests_votes.userId = users.id
            where requests_votes.requestId = ?
            order by requests_votes.bounty desc
        ";
        $ref = $app->dbNew->multi($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = [
                "userId" => $row["userId"],
                "username" => $row["username"],
                "bounty" => $row["bounty"],
            ];
        }

        return $data;
    }


    /**
     * createVote
     *
     * Adds a vote to a request.
     *
     * @param int $userId
     * @param int $bounty
     * @return void
     */
    public function createVote(int $userId, int $bounty): void
    {
        $app = App::go();

        # is it filled?
        if ($this->attributes->isFilled) {
            throw new Exception("request is already filled");
        }

        # can they afford it?
        $userData = $app->user->readProfile($userId);
        if ($userData["extra"]["Uploaded"] < $bounty) {
            throw new Exception("user does not have enough upload credit");
        }

        # insert the vote record
        $query = "insert ignore into requests_votes (requestId, userId, bounty) values (?, ?, ?)";
        $app->dbNew->do($query, [$this->id, $userId, $bounty]);
    }


    /** legacy */


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
    public static function get_requests($RequestIDs, $Return = true)
    {
        $app = App::go();

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
                      WHERE ID = " . $Request['TorrentID']);

                    list($Anonymous) = $app->dbOld->next_record();
                    if ($Anonymous) {
                        $Request['AnonymousFill'] = true;
                    }
                }

                unset($NotFound[$Request['ID']]);
                $Request['Tags'] = isset($Tags[$Request['ID']]) ? $Tags[$Request['ID']] : [];
                $Found[$Request['ID']] = $Request;
                $app->cache->set('request_' . $Request['ID'], $Request, 0);
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
        $app = App::go();

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
        $app = App::go();

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
        $app = App::go();

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

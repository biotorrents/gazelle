<?php

declare(strict_types=1);


/**
 * Gazelle\Requests
 */

namespace Gazelle;

class Requests extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "requests"; # resource name
    protected ?string $table = "requests"; # database table

    # cache settings
    private string $cachePrefix = "requests:";
    private string $cacheDuration = "1 hour";

    # request tax
    private float $requestTax = 0.2;

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "categoryId" => "categoryId",
        "userId" => "userId",
        "groupId" => "groupId",
        "torrentId" => "torrentId",
        "filledById" => "filledById",
        "filledAt" => "filledAt",
        "lastVote" => "lastVote",
        "identifier" => "identifier",
        "title" => "title",
        "slug" => "slug",
        "subject" => "subject",
        "object" => "object",
        "description" => "description",
        "picture" => "picture",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** crud */


    /**
     * read
     *
     * @param int|string $id
     * @return void
     */
    public function read(string|int $id = null): void
    {
        $app = App::go();

        # default read
        parent::read($id);

        # get the voteCount
        $query = "select count(*) from requests_votes where requestId = ?";
        $this->attributes->voteCount = $app->dbNew->single($query, [$this->id]);

        # get the bounty
        $query = "select sum(bounty) from requests_votes where requestId = ?";
        $this->attributes->bounty = $app->dbNew->single($query, [$this->id]);
    }


    /**
     * updateOrCreate
     *
     * @param array $data
     * @return void
     */
    public function updateOrCreate(array $data = []): void
    {
        $app = App::go();

        # required fields
        $data["userId"] ??= null;
        if (empty($data["userId"])) {
            throw new Exception("userId is required");
        }

        $data["categoryId"] ??= null;
        if (empty($data["categoryId"])) {
            throw new Exception("categoryId is required");
        }

        $data["title"] ??= null;
        if (empty($data["title"])) {
            throw new Exception("title is required");
        }

        $data["creators"] ??= [];
        if (empty($data["creators"])) {
            throw new Exception("creators is required");
        }

        $data["tags"] ??= [];
        if (empty($data["tags"])) {
            throw new Exception("tags is required");
        }

        $data["title"] ??= null;
        if (empty($data["title"])) {
            throw new Exception("title is required");
        }

        $data["description"] ??= null;
        if (empty($data["description"])) {
            throw new Exception("description is required");
        }

        # validate the picture
        $data["picture"] ??= null;
        if (!empty($data["picture"])) {
            $good = preg_match("/{$app->env->regexImage}/i", $data["picture"]);
            if (!$good) {
                throw new Exception("picture is invalid");
            }
        }

        # loop through the creators
        foreach ($data["creators"] as $creator) {
            # does the creator already exist?
            $query = "select id from artists_group where name = ?";
            $id = $app->dbNew->single($query, [$creator]);

            # if not, insert it
            if (!$id) {
                $query = "insert into artists_group (name) values (?)";
                $app->dbNew->do($query, [$creator]);
            }

            # get the artistIds currently associated with the request, if any
            $query = "
                select requests_artists.artistId from requests_artists
                join artists_group on requests_artists.artistId = artists_group.artistId
                where requests_artists.requestId = ?
            ";
            $artistIds = $app->dbNew->multi($query, [$this->id]);

            # compare the artistIds to the creators
            $artistIds = array_column($artistIds, "artistId");
            foreach ($artistIds as $artistId) {
            }
            if (!in_array($creator, $artistIds)) {
                # insert the creator
                $query = "insert into requests_artists (requestId, artistId) values (?, ?)";
                $app->dbNew->do($query, [$this->id, $creator]);
            }

            # delete the artistIds that are not in the creators
            $diff = array_diff($artistIds, $data["creators"]);
            foreach ($diff as $artistId) {
                $query = "delete from requests_artists where requestId = ? and artistId = ?";
                $app->dbNew->do($query, [$this->id, $artistId]);
            }
        }

        # loop through the tags
        foreach ($data["tags"] as $tag) {
            # does the tag already exist?
            $query = "select 1 from tags where name = ?";
            $exists = $app->dbNew->single($query, [$tag]);

            # if not, insert it
            if (!$exists) {
                $query = "insert into tags (name) values (?)";
                $app->dbNew->do($query, [$tag]);
            }

            # get the tagIds currently associated with the request, if any
            $query = "
                select tagId from requests_tags
                join tags on requests_tags.tagId = tags.id
                where requests_tags.requestId = ?
            ";
            $tagIds = $app->dbNew->multi($query, [$this->id]);

            # compare the tagIds to the tags
            $tagIds = array_column($tagIds, "tagId");
            if (!in_array($tag, $tagIds)) {
                # insert the tag
                $query = "insert into requests_tags (requestId, tagId) values (?, ?)";
                $app->dbNew->do($query, [$this->id, $tag]);
            }

            # delete the tagIds that are not in the tags
            $diff = array_diff($tagIds, $data["tags"]);
            foreach ($diff as $tagId) {
                $query = "delete from requests_tags where requestId = ? and tagId = ?";
                $app->dbNew->do($query, [$this->id, $tagId]);
            }
        }

        # unset the creators and tags
        unset($data["creators"], $data["tags"]);

        # default updateOrCreate
        parent::updateOrCreate($data);
    }


    /** relationships */


    /**
     * relationships
     *
     * @return ?array
     */
    public function relationships(): ?array
    {
        return [
            Creators::$type => $this->relatedCreators(),
            "tags" => $this->getTags(),
            "votes" => $this->getVotes(),
        ];
    }


    /**
     * relatedCreators
     */
    private function relatedCreators(): ?array
    {
        $app = App::go();

        $query = "select creatorId from creators_requests where requestId = ?";
        $ref = $app->dbNew->column($query, [$this->id]);

        if (!$ref) {
            return null;
        }

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Creators::$type];
        }

        return $data;
    }


    /** methods */


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


    /** */


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

        # calculate the bounty after tax
        $bountyAfterTax = $bounty * (1 - $this->requestTax);

        # insert the vote record
        $query = "insert ignore into requests_votes (requestId, userId, bounty) values (?, ?, ?)";
        $app->dbNew->do($query, [$this->id, $userId, $bountyAfterTax]);

        # update the request's last vote time
        $query = "update requests set lastVote = now() where id = ?";
        $app->dbNew->do($query, [$this->id]);

        # subtract the original bounty from the user
        $query = "update users_main set uploaded = uploaded - ? where id = ?";
        $app->dbNew->do($query, [$bounty, $userId]);
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
    public static function get_requests($requestIds, $returnUnused = true)
    {
        $app = App::go();

        $data = [];
        foreach ($requestIds as $requestId) {
            #$query = "select * from requests where id = ?";
            #$data[] = $app->dbNew->row($query, [$requestId]);
            $data[] = new self($requestId);
        }

        foreach ($data as $key => $value) {
            if (!$value->id) {
                unset($data[$key]);
            }
        }

        return $data;

        /** */

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
              created_at,
              LastVote,
              CategoryID,
              Title,
              Title2,
              TitleJP,
              Image,
              Description,
              CatalogueNumber,
              filledById,
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
                if ($Request['filledById']) {
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

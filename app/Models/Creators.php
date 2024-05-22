<?php

declare(strict_types=1);


/**
 * Gazelle\Creators
 */

namespace Gazelle;

class Creators extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public ?string $id = null; # primary key
    public static ?string $type = "creators"; # resource name
    protected ?string $table = "creators"; # database table

    # cache settings
    private string $cachePrefix = "creators:";
    private string $cacheDuration = "1 hour";

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "orcid" => "orcid",
        "semanticScholarId" => "semanticScholarId",
        "name" => "name",
        "slug" => "slug",
        "description" => "description",
        "aliases" => "aliases", # json
        "affiliations" => "affiliations", # json
        "homepage" => "homepage",
        "picture" => "picture",
        "hIndex" => "hIndex",
        "paperCount" => "paperCount",
        "citationCount" => "citationCount",
        "failCount" => "failCount",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** crud */


    /**
     * read
     */
    public function read(int|string $identifier = null): void
    {
        $app = App::go();

        # parent read
        parent::read($identifier);

        # decode the json fields
        $this->attributes->aliases = json_decode($this->attributes->aliases ?? "");
        $this->attributes->affiliations = json_decode($this->attributes->affiliations ?? "");
    }


    /** relationships */


    /**
     * relationships
     *
     * @return array
     */
    public function relationships(): array
    {
        return [
            TorrentGroups::$type => $this->relatedTorrentGroups(),
            Requests::$type => $this->relatedRequests(),
        ];
    }


    /**
     * relatedTorrentGroups
     *
     * @return array
     */
    public function relatedTorrentGroups(): array
    {
        $app = App::go();

        $query = "select groupId from creators_groups where creatorId = ? and deleted_at is null";
        $ref = $app->dbNew->column($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => TorrentGroups::$type];
        }

        return $data;
    }


    /**
     * relatedRequests
     *
     * @return array
     */
    public function relatedRequests(): array
    {
        $app = App::go();

        $query = "select requestId from creators_requests where creatorId = ? and deleted_at is null";
        $ref = $app->dbNew->column($query, [$this->id]);

        $data = [];
        foreach ($ref as $row) {
            $data[] = ["id" => $row, "type" => Requests::$type];
        }

        return $data;
    }


    /** methods */


    /**
     * getTorrentGroups
     *
     * Gets the torrent groups for a creator.
     *
     * @return array
     */
    public function getTorrentGroups(): array
    {
        $app = App::go();

        # get related id's
        $ref = $this->relatedTorrentGroups();

        $data = [];
        foreach ($ref as $row) {
            $data[] = new TorrentGroups($row["id"]);
        }

        return $data;
    }


    /**
     * getRequests
     *
     * Gets the requests for a creator.
     *
     * @return array
     */
    public function getRequests(): array
    {
        $app = App::go();

        # get related id's
        $ref = $this->relatedRequests();

        $data = [];
        foreach ($ref as $row) {
            $data[] = new Requests($row["id"]);
        }

        return $data;
    }


    /** */


    /**
     * hydrateFromSemanticScholar
     *
     * Searches for a creator in the Semantic Scholar database and hydrates the object.
     *
     * @return ?array what's written to the database
     */
    public function hydrateFromSemanticScholar(): ?array
    {
        $app = App::go();

        $semanticScholar = new SemanticScholar();
        $encodedName = urlencode($this->attributes->name);
        $response = $semanticScholar->search($encodedName, "authors");

        if (empty($response["data"])) {
            # increment the failCount and return
            $query = "update creators set failCount = failCount + 1 where id = ?";
            $app->dbNew->do($query, [$this->id]);

            return null;
        }

        # sort the array by hIndex descending
        $highestIndex = 0;
        foreach ($response["data"] as $key => $value) {
            if ($value["hIndex"] > $response["data"][$highestIndex]["hIndex"]) {
                $highestIndex = $key;
            }
        }

        # we have the canonical record
        $canonicalCreator = $response["data"][$highestIndex];

        # prepare the data for the database
        $data = [
            "id" => $this->id,
            "semanticScholarId" => $canonicalCreator["authorId"] ?? null,
            "name" => $canonicalCreator["name"] ?? null,
            "slug" => \Illuminate\Support\Str::slug($canonicalCreator["name"] ?? null),
            "aliases" => json_encode($canonicalCreator["aliases"] ?? null),
            "affiliations" => json_encode($canonicalCreator["affiliations"] ?? null),
            "homepage" => $canonicalCreator["homepage"] ?? null,
            "hIndex" => $canonicalCreator["hIndex"] ?? null,
            "paperCount" => $canonicalCreator["paperCount"] ?? null,
            "citationCount" => $canonicalCreator["citationCount"] ?? null,
        ];

        # now, save it and return
        $this->update($data);

        return $data;
    }


    /**
     * stats
     */
    /*
    public function stats(): array
    {
        $app = App::go();

        # start collecting data
        $data = [];

        # get the number of requests
        $query = "select count(*) from creators_requests where creatorId = ?";
        $data["requestCount"] = $app->dbNew->single($query, [$this->id]);

        # get the number of torrent groups
        $query = "select count(*) from creators_groups where creatorId = ?";
        $data["torrentGroupCount"] = $app->dbNew->single($query, [$this->id]);

        return $data;
    }
    */


    /** legacy Artists class */


    /**
     * getNameById
     *
     * Get the name of a creator by their id.
     * Optionally, return a link to their page.
     *
     * @param int $id
     * @param bool $html
     * @return string
     */
    public static function getNameById(int $id, ?bool $html = false): string
    {
        $app = \Gazelle\App::go();

        $query = "select name from creators where id = ?";
        $name = $app->dbNew->single($query, [$id]);

        if (!$name) {
            throw new Exception("not found");
        }

        if ($html) {
            return "<a href='/artist.php?id={$id}'>{$name}</a>";
        }

        return $name;
    }


    /**
     * get_artists
     *
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
     * get_artist
     *
     * Convenience function for get_artists, when you just need one group.
     *
     * @param int $GroupID
     * @return array - see get_artists
     */
    public static function get_artist($GroupID)
    {
        $Results = \Gazelle\Creators::get_artists(array($GroupID));
        return $Results[$GroupID];
    }


    /**
     * display_artists
     *
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
                    $link .= \Gazelle\Creators::display_artist($creators[2], $MakeLink, $Escape). ", ";
                    // no break

                case 3:
                    $link .= \Gazelle\Creators::display_artist($creators[2], $MakeLink, $Escape). ", ";
                    // no break

                case 2:
                    $link .= \Gazelle\Creators::display_artist($creators[1], $MakeLink, $Escape). ", ";
                    // no break

                case 1:
                    $link .= \Gazelle\Creators::display_artist($creators[0], $MakeLink, $Escape);
                    break;

                default:
                    $link = \Gazelle\Creators::display_artist($creators[0], $MakeLink, $Escape).' et al.';
            }

            return $link;
        } else {
            return '';
        }
    }


    /**
     * display_artist
     *
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
            error('Invalid parameters to \Gazelle\Creators::display_artist()');
        } elseif ($MakeLink) {
            return '<a href="/artist.php?id='.$creator['id'].'">'.\Gazelle\Text::esc($creator['name']).'</a>';
        } elseif ($Escape) {
            return \Gazelle\Text::esc($creator['name']);
        } else {
            return $creator['name'];
        }
    }


    /**
     * delete_artist
     *
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
        \Gazelle\Conversations::delete_page('artist', $creatorID);
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
     * normalise_artist_name
     *
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

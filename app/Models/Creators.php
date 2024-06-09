<?php

declare(strict_types=1);


/**
 * Gazelle\Creators
 */

namespace Gazelle;

class Creators extends ObjectCrud
{
    # https://jsonapi.org/format/1.2/#document-resource-objects
    public static ?string $type = "creators"; # resource name
    protected ?string $table = "creators"; # database table

    # cache settings
    protected ?string $cachePrefix = "creators:";

    # ["database" => "display"]
    protected array $maps = [
        "id" => "id",
        "userId" => "userId",
        "openAlexId" => "openAlexId",
        "orcid" => "orcid",
        "scopusId" => "scopusId",
        "semanticScholarId" => "semanticScholarId",
        "name" => "name",
        "slug" => "slug",
        "biography" => "biography",
        "aliases" => "aliases", # json
        "affiliations" => "affiliations", # json
        "homepage" => "homepage",
        "picture" => "picture",
        "wikipedia" => "wikipedia",
        "hIndex" => "hIndex",
        "paperCount" => "paperCount",
        "citationCount" => "citationCount",
        "summaryStats" => "summaryStats", # json
        "affiliationsOverTime" => "affiliationsOverTime", # json
        "topics" => "topics", # json
        "concepts" => "concepts", # json
        "countsByYear" => "countsByYear", # json
        "degreesOfSeparation" => "degreesOfSeparation",
        "failCount" => "failCount",
        "updatedById" => "updatedById",
        "created_at" => "createdAt",
        "updated_at" => "updatedAt",
        "deleted_at" => "deletedAt",
    ];


    /** methods */


    /**
     * hydrateFromOpenAlex
     *
     * Searches for a creator in the OpenAlex database and hydrates the object.
     *
     * @return array what's written to the database
     */
    public function hydrateFromOpenAlex(): array
    {
        $app = App::go();

        if (!$this->attributes->name) {
            return [];
        }

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            $openAlex = new OpenAlex();
            $response = $openAlex->match("authors", $this->attributes->name);

            if (empty($response)) {
                throw new Exception("no data");
            }

            $data = [
              "id" => $this->id,
              "userId" => $app->user->core["id"] ?? 0,
              "openAlexId" => $response["id"] ?? null,
              "orcid" => $response["orcid"] ?? null,
              "scopusId" => $response["ids"]["scopus"] ?? null,
              #"semanticScholarId" => $response["semanticScholarId"] ?? null,
              "name" => $response["display_name"] ?? null,
              "slug" => $app->dbNew->slug($response["display_name"] ?? null),
              #"biography" => $response["biography"] ?? null,
              "aliases" => json_encode($response["display_name_alternatives"] ?? []),
              "affiliations" => json_encode($response["last_known_institutions"] ?? []),
              #"homepage" => $response["homepage"] ?? null,
              #"picture" => $response["picture"] ?? null,
              #"wikipedia" => $response["wikipedia"] ?? null,
              "hIndex" => $response["summary_stats"]["h_index"] ?? null,
              "paperCount" => $response["works_count"] ?? null,
              "citationCount" => $response["cited_by_count"] ?? null,
              "summaryStats" => json_encode($response["summary_stats"] ?? []),
              "affiliationsOverTime" => json_encode($response["affiliations"] ?? []),
              "topics" => json_encode($response["topics"] ?? []),
              "concepts" => json_encode($response["x_concepts"] ?? []),
              "countsByYear" => json_encode($response["counts_by_year"] ?? []),
          ];

            # now, save it
            $this->update($data);

            # loop through the institutions
            $response["last_known_institutions"] ??= [];
            foreach ($response["last_known_institutions"] as $institution) {
                if (empty($institution)) {
                    continue;
                }

                $query = "select id from organizations where openAlexId = ?";
                $organizationId = $app->dbNew->single($query, [ $institution["id"] ]);

                if ($organizationId) {
                    $query = "insert ignore into creators_links (objectId, contentId, contentType, userId) values (?, ?, ?, ?)";
                    $app->dbNew->do($query, [$this->id, $organizationId, Organizations::$type, $app->user->core["id"] ?? 0]);

                    $query = "insert ignore into organizations_links (objectId, contentId, contentType, userId) values (?, ?, ?, ?)";
                    $app->dbNew->do($query, [$organizationId, $this->id, Creators::$type, $app->user->core["id"] ?? 0]);

                    continue;
                }

                $data = [
                    "id" => $app->dbNew->shortUuid(),
                    "userId" => $app->user->core["id"] ?? 0,
                    "openAlexId" => $institution["id"] ?? null,
                    "rorId" => $institution["ror"] ?? null,
                    "name" => $institution["display_name"] ?? null,
                    "country" => $institution["country_code"] ?? null,
                    "type" => $institution["education"] ?? null,
                    "degreesOfSeparation" => intval($this->attributes->degreesOfSeparation) + 1,
                ];

                $organization = new Organizations();
                $organization->updateOrCreate($data);
            }

            # commit and return
            $app->dbNew->commit();
            return $data;
        } catch (\Throwable $e) {
            $app->dbNew->rollBack();

            $query = "update {$this->table} set failCount = failCount + 1 where id = ?";
            $app->dbNew->do($query, [$this->id]);

            throw $e;
        }
    }


    /**
     * supplementFromSemanticScholar
     *
     * Searches for a creator in the Semantic Scholar database and hydrates the object.
     *
     * @return ?array what's written to the database
     */
    public function supplementFromSemanticScholar(): ?array
    {
        throw new Exception("not implemented");

        /** */

        $app = App::go();

        if (!$this->attributes->name) {
            return [];
        }

        try {
            # start a transaction
            $app->dbNew->beginTransaction();

            $semanticScholar = new SemanticScholar();
            $encodedName = urlencode($this->attributes->name);
            $response = $semanticScholar->search($encodedName, "authors");

            if (empty($response["data"])) {
                throw new Exception("no data");
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
                "userId" => $app->user->core["id"] ?? 0,
                "orcid" => $canonicalCreator["externalIds"]["ORCID"] ?? null,
                "semanticScholarId" => $canonicalCreator["authorId"] ?? null,
                "name" => $canonicalCreator["name"] ?? null,
                "slug" => \Illuminate\Support\Str::slug($canonicalCreator["name"] ?? null),
                "aliases" => json_encode($canonicalCreator["aliases"] ?? []),
                "affiliations" => json_encode($canonicalCreator["affiliations"] ?? []),
                "homepage" => $canonicalCreator["homepage"] ?? null,
                "hIndex" => $canonicalCreator["hIndex"] ?? null,
                "paperCount" => $canonicalCreator["paperCount"] ?? null,
                "citationCount" => $canonicalCreator["citationCount"] ?? null,
            ];

            # now, save it and return
            $this->update($data);
            return $data;
        } catch (\Throwable $e) {
            $app->dbNew->rollBack();

            $query = "update {$this->table} set failCount = failCount + 1 where id = ?";
            $app->dbNew->do($query, [$this->id]);

            throw $e;
        }
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
        $query = "select count(id) from creators_links where objectId = ? and contentType = ?";
        $data["requestCount"] = $app->dbNew->single($query, [$this->id, Requests::$type]);

        # get the number of torrent groups
        $query = "select count(id) from creators_links where objectId = ? and contentType = ?";
        $data["torrentGroupCount"] = $app->dbNew->single($query, [$this->id, TorrentGroups::$type]);

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
            return "<a href='/creators/{$id}'>{$name}</a>";
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
              ta.`contentId`,
              ta.`objectId`,
              ag.`name`
            FROM
              `creators_links` AS ta
            JOIN `creators` AS ag
            ON
              ta.`objectId` = ag.`id`
            WHERE
              ta.`contentId` IN($IDs)
            ORDER BY
              ta.`contentId` ASC,
              ag.`name` ASC;
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
        throw new Exception("not implemented");

        /** */

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

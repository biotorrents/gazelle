<?php

#declare(strict_types=1);


/**
 * TorrentSearch
 *
 * THIS IS GOING AWAY
 */

class TorrentSearch
{
    # constants
    public const anyTags = 0;
    public const allTags = 1;

    public const logicalAnd = " ";
    public const logicalOr = " | ";

    # map of sort mode => attribute name for ungrouped torrent page
    public static $sortOrders = [
        "cataloguenumber" => "cataloguenumber",
        "leechers" => "leechers",
        "random" => 1,
        "seeders" => "seeders",
        "size" => "size",
        "snatched" => "snatched",
        "time" => "id",
        "year" => "year",
    ];

    # map of sort mode => attribute name for grouped torrent page
    private static $sortOrdersGrouped = [
        "cataloguenumber" => "cataloguenumber",
        "leechers" => "sumleechers",
        "random" => 1,
        "seeders" => "sumseeders",
        "size" => "maxsize",
        "snatched" => "sumsnatched",
        "time" => "id",
        "year" => "year",
    ];

    # map of sort mode => aggregate expression required for some grouped sort orders
    private static $aggregateExpressions = [
        "leechers" => "sum(leechers) as sumleechers",
        "seeders" => "sum(seeders) as sumseeders",
        "size" => "max(size) as maxsize",
        "snatched" => "sum(snatched) as sumsnatched",
    ];

    # map of attribute name => global variable name with list of values that can be used for filtering
    private static $attributes = [
        "censored" => false,
        "filter_cat" => false,
        "freetorrent" => false,
        "releasetype" => "ReleaseTypes",
        "size_unit" => false,
        "year" => false,
    ];

    # list of fields that can be used for fulltext searches
    private static $fields = [
        "GroupTitle2" => 1, # organism
        "Groupnamejp" => 1, # strain
        "Version" => 1, # version
        "advgroupname" => 1,
        "archive" => 0, # todo
        "artistname" => 1, # author
        "cataloguenumber" => 1, # accession number
        "codec" => 1, # license
        "container" => 1, # format
        "description" => 1, # not group desc
        "filelist" => 1,
        "groupname" => 1, # title
        "location" => 1, # combined above
        "media" => 1, # platform
        "numbers" => 1, # combined above
        "resolution" => 1, # scope
        "search" => 1,
        "series" => 1, # location
        "studio" => 1, # department/lab
        "taglist" => 1
  ];

    # list of torrent-specific fields that can be used for filtering
    private static $torrentFields = [
        "censored" => 1,
        "description" => 1,
        "encoding" => 1,
        "filelist" => 1,
        "format" => 1,
        "media" => 1
  ];

    # some form field names don't match the ones in the index
    private static $formsToFields = [
        # todo: keep testing the granularity of filter combos
        "advgroupname" => "*", # todo: fix this ;)
        "location" => "(studio,series)",
        "numbers" => "(cataloguenumber,version)",
        "search" => "*",
        #"search" => "(groupname,GroupTitle2,groupnamejp,artistname,studio,series,cataloguenumber,yearfulltext)",
        #"advgroupname" => "(groupname,GroupTitle2,groupnamejp)",
  ];

    # specify the operator type to use for fields
    # empty key sets the default
    private static $fieldOperators = [
        "" => self::logicalAnd,
        "encoding" => self::logicalOr,
        "format" => self::logicalOr,
        "media" => self::logicalOr
    ];

    # specify the separator character to use for fields
    # empty key sets the default
    private static $fieldSeparators = [
        "" => " ",
        "encoding" => "|",
        "format" => "|",
        "media" => "|",
        "taglist" => ","
    ];

    // Primary SphinxqlQuery object used to get group IDs or torrent IDs for ungrouped searches
    private $SphQL;

    // Second SphinxqlQuery object used to get torrent IDs if torrent-specific fulltext filters are used
    private $SphQLTor;

    // Ordered result array or false if query resulted in an error
    private $SphResults;

    // Requested page
    private $page;

    // Number of results per page
    private $pageSize;

    // Number of results
    private $NumResults = 0;

    // Array with info from all matching torrent groups
    private $groups = [];

    // Whether any filters were used
    private $Filtered = false;

    // Whether the random sort order is selected
    private $Random = false;

    /*
     * Storage for fulltext search terms
     * ["Field name" => [
     *     "include" => [],
     *     "exclude" => [],
     *     "operator" => self::logicalAnd | self::logicalOr
     * ]], ...
     */
    private $terms = [];

    // Unprocessed search terms for retrieval
    private $RawTerms = [];

    // Storage for used torrent-specific attribute filters
    // ["Field name" => "Search expression", ...]
    private $UsedTorrentAttrs = [];

    // Storage for used torrent-specific fulltext fields
    // ["Field name" => "Search expression", ...]
    private $UsedTorrentFields = [];


    /**
     * __construct
     *
     * Initialize and configure a TorrentSearch object.
     *
     * @param bool $groupResults whether results should be grouped by groupId
     * @param string $orderBy attribute to use for sorting the results
     * @param string $orderWay whether to use ascending or descending order
     * @param int $page page number to display
     * @param int $pageSize number of results per page
     */
    public function __construct(bool $groupResults, string $orderBy, string $orderWay, int $page, int $pageSize)
    {
        $app = App::go();

        # bad arguments
        if ($groupResults && !isset(self::$sortOrdersGrouped[$orderBy])
            || !$groupResults && !isset(self::$sortOrders[$orderBy])
            || !in_array($orderWay, ["asc", "desc"])
        ) {
            throw new Exception("TorrentSearch constructor arguments:\n\n" . print_r(func_get_args(), true));
        }

        # force valid page
        if ($page < 1) {
            $page = 1;
        }

        # permissions check
        if (check_perms("site_search_many")) {
            $this->Page = $page;
        } else {
            $this->Page = min($page, SPHINX_MAX_MATCHES / $pageSize);
        }

        # $this pagination
        $resultLimit = $pageSize;
        $this->PageSize = $pageSize;
        $this->GroupResults = $groupResults;

        # $this SphinxqlQuery
        $this->SphQL = new SphinxqlQuery();
        $this->SphQL->where_match("_all", "fake", false);

        # random order
        if ($orderBy === "random") {
            $this->SphQL
                ->select("id, groupid")
                ->order_by("rand()", "");

            $this->Random = true;
            $this->Page = 1;

            # get more results because `order by rand()` can't be used in `group by` queries
            if ($groupResults) {
                $resultLimit *= 5;
            }
        }

        # group order
        elseif ($groupResults) {
            $select = "groupid";
            if (isset(self::$aggregateExpressions[$orderBy])) {
                $select .= ", " . self::$aggregateExpressions[$orderBy];
            }

            $this->SphQL
                ->select($select)
                ->group_by("groupid")
                ->order_group_by(self::$sortOrdersGrouped[$orderBy], $orderWay)
                ->order_by(self::$sortOrdersGrouped[$orderBy], $orderWay);
        }

        # default order
        else {
            $this->SphQL
                ->select("id, groupid")
                ->order_by(self::$sortOrders[$orderBy], $orderWay);
        }

        # pagination offset
        $offset = ($this->Page - 1) * $resultLimit;
        $minMax = $app->cacheOld->get_value("sphinx_min_max_matches");
        $maxMatches = max($offset + $resultLimit, $minMax ? $minMax : 1000);

        # query sphinx
        $this->SphQL
            ->from("torrents, delta")
            ->limit($offset, $resultLimit, $maxMatches);
    }


    /**
     * query
     *
     * Process search terms and run the main query
     *
     * @param array $terms array containing all search terms
     * @return array list of matching group id's with torrent is as the key for ungrouped results
     */
    public function query(array $terms = []): array
    {
        $this->process_search_terms($terms);

        $this->build_query();

        $this->run_query();

        $this->process_results();

        return $this->SphResults;
    }


    /**
     * insert_hidden_tags
     */
    public function insert_hidden_tags($tags)
    {
        $this->SphQL->where_match($tags, "taglist", false);
    }


    /**
     * run_query
     *
     * Internal function that runs the queries needed to get the desired results.
     */
    private function run_query()
    {
        $sphinxqlResult = $this->SphQL->query();

        # no results or error
        if ($sphinxqlResult->Errno > 0) {
            $this->SphResults = false;

            return false;
        }

        # random grouped handling
        if ($this->Random && $this->GroupResults) {
            $totalCount = $sphinxqlResult->get_meta("total_found");
            $this->SphResults = $sphinxqlResult->collect("groupid");

            $groupIds = array_keys($this->SphResults);
            $groupCount = count($groupIds ?? []);

            while ($sphinxqlResult->get_meta("total") < $totalCount && $groupCount < $this->PageSize) {
                # make sure we get $pageSize results, or all of them if there are less than $pageSize hits
                $this->SphQL->where("groupid", $groupIds, true);
                $sphinxqlResult = $this->SphQL->query();

                if (!$sphinxqlResult->has_results()) {
                    break;
                }

                $this->SphResults += $sphinxqlResult->collect("groupid");
                $groupIds = array_keys($this->SphResults);
                $groupCount = count($groupIds ?? []);
            }

            if ($groupCount > $this->PageSize) {
                $this->SphResults = array_slice($this->SphResults, 0, $this->PageSize, true);
            }

            $this->NumResults = count($this->SphResults ?? []);
        }

        # default handling
        else {
            $this->NumResults = (int)$sphinxqlResult->get_meta("total_found");
            if ($this->GroupResults) {
                $this->SphResults = $sphinxqlResult->collect("groupid");
            } else {
                $this->SphResults = $sphinxqlResult->to_pair("id", "groupid");
            }
        }
    }


    /**
     * build_query
     *
     * Process search terms and store the parts in appropriate arrays until we know if
     * the NOT operator can be used
     */
    private function build_query()
    {
        foreach ($this->terms as $field => $words) {
            $searchString = "";

            if (isset(self::$formsToFields[$field])) {
                $field = self::$formsToFields[$field];
            }

            $queryParts = ["include" => [], "exclude" => []];
            if (!empty($words["include"])) {
                foreach ($words["include"] as $word) {
                    $queryParts["include"][] = Sphinxql::sph_escape_string($word);
                }
            }

            if (!empty($words["exclude"])) {
                foreach ($words["exclude"] as $word) {
                    $queryParts["exclude"][] = "!" . Sphinxql::sph_escape_string(substr($word, 1));
                }
            }

            if (!empty($queryParts)) {
                if (isset($words["operator"])) {
                    // Is the operator already specified?
                    $operator = $words["operator"];
                } elseif (isset(self::$fieldOperators[$field])) {
                    // Does this field have a non-standard operator?
                    $operator = self::$fieldOperators[$field];
                } else {
                    // Go for the default operator
                    $operator = self::$fieldOperators[""];
                }

                if (!empty($queryParts["include"])) {
                    if ($field === "taglist") {
                        foreach ($queryParts["include"] as $key => $Tag) {
                            $queryParts["include"][$key] = "( ".$Tag." | ".$Tag.":* )";
                        }
                    }
                    $searchString .= "( " . implode($operator, $queryParts["include"]) . " ) ";
                }

                if (!empty($queryParts["exclude"])) {
                    $searchString .= implode(" ", $queryParts["exclude"]);
                }

                $this->SphQL->where_match($searchString, $field, false);
                if (isset(self::$torrentFields[$field])) {
                    $this->UsedTorrentFields[$field] = $searchString;
                }
                $this->Filtered = true;
            }
        }
    }


    /**
     * process_search_terms
     *
     * Look at each search term and figure out what to do with it
     *
     * @param array $terms Array with search terms from query()
     */
    private function process_search_terms($terms)
    {
        foreach ($terms as $key => $term) {
            if (isset(self::$fields[$key])) {
                $this->process_field($key, $term);
            } elseif (isset(self::$attributes[$key])) {
                $this->process_attribute($key, $term);
            }
            $this->RawTerms[$key] = $term;
        }
        $this->post_process();
    }


    /**
     * process_attribute
     *
     * Process attribute filters and store them in case we need to post-process grouped results
     *
     * @param string $attribute Name of the attribute to filter against
     * @param mixed $value The filter's condition for a match
     */
    private function process_attribute($attribute, $value)
    {
        if ($value === "") {
            return;
        }

        if ($attribute === "year") {
            $this->search_year($value);
        } elseif ($attribute === "size_unit") {
            // For the record, size_unit must appear in the GET parameters after size_min and size_max for this to work. Sorry.
            if (is_numeric($this->RawTerms["size_min"]) || is_numeric($this->RawTerms["size_max"])) {
                $this->SphQL->where_between("size", [intval(($this->RawTerms["size_min"] ?? 0)*(1024**$value)), intval(min(PHP_INT_MAX, ($this->RawTerms["size_max"] ?? INF)*(1024**$value)))]);
            }
        } elseif ($attribute === "freetorrent") {
            if ($value === 3) {
                $this->SphQL->where("freetorrent", 0, true);
                $this->UsedTorrentAttrs["freetorrent"] = 3;
            } elseif ($value >= 0 && $value < 3) {
                $this->SphQL->where("freetorrent", $value);
                $this->UsedTorrentAttrs[$attribute] = $value;
            }
        } elseif ($attribute === "filter_cat") {
            if (!is_array($value)) {
                $value = array_fill_keys(explode("|", $value), 1);
            }
            $categoryFilter = [];
            foreach (array_keys($value) as $category) {
                if (is_numeric($category)) {
                    $categoryFilter[] = $category;
                } else {
                    global $Categories;
                    $validValues = array_map("strtolower", $Categories);
                    if (($categoryID = array_search(strtolower($category), $validValues)) !== false) {
                        $categoryFilter[] = $categoryID + 1;
                    }
                }
            }
            $this->SphQL->where("categoryid", ($categoryFilter ?? 0));
        } else {
            if (!is_numeric($value) && self::$attributes[$attribute] !== false) {
                // Check if the submitted value can be converted to a valid one
                $validValuesVarname = self::$attributes[$attribute];
                global $$validValuesVarname;
                $validValues = array_map("strtolower", $$validValuesVarname);
                if (($value = array_search(strtolower($value), $validValues)) === false) {
                    // Force the query to return 0 results if value is still invalid
                    $value = max(array_keys($validValues)) + 1;
                }
            }
            $this->SphQL->where($attribute, $value);
            $this->UsedTorrentAttrs[$attribute] = $value;
        }

        $this->Filtered = true;
    }


    /**
     * process_field
     *
     * Look at a fulltext search term and figure out if it needs special treatment
     *
     * @param string $field Name of the search field
     * @param string $term Search expression for the field
     */
    private function process_field($field, $term)
    {
        $term = trim($term);
        if ($term === "") {
            return;
        }

        if ($field === "filelist") {
            $this->search_filelist($term);
        } elseif ($field === "taglist") {
            $this->search_taglist($term);
        } else {
            $this->add_field($field, $term);
        }
    }


    /**
     * post_process
     *
     * Some fields may require post-processing
     */
    private function post_process()
    {
        if (isset($this->terms["taglist"])) {
            // Replace bad tags with tag aliases
            $this->terms["taglist"] = Tags::remove_aliases($this->terms["taglist"]);
            if (isset($this->RawTerms["tags_type"]) && (int)$this->RawTerms["tags_type"] === self::anyTags) {
                $this->terms["taglist"]["operator"] = self::logicalOr;
            }

            // Update the RawTerms array so get_terms() can return the corrected search terms
            if (isset($this->terms["taglist"]["include"])) {
                $AllTags = $this->terms["taglist"]["include"];
            } else {
                $AllTags = [];
            }

            if (isset($this->terms["taglist"]["exclude"])) {
                $AllTags = array_merge($AllTags, $this->terms["taglist"]["exclude"]);
            }
            $this->RawTerms["taglist"] = str_replace("_", ".", implode(", ", $AllTags));
        }
    }


    /**
     * search_filelist
     *
     * Use phrase boundary for file searches to make sure we don't count
     * partial hits from multiple files
     *
     * @param string $term Given search expression
     */
    private function search_filelist($term)
    {
        $searchString = '"' . Sphinxql::sph_escape_string($term) . '"~20';
        $this->SphQL->where_match($searchString, "filelist", false);
        $this->UsedTorrentFields["filelist"] = $searchString;
        $this->Filtered = true;
    }


    /**
     * search_taglist
     *
     * Prepare tag searches before sending them to the normal treatment
     *
     * @param string $term Given search expression
     */
    private function search_taglist($term)
    {
        $term = strtr($term, ".", "_");
        $this->add_field("taglist", $term);
    }


    /**
     * search_year
     *
     * The year filter accepts a range. Figure out how to handle the filter value
     *
     * @param string $term Filter condition. Can be an integer or a range with the format X-Y
     * @return bool True if parameters are valid
     */
    private function search_year($term)
    {
        $Years = explode("-", $term);
        if (count($Years ?? []) === 1 && is_numeric($Years[0])) {
            // Exact year
            $this->SphQL->where("year", $Years[0]);
        } elseif (count($Years ?? []) === 2) {
            if (empty($Years[0]) && is_numeric($Years[1])) {
                // Range: 0 - 2005
                $this->SphQL->where_lt("year", $Years[1], true);
            } elseif (empty($Years[1]) && is_numeric($Years[0])) {
                // Range: 2005 - 2^32-1
                $this->SphQL->where_gt("year", $Years[0], true);
            } elseif (is_numeric($Years[0]) && is_numeric($Years[1])) {
                // Range: 2005 - 2009
                $this->SphQL->where_between("year", [min($Years), max($Years)]);
            } else {
                // Invalid input
                return false;
            }
        } else {
            // Invalid input
            return false;
        }
        return true;
    }


    /**
     * add_field
     *
     * Add a field filter that doesn't need special treatment
     *
     * @param string $field Name of the search field
     * @param string $term Search expression for the field
     */
    private function add_field($field, $term)
    {
        if (isset(self::$fieldSeparators[$field])) {
            $Separator = self::$fieldSeparators[$field];
        } else {
            $Separator = self::$fieldSeparators[""];
        }

        $words = explode($Separator, $term);
        foreach ($words as $word) {
            $this->add_word($field, $word);
        }
    }


    /**
     * add_word
     *
     * Add a keyword to the array of search terms
     *
     * @param string $field Name of the search field
     * @param string $word Keyword
     */
    private function add_word($field, $word)
    {
        $word = trim($word);
        // Skip isolated hyphens to enable "Artist - Title" searches
        if ($word === "" || $word === "-") {
            return;
        }

        if ($word[0] === "!" && strlen($word) >= 2 && strpos($word, "!", 1) === false) {
            $this->terms[$field]["exclude"][] = $word;
        } else {
            $this->terms[$field]["include"][] = $word;
        }
    }


    /**
     * get_groups
     *
     * @return array Torrent group information for the matches from Torrents::get_groups
     */
    public function get_groups()
    {
        return $this->Groups;
    }


    /**
     * get_terms
     *
     * @param string $Type Field or attribute name
     * @return string Unprocessed search terms
     */
    public function get_terms($Type)
    {
        return $this->RawTerms[$Type] ?? "";
    }


    /**
     * record_count
     *
     * @return int Result count
     */
    public function record_count()
    {
        return $this->NumResults;
    }


    /**
     * has_filters
     *
     * @return bool Whether any filters were used
     */
    public function has_filters()
    {
        return $this->Filtered;
    }


    /**
     * need_torrent_ft
     *
     * @return bool Whether any torrent-specific fulltext filters were used
     */
    public function need_torrent_ft()
    {
        return $this->GroupResults && $this->NumResults > 0 && !empty($this->UsedTorrentFields);
    }


    /**
     * process_results
     *
     * Get torrent group info and remove any torrents that don't match
     */
    private function process_results()
    {
        !d($this->SphResults);

        if (count($this->SphResults ?? []) === 0) {
            return;
        }

        $this->Groups = Torrents::get_groups($this->SphResults);
        if ($this->need_torrent_ft()) {
            // Query Sphinx for torrent IDs if torrent-specific fulltext filters were used
            $this->filter_torrents_sph();
        } elseif ($this->GroupResults) {
            // Otherwise, let PHP discard unmatching torrents
            $this->filter_torrents_internal();
        }
        // Ungrouped searches don't need any additional filtering
    }


    /**
     * filter_torrents_sph
     *
     * Build and run a query that gets torrent IDs from Sphinx when fulltext filters
     * were used to get primary results and they are grouped
     */
    private function filter_torrents_sph()
    {
        $allTorrents = [];
        foreach ($this->Groups as $groupId => $group) {
            if (!empty($group["Torrents"])) {
                $allTorrents += array_fill_keys(array_keys($group["Torrents"]), $groupId);
            }
        }

        $torrentCount = count($allTorrents ?? []);
        $this->SphQLTor = new SphinxqlQuery();
        $this->SphQLTor->where_match("_all", "fake", false);
        $this->SphQLTor->select("id")->from("torrents, delta");

        foreach ($this->UsedTorrentFields as $field => $term) {
            $this->SphQLTor->where_match($term, $field, false);
        }

        $this->SphQLTor->copy_attributes_from($this->SphQL);
        $this->SphQLTor->where("id", array_keys($allTorrents))->limit(0, $torrentCount, $torrentCount);
        $sphinxqlResultTor = $this->SphQLTor->query();
        $matchingTorrentIds = $sphinxqlResultTor->to_pair("id", "id");

        foreach ($allTorrents as $torrentId => $groupId) {
            if (!isset($matchingTorrentIds[$torrentId])) {
                unset($this->Groups[$groupId]["Torrents"][$torrentId]);
            }
        }
    }


    /**
     * filter_torrents_internal
     *
     * Non-Sphinx method of collecting IDs of torrents that match any
     * torrent-specific attribute filters that were used in the search query
     */
    private function filter_torrents_internal()
    {
        foreach ($this->Groups as $groupId => $group) {
            if (empty($group["Torrents"])) {
                continue;
            }

            foreach ($group["Torrents"] as $torrentId => $torrent) {
                if (!$this->filter_torrent_internal($torrent)) {
                    unset($this->Groups[$groupId]["Torrents"][$torrentId]);
                }
            }
        }
    }


    /**
     * filter_torrent_internal
     *
     * Post-processing to determine if a torrent is a real hit,
     * or if it was returned because another torrent in the group matched.
     * Only used if there are no torrent-specific fulltext conditions.
     *
     * @param array $torrent torrent array, probably from Torrents::get_groups()
     * @return bool true if it's a real hit
     */
    private function filter_torrent_internal(array $torrent): bool
    {
        if (isset($this->UsedTorrentAttrs["freetorrent"])) {
            $filterValue = $this->UsedTorrentAttrs["freetorrent"];
            if ($filterValue === "3" && $torrent["FreeTorrent"] === "0") {
                // Either FL or NL is ok
                return false;
            } elseif ($filterValue !== "3" && $filterValue !== (int)$torrent["FreeTorrent"]) {
                return false;
            }
        }
        return true;
    }
}

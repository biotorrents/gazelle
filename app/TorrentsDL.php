<?php
#declare(strict_types=1);

/**
 * Class for functions related to the features involving torrent downloads
 */
class TorrentsDL
{
    const ChunkSize = 100;
    const MaxPathLength = 200;
    private $QueryResult;
    private $QueryRowNum = 0;
    private $Zip;
    private $IDBoundaries;
    private $FailedFiles = [];
    private $NumAdded = 0;
    private $NumFound = 0;
    private $Size = 0;
    private $Title;
    private $User;
    private $AnnounceURL;
    private $AnnounceList;

    /**
     * Create a Zip object and store the query results
     *
     * @param mysqli_result $QueryResult results from a query on the collector pages
     * @param string $Title name of the collection that will be created
     * @param string $AnnounceURL URL to add to the created torrents
     */
    public function __construct(&$QueryResult, $Title)
    {
        G::$cache->InternalCache = false; // The internal cache is almost completely useless for this
        App::unlimit(); // Need more memory and longer timeout
        $this->QueryResult = $QueryResult;
        $this->Title = $Title;
        $this->User = G::$user;
        $this->AnnounceURL = ANNOUNCE_URLS[0][0]."/".G::$user['torrent_pass']."/announce";

        function add_passkey($Ann)
        {
            return (is_array($Ann)) ? array_map('add_passkey', $Ann) : $Ann."/".G::$user['torrent_pass']."/announce";
        }

        # todo: Probably not working, but no need yet
        $this->AnnounceList = (sizeof(ANNOUNCE_URLS[0]) === 1 && sizeof(ANNOUNCE_URLS[0][0]) === 1) ? [] : array_map('add_passkey', ANNOUNCE_URLS[0]);

        # Tracker tiers (pending)
        #$this->AnnounceList = (sizeof(ANNOUNCE_URLS) === 1 && sizeof(ANNOUNCE_URLS[0]) === 1) ? [] : array(array_map('add_passkey', ANNOUNCE_URLS[0]), ANNOUNCE_URLS[1]);
        
        # Original Oppaitime
        #$this->AnnounceList = (sizeof(ANNOUNCE_URLS) == 1 && sizeof(ANNOUNCE_URLS[0]) == 1) ? [] : array_map('add_passkey', ANNOUNCE_URLS);

        $this->Zip = new Zip(Misc::file_string($Title));
    }

    /**
     * Store the results from a DB query in smaller chunks to save memory
     *
     * @param string $Key the key to use in the result hash map
     * @return array with results and torrent group IDs or false if there are no results left
     */
    public function get_downloads($Key)
    {
        $GroupIDs = $Downloads = [];
        $OldQuery = G::$db->get_query_id();
        G::$db->set_query_id($this->QueryResult);

        if (!isset($this->IDBoundaries)) {
            if ($Key === 'TorrentID') {
                $this->IDBoundaries = false;
            } else {
                $this->IDBoundaries = G::$db->to_pair($Key, 'TorrentID', false);
            }
        }

        $Found = 0;
        while ($Download = G::$db->next_record(MYSQLI_ASSOC, false)) {
            if (!$this->IDBoundaries || $Download['TorrentID'] === $this->IDBoundaries[$Download[$Key]]) {
                $Found++;
                $Downloads[$Download[$Key]] = $Download;
                $GroupIDs[$Download['TorrentID']] = $Download['GroupID'];

                if ($Found >= self::ChunkSize) {
                    break;
                }
            }
        }

        $this->NumFound += $Found;
        G::$db->set_query_id($OldQuery);

        if (empty($Downloads)) {
            return false;
        }

        return array($Downloads, $GroupIDs);
    }

    /**
     * Add a file to the zip archive
     *
     * @param string $TorrentData bencoded torrent without announce url (new format) or TORRENT object (old format)
     * @param array $Info file info stored as an array with at least the keys
     *  Artist, Name, Year, Media, Format, Encoding and TorrentID
     * @param string $FolderName folder name
     */
    public function add_file(&$TorrentData, $Info, $FolderName = '')
    {
        $FolderName = Misc::file_string($FolderName);
        $MaxPathLength = $FolderName ? (self::MaxPathLength - strlen($FolderName) - 1) : self::MaxPathLength;
        $FileName = self::construct_file_name($Info['TorrentID'], $MaxPathLength);

        $this->Size += $Info['Size'];
        $this->NumAdded++;
        $this->Zip->add_file(self::get_file($TorrentData, $this->AnnounceURL, $this->AnnounceList), ($FolderName ? "$FolderName/" : "") . $FileName);

        usleep(25000); // We don't want to send much faster than the client can receive
        return;
    }

    /**
     * Add a file to the list of files that could not be downloaded
     *
     * @param array $Info file info stored as an array with at least the keys Artist, Name and Year
     */
    public function fail_file($Info)
    {
        $this->FailedFiles[] = $Info['Artist'] . ' - ' . $Info['Name'] . ' - ' . $Info['Year'];
    }

    /**
     * Add a file to the list of files that did not match the user's format or quality requirements
     *
     * @param array $Info file info stored as an array with at least the keys Artist, Name and Year
     */
    public function skip_file($Info)
    {
        $this->SkippedFiles[] = $Info['Artist'] . ' - ' . $Info['Name'] . ' - ' . $Info['Year'];
    }

    /**
     * Add a summary to the archive and include a list of files that could not be added. Close the zip archive
     *
     * @param bool $FilterStats whether to include filter stats in the report
     */
    public function finalize($FilterStats = true)
    {
        $this->Zip->add_file($this->summary($FilterStats), "Summary.txt");
        if (!empty($this->FailedFiles)) {
            $this->Zip->add_file($this->errors(), "Errors.txt");
        }
        $this->Zip->close_stream();
    }

    /**
     * Produce a summary text over the collector results
     *
     * @param bool $FilterStats whether to include filter stats in the report
     * @return summary text
     */
    public function summary($FilterStats)
    {
        $ENV = ENV::go;
        
        $Used = Format::get_size(memory_get_usage(true));
        $Date = date("M d Y, H:i");
        $NumSkipped = count($this->SkippedFiles);

        # wtf
        return "Collector Download Summary for $this->Title - $ENV->SITE_NAME\r\n"
      . "\r\n"
      . "User:    {$this->User[Username]}\r\n"
      . "Passkey: {$this->User[torrent_pass]}\r\n"
      . "\r\n"
      . "Used:    $Used\r\n"
      . "Date:    $Date\r\n"
      . "\r\n"

      . ($FilterStats !== false
        ? "Torrent groups analyzed: $this->NumFound\r\n"
          . "Torrent groups filtered: $NumSkipped\r\n"
        : "")

      . "Torrents downloaded:   $this->NumAdded\r\n"
      . "\r\n"
      . "Total size of torrents (ratio hit): ".Format::get_size($this->Size)."\r\n"

      . ($NumSkipped
        ? "\r\n"
          . "Albums unavailable within your criteria (consider making a request for your desired format):\r\n"
          . implode("\r\n", $this->SkippedFiles) . "\r\n"
        : "");
    }

    /**
     * Compile a list of files that could not be added to the archive
     *
     * @return list of files
     */
    public function errors()
    {
        return "A server error occurred. Please try again at a later time.\r\n"
      . "\r\n"
      . "The following torrents could not be downloaded:\r\n"
      . implode("\r\n", $this->FailedFiles) . "\r\n";
    }

    /**
     * Combine a bunch of torrent info into a standardized file name
     *
     * @params most input variables are self-explanatory
     * @param int $TorrentID if given, append "-TorrentID" to torrent name
     * @param int $MaxLength maximum file name length
     * @return file name with at most $MaxLength characters
     */
    public static function construct_file_name($TorrentID = false, $MaxLength = self::MaxPathLength)
    {
        $MaxLength -= 8; // ".torrent"
        if ($TorrentID !== false) {
            $MaxLength -= (strlen($TorrentID) + 1);
        }
        return "$TorrentID.torrent";

        /*
        $TorrentArtist = Misc::file_string($Artist);
        $TorrentName = Misc::file_string($Album);
        */
    }

    /**
     * Convert a stored torrent into a binary file that can be loaded in a torrent client
     *
     * @param mixed $TorrentData bencoded torrent without announce URL (new format) or TORRENT object (old format)
     * @return bencoded string
     */
    public static function get_file(&$TorrentData, $AnnounceURL, $AnnounceList = [])
    {
        if (Misc::is_new_torrent($TorrentData)) {
            $Bencode = BencodeTorrent::add_announce_url($TorrentData, $AnnounceURL);
            if (!empty($AnnounceList)) {
                $Bencode = BencodeTorrent::add_announce_list($Bencode, $AnnounceList);
            }
            return $Bencode;
        }

        $Tor = new TORRENT(unserialize(base64_decode($TorrentData)), true);
        $Tor->set_announce_url($AnnounceURL);

        unset($Tor->Val['announce-list']);
        if (!empty($AnnounceList)) {
            $Tor->set_announce_list($AnnounceList);
        }
        
        unset($Tor->Val['url-list']);
        unset($Tor->Val['libtorrent_resume']);
        return $Tor->enc();
    }
}

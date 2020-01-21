<?php

class TorrentsDL
{
    # ...

    /**
     * Create a Zip object and store the query results
     *
     * @param mysqli_result $QueryResult results from a query on the collector pages
     * @param string $Title name of the collection that will be created
     * @param string $AnnounceURL URL to add to the created torrents
     */
    public function __construct(&$QueryResult, $Title)
    {
        G::$Cache->InternalCache = false; // The internal cache is almost completely useless for this
        Zip::unlimit(); // Need more memory and longer timeout
        $this->QueryResult = $QueryResult;
        $this->Title = $Title;
        $this->User = G::$LoggedUser;
        $this->AnnounceURL = ANNOUNCE_URLS[0][0].'/'.G::$LoggedUser['torrent_pass'].'/announce';

        function add_passkey($Ann)
        {
            return (is_array($Ann)) ? array_map('add_passkey', $Ann) : $Ann.'/'.G::$LoggedUser['torrent_pass'].'/announce';
        }

        $this->AnnounceList = array(array_map('add_passkey', ANNOUNCE_URLS[0]), ANNOUNCE_URLS[1]);
        #$this->AnnounceList = (sizeof(ANNOUNCE_URLS) === 1 && sizeof(ANNOUNCE_URLS[0]) === 1) ? [] : array_map('add_passkey', ANNOUNCE_URLS);
        $this->Zip = new Zip(Misc::file_string($Title));
    }

    # ...
}

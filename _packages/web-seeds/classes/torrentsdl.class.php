<?php

class TorrentsDL
{
    # Line 21
    private $AnnounceList;
    private $WebSeeds;
    # Line 22

    # Line 31
    public function __construct(&$QueryResult, $Title)
    {
        G::$Cache->InternalCache = false; // The internal cache is almost completely useless for this
        Zip::unlimit(); // Need more memory and longer timeout
        $this->QueryResult = $QueryResult;
        $this->Title = $Title;
        $this->User = G::$LoggedUser;
        $this->AnnounceURL = ANNOUNCE_URLS[0][0].'/'.G::$LoggedUser['torrent_pass'].'/announce';
        $this->WebSeeds = $WebSeeds;
        # Line 39
    }
}

# cont.
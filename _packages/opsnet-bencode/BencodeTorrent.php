<?php

namespace OrpheusNET\BencodeTorrent;

/**
 * BEncode service that allows us to encode PHP objects into BEncode and decode
 * BEncode into PHP objects for torrents. BEncode supports the following PHP objects:
 *      - Associated Array
 *      - Lists
 *      - Strings
 *      - Integers
 * with any other type throwing an exception. A list is defined for our purposes
 * as an array with only numeric keys in perfect order, otherwise we assume it's
 * an associated array and will encode as a dictionary.
 *
 * Additionally, as this is for torrent files, we can make the following assumptions
 * and requirements:
 *  1. Top level data structure must be a dictionary
 *  2. Dictionary must contain an info key
 * If any of these are violated, then we raise an exception for this particular file.
 *
 * @see https://wiki.theory.org/index.php/BitTorrentSpecification
 *
 * For Gazelle, this also acts as a unification of the two original BEncode implementations
 * which were both used in separate areas of the codebase.
 */
class BencodeTorrent extends Bencode {
    const FILELIST_DELIM = 0xF7;
    private static $utf8_filelist_delim = null;

    public function __construct() {
        $this->setDelim();
    }

    /**
     * Internal function that sets up the filelist_delim character for use. We cannot use encode
     * and char to set a class constant or variable, so we wait till the class is initialized
     * for the first time to set it.
     */
    private function setDelim() {
        if (self::$utf8_filelist_delim === null) {
            self::$utf8_filelist_delim = utf8_encode(chr(self::FILELIST_DELIM));
        }
    }

    /**
     * Sets the internal data array
     * @param array $data
     * @throws \RuntimeException
     */
    public function setData($data) {
        parent::setData($data);
        $this->validate();
    }

    /**
     * Given a BEncoded string and decode it
     * @param string $data
     * @throws \RuntimeException
     */
    public function decodeString(string $data) {
        parent::decodeString($data);
        $this->validate();
    }

    /**
     * Given a path to a file, decode the contents of it
     *
     * @param string $path
     * @throws \RuntimeException
     */
    public function decodeFile(string $path) {
        parent::decodeFile($path);
        $this->validate();
    }

    /**
     * Validates that the internal data array
     * @throws \RuntimeException
     */
    public function validate() {
        if (!is_array($this->data)) {
            throw new \TypeError('Data must be an array');
        }
        if (empty($this->data['info'])) {
            throw new \RuntimeException("Torrent dictionary doesn't have info key");
        }
        if (isset($this->data['info']['files'])) {
            foreach ($this->data['info']['files'] as $file) {
                $path_key = isset($file['path.utf-8']) ? 'path.utf-8' : 'path';
                if (isset($file[$path_key])) {
                    $filter = array_filter(
                        $file[$path_key],
                        function ($element) {
                            return strlen($element) === 0;
                        }
                    );
                    if (count($filter) > 0) {
                        throw new \RuntimeException('Cannot have empty path for a file');
                    }
                }
            }
        }
    }

    /**
     * Utility function to clean out keys in the data and info dictionaries that we don't need in
     * our torrent file when we go to store it in the DB or serve it up to the user (with the
     * expectation that we'll be calling at least setAnnounceUrl(...) when a user asks for a valid
     * torrent file).
     *
     * @return bool flag to indicate if we altered the info dictionary
     */
    public function clean() : bool {
        $this->cleanDataDictionary();
        return $this->cleanInfoDictionary();
    }

    /**
     * Clean out keys within the data dictionary that are not strictly necessary or will be
     * overwritten dynamically on any downloaded torrent (like announce or comment), so that we
     * store the smallest encoded string within the database and cuts down on potential waste.
     */
    public function cleanDataDictionary() {
        $allowed_keys = array('encoding', 'info');
        foreach ($this->data as $key => $value) {
            if (!in_array($key, $allowed_keys)) {
                unset($this->data[$key]);
            }
        }
    }

    /**
     * Cleans out keys within the info dictionary (and would affect the generated info_hash)
     * that are not standard or expected. We do allow some keys that are not strictly necessary
     * (primarily the two below), but that's because it's better to just have the extra bits in
     * the dictionary than having to force a user to re-download the torrent file for something
     * that they might have no idea their client is doing nor how to stop it. Returns TRUE if
     * we had to change something in the info dictionary that would affect the info_hash (thus
     * requiring a re-download), else return FALSE.
     *
     * x_cross_seed is added by PyroCor (@see https://github.com/pyroscope/pyrocore)
     * unique is added by xseed (@see https://whatbox.ca/wiki/xseed)
     *
     * @return bool
     */
    public function cleanInfoDictionary() : bool {
        $cleaned = false;
        $allowed_keys = array('files', 'name', 'piece length', 'pieces', 'private', 'length',
                              'name.utf8', 'name.utf-8', 'md5sum', 'sha1', 'source',
                              'file-duration', 'file-media', 'profiles', 'x_cross_seed', 'unique');
        foreach ($this->data['info'] as $key => $value) {
            if (!in_array($key, $allowed_keys)) {
                unset($this->data['info'][$key]);
                $cleaned = true;
            }
        }

        return $cleaned;
    }

    /**
     * Returns a bool on whether the private flag set to 1 within the info dictionary.
     *
     * @return bool
     */
    public function isPrivate() : bool {
        $this->hasData();
        return isset($this->data['info']['private']) && $this->data['info']['private'] === 1;
    }

    /**
     * Sets the private flag (if not already set) in the info dictionary. Setting this to 1 makes
     * it so a client will only publish its presence in the swarm via the tracker in the announce
     * URL, else it'll be discoverable via other means such as PEX peer exchange or dht, which is
     * a negative for security and privacy of a private swarm. Returns a bool on whether or not
     * the flag was changed so that an appropriate screen can be shown to the user.
     *
     * @return bool
     */
    public function makePrivate() : bool {
        $this->hasData();
        if ($this->isPrivate()) {
            return false;
        }
        $this->data['info']['private'] = 1;
        ksort($this->data['info']);
        return true;
    }

    /**
     * Set the source flag in the info dictionary equal to $source. This can be used to ensure a
     * unique info hash across sites so long as all sites use the source flag. This isn't an
     * 'official' flag (no accepted BEP on it), but it has become the defacto standard with more
     * clients supporting it natively. Returns a boolean on whether or not the source was changed
     * so that an appropriate screen can be shown to the user.
     *
     * @param string $source
     *
     * @return bool true if the source was set/changed, false if no change
     */
    public function setSource(string $source) : bool {
        $this->hasData();
        if (isset($this->data['info']['source']) && $this->data['info']['source'] === $source) {
            return false;
        }
        // Since we've set the source and will require a re-download, we might as well clean
        // these out as well
        unset($this->data['info']['x_cross_seed']);
        unset($this->data['info']['unique']);
        $this->setValue(['info.source' => $source]);
        return true;
    }

    /**
     * Function to allow you set any number of keys and values in the data dictionary. You can
     * set the value in a dictionary by concatenating the keys into a string with a period
     * separator (ex: info.name will set name field in the info dictionary) so that the rest
     * of the dictionary is unaffected.
     *
     * @param array $array
     */
    public function setValue(array $array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                ksort($value);
            }
            $keys = explode('.', $key);
            $data = &$this->data;
            for ($i = 0; $i < count($keys); $i++) {
                $data = &$data[$keys[$i]];
            }
            $data = $value;
            $data = &$this->data;
            for ($i = 0; $i < count($keys); $i++) {
                $data = &$data[$keys[$i]];
                if (is_array($data)) {
                    ksort($data);
                }
            }
        }
        ksort($this->data);
        $this->validate();
    }

    /**
     * Get a sha1 encoding of the BEncoded info dictionary. The SHA1 encoding allows us to transmit
     * the info dictionary over the wire (such as within URLs or in submitted forms). Gazelle
     * primarily relies on this so that it can ensure that all torrents uploaded have unique
     * info hashes and so a user could search for a torrent based on its info hash. The
     * BitTorrent protocol uses this when announcing/scraping a torrent so that the tracker can
     * identify the torrent the client is talking about.
     *
     * @return string
     */
    public function getInfoHash() : string {
        $this->hasData();
        return sha1($this->encodeVal($this->data['info']));
    }

    public function getHexInfoHash(): string {
        return pack('H*', $this->getInfoHash());
    }

    /**
     * @return string
     */
    public function getName() {
        if (isset($this->data['info']['name.utf-8'])) {
            return $this->data['info']['name.utf-8'];
        }
        return $this->data['info']['name'];
    }

    /**
     * Get the total size in bytes of the files in the torrent. For a single file torrent, it'll
     * just be the 'length' key in the 'info' dictionary, else we iterate through the 'files' list
     * and add up the 'length' of each element.
     *
     * @return int
     */
    public function getSize() : int {
        $cur_size = 0;
        if (!isset($this->data['info']['files'])) {
            $cur_size = $this->data['info']['length'];
        }
        else {
            foreach ($this->data['info']['files'] as $file) {
                $cur_size += $file['length'];
            }
        }
        return $cur_size;
    }

    /**
     * Get an array of files that are in the torrent, where each element is a array that contains
     * the keys 'name' and 'size'. For single torrent files, then we just take the name and length
     * keys from the info dictionary. For multiple file torrents, we then iterate through the
     * 'files' list where each element has 'length' and 'path' (which is a list of all components
     * of the path, which we can join together with '/').
     *
     * @return array
     */
    public function getFileList() : array {
        $files = [];
        if (!isset($this->data['info']['files'])) {
            // Single-file torrent
            $name = (isset($this->data['info']['name.utf-8']) ?
                $this->data['info']['name.utf-8'] :
                $this->data['info']['name']);
            $size = $this->data['info']['length'];
            $files[] = ['path' => $name, 'size' => $size];
        }
        else {
            $size = 0;
            foreach ($this->data['info']['files'] as $file) {
                $size += $file['length'];
                $path_key = isset($file['path.utf-8']) ? 'path.utf-8' : 'path';
                $files[] = ['path' => implode('/', $file[$path_key]), 'size' => $file['length']];
            }
            usort(
                $files,
                function ($a, $b) {
                    return strnatcasecmp($a['path'], $b['path']);
                }
            );
        }
        return array('total_size' => $size, 'files' => $files);
    }

    public function hasFiles(): bool {
        return isset($this->data['info']['files']);
    }

    public function hasEncryptedFiles(): bool {
        return isset($this->data['encrypted_files']);
    }

    /**
     * Returns an array of strings formatted to be inserted into a Gazelle database into the table
     * torrents.FileList which is then used for displaying the table of files to the user when
     * viewing the group. Format of each string is:
     * {extension} s{size} {name} {delimiter}
     * We use the delimiter so that we can split the first three apart via ' ' and that then we
     * use the delimiter to find where the name ends.
     *
     * @return array
     */
    public function getGazelleFileList() : array {
        $files = [];
        foreach ($this->getFileList()['files'] as $file) {
            $path = $file['path'];
            $size = $file['size'];
            $path = $this->makeUTF8(strtr($path, "\n\r\t", '   '));
            $ext_pos = strrpos($path, '.');
            // Should not be $ExtPos !== false. Extension-less files that start with a .
            // should not get extensions
            $ext = ($ext_pos ? trim(substr($path, $ext_pos + 1)) : '');
            $files[] =  sprintf("%s s%ds %s %s", ".$ext", $size, $path, self::$utf8_filelist_delim);
        }
        return $files;
    }

    /**
     * Given a string, convert it to UTF-8 format, if it's not already in UTF-8.
     *
     * @param string $str input to convert to utf-8 format
     *
     * @return string
     */
    private function makeUTF8(string $str) : string {
        if (preg_match('//u', $str)) {
            $encoding = 'UTF-8';
        }
        if (empty($encoding)) {
            $encoding = mb_detect_encoding($str, 'UTF-8, ISO-8859-1');
        }
        // Legacy thing for Gazelle, leaving it in, but not going to bother testing
        // @codeCoverageIgnoreStart
        if (empty($encoding)) {
            $encoding = 'ISO-8859-1';
        }
        // @codeCoverageIgnoreEnd
        if ($encoding === 'UTF-8') {
            return $str;
        }
        else {
            return @mb_convert_encoding($str, 'UTF-8', $encoding);
        }
    }
}

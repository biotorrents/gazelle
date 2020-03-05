<?php

namespace OrpheusNET\BencodeTorrent;

class Bencode {
    protected $data = null;

    /**
     * Sets the internal data array
     * @param mixed $data
     * @throws \RuntimeException
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Given a BEncoded string and decode it
     * @param string $data
     * @throws \RuntimeException
     */
    public function decodeString(string $data) {
        $this->data = $this->decode($data);
    }

    /**
     * Given a path to a file, decode the contents of it
     *
     * @param string $path
     * @throws \RuntimeException
     */
    public function decodeFile(string $path) {
        $this->data = $this->decode(file_get_contents($path, FILE_BINARY));
    }

    /**
     * Decodes a BEncoded string to the following values:
     * - Dictionary (starts with d, ends with e)
     * - List (starts with l, ends with e
     * - Integer (starts with i, ends with e
     * - String (starts with number denoting number of characters followed by : and then the string)
     *
     * @see https://wiki.theory.org/index.php/BitTorrentSpecification
     *
     * @param string $data
     * @param int    $pos
     * @return mixed
     */
    protected function decode(string $data, int &$pos = 0) {
        $start_decode = $pos === 0;
        if ($data[$pos] === 'd') {
            $pos++;
            $return = [];
            while ($data[$pos] !== 'e') {
                $key = $this->decode($data, $pos);
                $value = $this->decode($data, $pos);
                if ($key === null || $value === null) {
                    break;
                }
                if (!is_string($key)) {
                    throw new \RuntimeException('Invalid key type, must be string: '.gettype($key));
                }
                $return[$key] = $value;
            }
            ksort($return);
            $pos++;
        }
        elseif ($data[$pos] === 'l') {
            $pos++;
            $return = [];
            while ($data[$pos] !== 'e') {
                $value = $this->decode($data, $pos);
                $return[] = $value;
            }
            $pos++;
        }
        elseif ($data[$pos] === 'i') {
            $pos++;
            $digits = strpos($data, 'e', $pos) - $pos;
            $return = substr($data, $pos, $digits);
            if ($return === '-0') {
                throw new \RuntimeException('Cannot have integer value -0');
            }
            $multiplier = 1;
            if ($return[0] === '-') {
                $multiplier = -1;
                $return = substr($return, 1);
            }
            if (!ctype_digit($return)) {
                $msg = 'Cannot have non-digit values in integer number: '.$return;
                throw new \RuntimeException($msg);
            }
            $return = $multiplier * ((int) $return);
            $pos += $digits + 1;
        }
        else {
            $digits = strpos($data, ':', $pos) - $pos;
            $len = (int) substr($data, $pos, $digits);
            $pos += ($digits + 1);
            $return = substr($data, $pos, $len);
            $pos += $len;
        }
        if ($start_decode) {
            if ($pos !== strlen($data)) {
                throw new \RuntimeException('Could not fully decode bencode string');
            }
        }
        return $return;
    }

    /**
     * Get the internal data array
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @throws \RuntimeException
     */
    protected function hasData() {
        if ($this->data === null) {
            throw new \RuntimeException('Must decode proper bencode string first');
        }
    }

    /**
     * @return string
     */
    public function getEncode() : string {
        $this->hasData();
        return $this->encodeVal($this->data);
    }

    /**
     * @param mixed $data
     * @return string
     */
    protected function encodeVal($data) : string {
        if (is_array($data)) {
            $return = '';
            $check = -1;
            $list = true;
            foreach ($data as $key => $value) {
                if ($key !== ++$check) {
                    $list = false;
                    break;
                }
            }

            if ($list) {
                $return .= 'l';
                foreach ($data as $value) {
                    $return .= $this->encodeVal($value);
                }
            }
            else {
                $return .= 'd';
                foreach ($data as $key => $value) {
                    $return .= $this->encodeVal(strval($key));
                    $return .= $this->encodeVal($value);
                }
            }
            $return .= 'e';
        }
        elseif (is_integer($data)) {
            $return = 'i'.$data.'e';
        }
        else {
            $return = strlen($data) . ':' . $data;
        }
        return $return;
    }
}

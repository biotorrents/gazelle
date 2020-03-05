<?php

function safe_b64encode($string) {
  $data = base64_encode($string);
  $data = str_replace(array('+','/','='),array('-','_',''),$data);
  return $data;
}

class Torrent {
  public $metadata;
  public $name;
  public $id;
  
  function __construct ($url_or_file) {
    $this->name = urldecode(end(explode('/', $url_or_file)));
    $this->id = safe_b64encode($url_or_file);

    $filesize = (int) array_change_key_case(
      get_headers($url_or_file, TRUE))['content-length'];
    $piece_size = $this->get_piece_size($filesize);
    $log = getcwd() . "/log/" . $this->id;

    # now create the actual torrent
    $this->metadata = array(
        'announce' => 'http://academictorrents.com/announce.php',
        'encoding' => 'UTF-8',
        'info' => array(
            'length'       => $filesize,
            'name'         => $this->name,
            'pieces'       => $this->make_pieces($url_or_file, $piece_size,
                                                 $filesize, $log),
            'piece length' => $piece_size,
        ),
        'url-list' => array($url_or_file),
    );
    $this->save();
  }

  static function bencode($var) {
    if (is_int($var)) {
      return 'i' . $var . 'e';
    } else if (is_string($var)) {
      return strlen($var) . ':' . $var;
    } else if (is_array($var)) {
      # must distinguish between dict and list
      for ($i = 0; $i < count($var); $i++) {
        # if dict, cannot index using ints?
        if (!isset($var[$i])) {
          $dictionary = $var;
          ksort($dictionary);
          $ret = 'd';
          foreach ($dictionary as $key => $value) {
            $ret .= Torrent::bencode($key) . Torrent::bencode($value);
          }
          return $ret . 'e';
        }
      }
      $ret = 'l';
      foreach ($var as $value) {
        $ret .= Torrent::bencode($value);
      }
      return $ret . 'e';
    }
  }

  private function get_piece_size($filesize) {
    $piece_length = 524288;
    while (ceil($filesize / $piece_length) == 1) {
        $piece_length /= 2;
    }
    while (ceil($filesize / $piece_length) > 1500) {
        $piece_length *= 2;
    }
    return $piece_length;
  }

  private function make_pieces($url, $piece_length, $filesize, $logfile) {
    if (($fp = fopen($url, 'r')) == FALSE) {
        return FALSE;
    }
    $pieces = '';
    $part = '';

    $position = 0;
    $i = 0;
    while ($position < $filesize) {
      $bytes_read = 0;
      # fread doesn't actually read in the correct number of bytes
      # piece together multiple freads
      while ($bytes_read < $piece_length && $position < $filesize) {
        $this_part = fread($fp, min($piece_length, $filesize - $position));
        $bytes_read += strlen($this_part);
        $position += strlen($this_part);
        $part .= $this_part;
      }
      $next_part = substr($part, $piece_length);
      $part = substr($part, 0, $piece_length);
      $piece = sha1($part, $raw_output=TRUE);
      $pieces .= $piece;

      $part = $next_part;

      if ($position > $filesize) {
        $position = $filesize;
      }
      # log progress every 5 pieces
      if ($i++ % 5 == 0 || $position == $filesize) {
        $log = fopen($logfile, "w");
        fwrite($log, $position . '/' . $filesize);
        fflush($f);
        fclose($log);
      }
    }
    fflush($f);
    fclose($fp);
    return $pieces;
  }

  private function save() {
    $dir = getcwd() . "/torrents/" . $this->id; 
    mkdir($dir);
    $f = fopen($dir . "/" . $this->name . ".torrent", "w");
    fwrite($f, Torrent::bencode($this->metadata));
    fflush($f);
    fclose($f);
  }
}

/*
 create:
 1. Input: GET variables url-encoded string "url".
 2. Creates log for "id" in ./log/ for the download.
 3. Streamingly creates torrent, updating log.
 4. Dumps finished torrent file in ./torrents/
*/

# Main
if (isset($_GET["url"]) && filter_var($_GET["url"], FILTER_VALIDATE_URL)) {
  $logname = getcwd() . "/log/" . safe_b64encode($_GET["url"]);

  # if log exists, don't start new torrent
  if (file_exists($logname)) {
    # check status; return "done", or "processing"
    $contents = explode("/", stream_get_contents(fopen($logname, "r")));
    $progress = intval($contents[0]);
    $total = intval($contents[1]);
    if ($progress==$total) {
      echo(json_encode(array(
        'status' => 'OK',
        'msg' => "Torrent complete!")));
    } else {
      echo(json_encode(array(
        'status' => 'OK',
        'msg' => "Torrent already processing!")));
    }    
  } else {
    $torrent = new Torrent($_GET["url"]);
  }
} else {
  echo(json_encode(array(
    'status' => 'ERROR',
    'msg' => "Requires GET variable 'url'")));
};
?>

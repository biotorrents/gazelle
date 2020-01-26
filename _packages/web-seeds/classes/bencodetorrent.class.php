<?php

class BencodeTorrent extends BencodeDecode
{
    
    # Line 172
    /**
     * Add list of web seeds to a torrent
     */
    public static function add_web_seeds($Data, $Urls)
    {
        $r = 'd8:url-listl';
        for ($i = 0; $i < count($Urls); $i++) {
            $r .= 'l';
            for ($j = 0; $j < count($Urls[$i]); $j++) {
                $r .= strlen($Urls[$i][$j]).':'.$Urls[$i][$j];
            }
            $r .= 'e';
        }
        return $r.'e'.substr($Data, 1);
    }
}
# EOF

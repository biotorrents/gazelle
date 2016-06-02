<?php
class MediaInfo {
    public static function parse($string, $raw=false) {
        $t = new ParseManager($string);
        $t->parse();
        return $raw ? $t->output_raw() : $t->output();
    }
}

class ParseManager {
    protected $lines;
    protected $parsed_lines;
    protected $index;
    protected $parsers;
    protected $output;
    protected $available_parsers = array(
        'general'=> 'GeneralSectionParser',
        'video'=> 'VideoSectionParser',
        'audio'=> 'AudioSectionParser',
        'text'=> 'TextSectionParser',
    );

    const GENERIC_PARSER = 'generic_parser';
    const MEDIAINFO_START = 'general';
    public function __construct($string='') {
        $this->index = 0;
        $this->output = '';
        $this->parsed_lines = array();
        $this->set_string($string);
        $p = new SectionParser();
        $this->add_parser($p);
    }

    protected function set_string($string) {
        $string = trim($string);
        $string = static::strip_escaped_tags($string);
        $lines = preg_split("/\r\n|\n|\r/", $string);
        array_walk($lines,function(&$l) {
            $l=trim($l);
        });
        $this->lines = $lines;
    }

    protected function add_parser($p,$name='') {
        $p->set_lines($this->lines,$this->index);
        if (!$name) $name = static::GENERIC_PARSER;
        $this->parsers[$name][] = $p;
    }

    public function parse() {
        // get the next section
        while ($this->index < count($this->lines) &&
          !($line = $this->parsers[static::GENERIC_PARSER][0]->parse_line()));
        $section = SectionParser::section_name($line);
        $this->index--; // go back to line we just read

        // we can have multiple mediainfo files inside the block, so handle that case here
        if ($section == self::MEDIAINFO_START && isset($this->parsers[$section])) {
            $this->new_mediainfo();
        }
        if (isset($this->available_parsers[$section])){
            $parser = new $this->available_parsers[$section];
            $this->add_parser($parser,$section);
            // parse section using the parser
            while ($line = $parser->parse_line()) $this->parsed_lines[] = $line;

            $this->parsed_lines[] = '';
        }
        else {
            // skip until the next blank line or until the next general section
            while ($line = $this->parsers[static::GENERIC_PARSER][0]->parse_line()){
                $section = SectionParser::section_name($line);
                if ($section == self::MEDIAINFO_START) {
                    $this->index--; // go back to line we just read
                    break;
                }
            }
        }

        // keep iterating until the last line
        if ($this->index < count($this->lines)) {
            $this->parse();
        }
    }

    public function output($cummulative=true) {
        $string = implode("<br />\n",$this->parsed_lines);
        if (!isset($this->parsers['general'])) return $string;

        $midiv_start = '<div class="spoilerContainer hideContainer">
            <input type="button" class="spoilerButton" onclick="BBCode.spoiler(this);" value="Show '.
            $this->parsers['general'][0]->filename.
            '" /><blockquote class="spoiler hidden">';
        $midiv_end = "</blockquote></div>";

        $output = '<table class="mediainfo"><tbody><tr><td>';
        $output .= $this->parsers['general'][0]->output();
        if (isset($this->parsers['video'])){
            $output .= '</td><td>';
            $output .= $this->parsers['video'][0]->output();
        }
        if (isset($this->parsers['audio'])){
            $output .= '</td><td>';
            $output .= '<table><caption>Audio</caption><tbody>';
            foreach($this->parsers['audio'] as $index => $ap) {
                $output .= sprintf('<tr><td>#%d: &nbsp;</td><td>%s</td></tr>',intval($index+1),$ap->output());
            }
            $output .= '</tbody></table>';
        }
        if (isset($this->parsers['text'])){
            // subtitles table will be printed below the previous table
            $output .= '<br />';
            $output .= '<table><caption>Subtitles</caption><tbody>';
            foreach($this->parsers['text'] as $index => $tp) {
                $output .= sprintf('<tr><td>#%d: &nbsp;</td><td>%s</td></tr>',intval($index+1),$tp->output());
            }
            $output .= '</tbody></table>';
        }
        $output .= '</td></tr></tbody></table><br />';
        $output = $midiv_start . $string . $midiv_end . $output;
        if ($cummulative) {
            $output = $this->output . $output;
        }
        return  $output;
    }

    public function output_raw() {
        $output = array();
        $sections = ['general', 'video', 'audio', 'text'];

        foreach($sections as $section) {
            if (isset($this->parsers[$section])) {
                $output[$section] = array();
                foreach($this->parsers[$section] as $index => $parser) {
                    $output[$section][$index] = $parser->output_raw();
                }
            }
        }
        return $output;
    }

    // strip escaped html tags
    // this is not done for security, just to beautify things (html should already be escaped)
    public static function strip_escaped_tags($string) {
        // use the php function first
        $string = strip_tags($string);

        $gt = '&gt;|&#62;|>';
        $lt = '&lt;|&#60;|<';

        // there is no opening tag, so don't go through the rest of the regexes
        if (!preg_match("($lt)",$string))
            return $string;

        $tag_match = "/(?:$lt)(?P<tag>(?:(?!$gt).)*)(?:$gt)/ims";

        // this should match and remove tags
        $string = preg_replace($tag_match,'',$string);

        return $string;
    }

    protected function new_mediainfo() {
        $this->output .= $this->output(false);
        $this->parsed_lines = array();
        foreach(array_keys($this->parsers) as $key) {
            if ($key != static::GENERIC_PARSER)
                unset($this->parsers[$key]);
        }
    }

}

class SectionParser {
    protected $lines;
    protected $index;


    public function __construct(&$lines=array(),&$i=0) {
        $this->set_lines($lines,$i);
    }
    public function set_lines(&$lines=array(),&$i=0){
        $this->lines = &$lines;
        $this->index = &$i;
    }
    // should always return the read line
    public function parse_line(){
        if (!isset($this->lines[$this->index])) {
            return null;
        }
        $line = $this->lines[$this->index++];
        $pair = static::property_value_pair($line);
        $this->handle_cases($pair['property'],$pair['value']);
        return $line;
    }
    public function output() {

    }
    protected function handle_cases($property, $value) {

    }
    public static function section_name($string) {
        if (!$string) return false;
        $mistart="/^(?:general$|unique id|complete name)/i";
        if (preg_match($mistart, $string)) return ParseManager::MEDIAINFO_START;
        $words = explode(' ',$string);
        return strtolower($words[0]);
    }
    public static function property_value_pair($string) {
        $pair = explode(":", $string, 2);
        return array('property'=>strtolower(trim($pair[0])),'value'=>trim($pair[1]??''));
    }
    public static function strip_path($string) { // remove filepath
        $string = str_replace("\\", "/", $string);
        $path_parts = pathinfo($string);
        return $path_parts['basename'];
    }
    public static function parse_size($string) {
        return str_replace(array('pixels', ' '), null, $string);
    }
    protected static function table_head($caption) {
        return "<table class='nobr'><caption>$caption</caption><tbody>";
    }
    protected static function table_row($property,$value) {
        if ($value)
            return "<tr><td>$property:&nbsp;&nbsp;</td><td>$value</td></tr>";
        return '';
    }
    protected static function table_tail(){
        return '</tbody></table>';
    }

}

class AudioSectionParser extends SectionParser {
    protected $audioformat;
    protected $audiobitrate;
    protected $audiochannels;
    protected $audiochannelpositions;
    protected $audiotitle;
    protected $audiolang;
    protected $audioprofile;
    protected $form_audioformat;
    protected $form_audiochannels;

    protected function handle_cases($property,$value) {
        switch ($property) {
            case "format":
                $this->audioformat = $value;
                break;
            case "bit rate":
                $this->audiobitrate = $value;
                break;
            case "channel(s)":
                $this->audiochannels = $value;
                break;
            case "channel positions":
                $this->audiochannelpositions = $value;
                break;
            case "title":
                $this->audiotitle = $value;
                break;
            case "language":
                $this->audiolang = $value;
                break;
            case "format profile":
                $this->audioprofile = $value;
                break;
        }
    }
    public function output() {
        $this->process_vars();
        $output = $this->audiolang . ' ' . $this->channels() . ' ' . $this->format();
        $output .= ($this->audiobitrate) ? " @ $this->audiobitrate" : '';
        $output .= ($this->audiotitle) ? " ($this->audiotitle)" : '';
        return $output;
    }
    public function output_raw() {
        $this->process_vars();
        $output = array();
        $properties = [
            'audioformat', 'audiobitrate', 'audiochannels',
            'audiochannelpositions', 'audiotitle', 'audiolang', 'audioprofile',
            'form_audioformat', 'form_audiochannels'
        ];
        foreach($properties as $property) {
            if ($this->$property) $output[$property] = $this->$property;
        }
        return $output;
    }
    protected function process_vars() {
        $this->form_audioformat = $this->form_format();
        $this->form_audiochannels = $this->form_channels();
    }
    protected function format() {
        if (strtolower($this->audioformat) === 'mpeg audio') {
            switch (strtolower($this->audioprofile)) {
                case 'layer 3':
                    return 'MP3';
                case 'layer 2':
                    return 'MP2';
                case 'layer 1':
                    return 'MP1';
            }
        }
        return $this->audioformat;
    }
    protected function form_format() {
        // Not implemented: Real Audio, DTS-HD
        switch (strtolower($this->format())) {
            case 'mp2':
                return 'MP2';
            case 'mp3':
                return 'MP3';
            case 'vorbis':
                return 'OGG';
            case 'aac':
                return 'AAC';
            case 'ac-3':
                return 'AC3';
            case 'truehd':
                return 'TrueHD';
            case 'dts':
                switch (strtolower($this->audioprofile)) {
                    case 'es':
                        return 'DTS-ES';
                    case 'ma / core':
                        return 'DTS-HD MA';
                    default:
                        return 'DTS';
                }
            case 'flac':
                return 'FLAC';
            case 'pcm':
                return 'PCM';
            case 'wma':
                return 'WMA';
        }
    }
    protected function channels() {
        if (isset($this->audiochannels)) {
            $chans = preg_replace('/^(\d).*$/', '$1', $this->audiochannels);

            if (isset($this->audiochannelpositions) && preg_match('/LFE/',
                    $this->audiochannelpositions)) {
                $chans -= .9;
            } else {
                $chans = $chans . '.0';
            }

            return $chans . 'ch';
        }
    }
    protected function form_channels() {
        return preg_replace('/ch/', '', $this->channels());
    }
}

class GeneralSectionParser extends SectionParser {
    public $filename;
    protected $generalformat;
    protected $duration;
    protected $filesize;
    protected $form_codec;
    protected $form_releasegroup;

    protected function handle_cases($property,$value) {
        switch ($property) {
            case "complete name":
                // Remove autodetected urls
                $value = preg_replace('#\[autourl(?:=.+)?\](.+)\[/autourl\]#', '$1', $value);
                $this->filename = static::strip_path($value);
                $this->lines[$this->index-1] = "Complete name : " . $this->filename;
                if (strlen($this->filename) > 100)
                    $this->filename = substr($this->filename, 0, 80) . '...' . substr($this->filename, -17);
                break;
            case "format":
                $this->generalformat = $value;
                break;
            case "duration":
                $this->duration = $value;
                break;
            case "file size":
                $this->filesize = $value;
                break;
        }
    }
    public function output() {
        $this->process_vars();
        $output = static::table_head('General');
        $properties = [
            'Container' => 'generalformat',
            'Runtime' => 'duration',
            'Size' => 'filesize'
        ];
        foreach($properties as $property => $value) {
            $output .= static::table_row($property,$this->$value);
        }
        $output .= static::table_tail();
        return  $output;
    }
    public function output_raw() {
        $this->process_vars();
        $output = array();
        $properties = [
            'filename', 'generalformat', 'duration', 'filesize', 'form_codec',
            'form_releasegroup'
        ];
        foreach($properties as $property) {
            if ($this->$property) $output[$property] = $this->$property;
        }
        return $output;
    }
    protected function process_vars() {
        switch (strtolower($this->generalformat)) {
            case 'mpeg-ts':
                $this->form_codec = 'MPEG-TS';
                break;
            // We can't determine if it's DVD5 or DVD9, so don't guess.
            case 'mpeg-ps':
                $this->form_codec = '---';
                break;
        }
        $matches = array();
        preg_match('/(?:^|.*\\|\/)\[(.*?)\].*$/',
            $this->filename, $matches);
        $this->form_releasegroup = $matches ? $matches[1] : '';
    }
}

class TextSectionParser extends SectionParser {
    protected $title;
    protected $language;
    protected $format;
    protected $default;
    protected $processed_language;
    protected $form_format;

    protected function handle_cases($property,$value) {
        switch ($property) {
            case 'title':
                $this->title = $value;
                break;
            case 'language':
                $this->language = $value;
                break;
            case 'format':
                $this->format = $value;
                break;
            case 'default':
                $this->default = ($value == 'Yes');
                break;
        }
    }
    public function output() {
        $this->process_vars();
        $language = $this->processed_language;
        $output = "$language ($this->format)";
        if ($this->title) $output .= " ($this->title)";
        if ($this->default) $output .= ' (default)';
        return $output;
    }
    public function output_raw() {
        $this->process_vars();
        $output = array();
        $properties = [
            'title', 'language', 'format', 'default', 'processed_language',
            'form_format'
        ];
        foreach($properties as $property) {
            if ($this->$property) $output[$property] = $this->$property;
        }
        return $output;
    }
    protected function process_vars() {
        $this->processed_language = ($this->language) ?
            $this->language : 'Unknown';
        $this->form_format = 'Softsubbed';
    }
}

class VideoSectionParser extends SectionParser {
    protected $videoformat;
    protected $videoformatversion;
    protected $codec;
    protected $width;
    protected $height;
    protected $writinglibrary;
    protected $frameratemode;
    protected $framerate;
    protected $aspectratio;
    protected $bitrate;
    protected $bitratemode;
    protected $nominalbitrate;
    protected $bpp;
    protected $bitdepth;
    protected $processed_codec;
    protected $processed_resolution;
    protected $processed_framerate;
    protected $form_codec;
    protected $form_resolution;

    protected function handle_cases($property,$value) {
        switch ($property) {
            case "format":
                $this->videoformat = $value;
                break;
            case "format version":
                $this->videoformatversion = $value;
                break;
            case "codec id":
                $this->codec = strtolower($value);
                break;
            case "width":
                $this->width = static::parse_size($value);
                break;
            case "height":
                $this->height = static::parse_size($value);
                break;
            case "writing library":
                $this->writinglibrary = $value;
                break;
            case "frame rate mode":
                $this->frameratemode = $value;
                break;
            case "frame rate":
                // if variable this becomes Original frame rate
                $this->framerate = $value;
                break;
            case "display aspect ratio":
                $this->aspectratio = $value;
                break;
            case "bit rate":
                $this->bitrate = $value;
                break;
            case "bit rate mode":
                $this->bitratemode = $value;
                break;
            case "nominal bit rate":
                $this->nominalbitrate = $value;
                break;
            case "bits/(pixel*frame)":
                $this->bpp = $value;
                break;
            case 'bit depth':
                $this->bitdepth = $value;
                break;
        }
    }
    public function output() {
        $this->process_vars();
        $output = static::table_head('Video');
        $properties = [
            'Codec' => 'processed_codec',
            'Bit depth' => 'bitdepth',
            'Resolution' => 'processed_resolution',
            'Aspect ratio' => 'aspectratio',
            'Frame rate' => 'processed_framerate',
            'Bit rate' => 'bitrate',
            'BPP' => 'bpp'
        ];
        foreach($properties as $property => $value) {
            $output .= static::table_row($property,$this->$value);
        }
        $output .= static::table_tail();
        return  $output;
    }
    public function output_raw() {
        $this->process_vars();
        $output = array();
        $properties = [
            'videoformat', 'videoformatversion', 'codec', 'width', 'height',
            'writinglibrary', 'frameratemode', 'framerate', 'aspectratio',
            'bitrate', 'bitratemode', 'nominalbitrate', 'bpp', 'bitdepth',
            'processed_codec', 'processed_resolution', 'processed_framerate',
            'form_codec', 'form_resolution'
        ];
        foreach($properties as $property) {
            if ($this->$property) $output[$property] = $this->$property;
        }
        return $output;
    }
    protected function process_vars() {
        $this->processed_codec = $this->compute_codec();
        $this->processed_resolution = ($this->width) ?
            $this->width . 'x' . $this->height : '';
        $this->processed_framerate = (strtolower($this->frameratemode) !=
            "constant" && $this->frameratemode) ?
            $this->frameratemode : $this->framerate;
        $this->form_codec = $this->compute_form_codec();
        $this->form_resolution = $this->compute_form_resolution();
    }
    protected function compute_codec() {
        switch (strtolower($this->videoformat)) {
            case "mpeg video":
                switch (strtolower($this->videoformatversion)) {
                    case "version 2":
                        return "MPEG-2";
                    case "version 1":
                        return "MPEG-1";
                }
                return $this->videoformat;
        }

        switch (strtolower($this->codec)) {
            case "div3":
                return "DivX 3";
            case "divx":
            case "dx50":
                return "DivX";
            case "xvid":
                return "XviD";
            case "x264":
                return "x264";
        }

        $chk = strtolower($this->codec);
        $wl = strtolower($this->writinglibrary);
        if (($chk === "v_mpeg4/iso/avc" || $chk === "avc1") && strpos($wl, "x264 core") === FALSE) {
            return "H264";
        } else if (($chk === "v_mpeg4/iso/avc" || $chk === "avc1") && strpos($wl, "x264 core") > -1)  {
            return "x264";
        } else if (strtolower($this->videoformat) === "avc" && strpos($wl, "x264 core") === FALSE) {
            return "H264";
        }

        if (($chk === 'v_mpegh/iso/hevc') || ($wl === 'hevc'))
            return 'H265';
    }
    protected function compute_form_codec() {
        // Not implemented: DVD5, DVD9, WMV, Real Video
        // MPEG-TS set as GeneralSectionParser::$form_codec if found
        // MPEG-PS sets GeneralSectionParser::$form_codec to blank form value
        //   so DVD5 or DVD9 is selected manually.
        $codec = $this->compute_codec();
        switch(strtolower($codec)) {
            case 'x264':
            case 'h264':
                return strtolower($this->bitdepth) == '10 bits' ?
                    'h264 10-bit' : 'h264';
            case 'h265':
                return 'h265';
            case 'xvid':
                return 'XviD';
            case 'divx':
            case 'divx 3':
                return 'DivX';
            case 'mpeg-1':
                return 'MPEG';
            case 'mpeg-2':
                return 'MPEG-2';
        }
        switch(strtolower($this->codec)) {
            case 'wmv3':
                return 'VC-1';
            case 'mp43':
                return 'MPEG-4 v3';
        }
        switch(strtolower($this->videoformat)) {
            case 'vc-1':
                return 'VC-1';
            case 's-mpeg 4 v3':
                return 'MPEG-4 v3';
        }
    }
    protected function compute_form_resolution() {
				global $Resolutions;
        $closest = null;
        if (isset($this->height)) {
            $resolutions = $Resolutions;
            foreach($resolutions as $resolution) {
                if (!isset($closest) || abs($this->height - $resolution) <
                        abs($this->height - $closest)) {
                    $closest = $resolution;
                }
            }
        }
        return $closest;
    }
}

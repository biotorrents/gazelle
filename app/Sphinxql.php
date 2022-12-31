<?php

#declare(strict_types=1);

class Sphinxql extends mysqli
{
    private static $Connections = [];
    private $Server;
    private $Port;
    private $Socket;
    private $Ident;
    private $Connected = false;

    public static $Queries = [];
    public static $Time = 0.0;

    /**
     * Initialize Sphinxql object
     *
     * @param string $Server server address or hostname
     * @param int $Port listening port
     * @param string $Socket Unix socket address, overrides $Server:$Port
     */
    public function __construct($Server, $Port, $Socket)
    {
        $this->Server = $Server;
        $this->Port = $Port;
        $this->Socket = $Socket;
        $this->Ident = self::get_ident($Server, $Port, $Socket);
    }

    /**
     * Create server ident based on connection information
     *
     * @param string $Server server address or hostname
     * @param int $Port listening port
     * @param string $Socket Unix socket address, overrides $Server:$Port
     * @return identification string
     */
    private static function get_ident($Server, $Port, $Socket)
    {
        if ($Socket) {
            return $Socket;
        } else {
            return "$Server:$Port";
        }
    }

    /**
     * Create Sphinxql object or return existing one
     *
     * @param string $Server server address or hostname
     * @param int $Port listening port
     * @param string $Socket Unix socket address, overrides $Server:$Port
     * @return Sphinxql object
     */
    public static function init_connection($Server, $Port, $Socket)
    {
        $Ident = self::get_ident($Server, $Port, $Socket);
        if (!isset(self::$Connections[$Ident])) {
            self::$Connections[$Ident] = new Sphinxql($Server, $Port, $Socket);
        }
        return self::$Connections[$Ident];
    }

    /**
     * Connect the Sphinxql object to the Sphinx server
     */
    public function sph_connect()
    {
        $debug = Debug::go();

        if ($this->Connected || $this->connect_errno) {
            return;
        }

        $debug['messages']->info("connecting to sphinx server $this->Ident");

        for ($Attempt = 0; $Attempt < 3; $Attempt++) {
            parent::__construct($this->Server, '', '', '', $this->Port, $this->Socket);
            if (!$this->connect_errno) {
                $this->Connected = true;
                break;
            }
            sleep(1);
        }

        if ($this->connect_errno) {
            $Errno = $this->connect_errno;
            $Error = $this->connect_error;
            $this->error("Connection failed. (".strval($Errno).": ".strval($Error).")");
            $debug['messages']->info("couldn't connect to sphinx server $this->Ident ($Errno $Error)");
        } else {
            $debug['messages']->info("connected to sphinx server $this->Ident");
        }
    }

    /**
     * Print a message to privileged users and optionally halt page processing
     *
     * @param string $Msg message to display
     * @param bool $Halt halt page processing. Default is to continue processing the page
     * @return Sphinxql object
     */
    public function error($Msg, $Halt = false)
    {
        $ENV = ENV::go();
        $debug = Debug::go();
        $ErrorMsg = 'SphinxQL ('.$this->Ident.'): '.strval($Msg);

        if ($Halt === true && ($ENV->dev || check_perms('site_debug'))) {
            echo '<pre>'.Text::esc($ErrorMsg).'</pre>';
            error();
        } elseif ($Halt === true) {
            error(400);
        }
    }

    /**
     * Escape special characters before sending them to the Sphinx server.
     * Two escapes needed because the first one is eaten up by the mysql driver.
     *
     * @param string $String string to escape
     * @return escaped string
     */
    public static function sph_escape_string($String)
    {
        return strtr(
            strtolower($String),
            array(
            '('=>'\\\\(',
            ')'=>'\\\\)',
            '|'=>'\\\\|',
            '-'=>'\\\\-',
            '@'=>'\\\\@',
            '~'=>'\\\\~',
            '&'=>'\\\\&',
            '\''=>'\\\'',
            '<'=>'\\\\<',
            '!'=>'\\\\!',
            '"'=>'\\\\"',
            '/'=>'\\\\/',
            '*'=>'\\\\*',
            '$'=>'\\\\$',
            '^'=>'\\\\^',
            '\\'=>'\\\\\\\\')
        );
    }

    /**
     * Register sent queries globally for later retrieval by debug functions
     *
     * @param string $QueryString query text
     * @param param $QueryProcessTime time building and processing the query
     */
    public static function register_query($QueryString, $QueryProcessTime)
    {

        #$debug = Debug::go();
        #$debug['sphinx']->info(self::$Queries);
        self::$Queries[] = array($QueryString, $QueryProcessTime);
        self::$Time += $QueryProcessTime;
    }
}

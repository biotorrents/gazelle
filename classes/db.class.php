<?php
declare(strict_types = 1);

/**
 * MySQL wrapper class
 * 
 * This class provides an interface to mysqli.
 * It provides debugging features and some other cool stuff.
 * 
 * Everything returned by this class is automatically escaped for output.
 * This can be turned off by setting $Escape to false in next_record or to_array.
 * 
 * 
 * Basic usage
 * ===========
 * 
 * Making a query:
 * 
 * $DB->prepared_query("
 *   SELECT *
 *   FROM table...
 * ");
 * 
 * Getting data from a query:
 * 
 * $array = $DB->next_record();
 * 
 * Escaping a string:
 * 
 * db_string($str);
 * 
 * 
 * Advanced usage
 * ==============
 * 
 * The conventional way of retrieving a row from a result set:
 * 
 * list($All, $Columns, $That, $You, $Select) = $DB->next_record();
 * 
 * This is how you loop over the result set:
 * 
 * while (list($All, $Columns, $That, $You, $Select) = $DB->next_record()) {
 *   echo "Do stuff with $All of the $Columns $That $You $Select";
 * }
 * 
 * There are also a couple more mysqli functions that have been wrapped:
 * 
 *   - affected_rows
 *   - close
 *   - inserted_id
 *   - record_count
 * 
 * A few handy custom functions:
 * 
 * collect($Key)
 *   Loops over the result set, creating an array from one of the fields ($Key).
 *   For an example, see forum.php.
 * 
 * set_query_id($ResultSet)
 *   This class can only hold one result set at a time.
 *   Using set_query_id allows you to set the result set it's using to $ResultSet.
 *   This result set should have been obtained earlier by using $DB->prepared_query, e.g.,
 * 
 *   $FoodRS = $DB->prepared_query("
 *     SELECT *
 *     FROM food
 *   ");
 * 
 *   $DB->prepared_query("
 *     SELECT *
 *     FROM drink
 *   ");
 * 
 *   $Drinks = $DB->next_record();
 *   $DB->set_query_id($FoodRS);
 *   $Food = $DB->next_record();
 * 
 * to_array($Key = false)
 *   Transforms an entire result set into an array.
 *   If $Key is set, the function uses $Key as the index.
 *   Otherwise, it uses an iterator.
 *   For an example, check out forum.php.
 */


/**
 * db_string
 *
 * Handles escaping.
 */
function db_string($String, $DisableWildcards = false)
{
    global $DB;
    $DB->connect(0);

    # Connect and mysqli_real_escape_string()
    # Previously called $DB->escape_str, now below
    # todo: Fix the bad escapes everywhere; see below

    if (!is_string($String)) { # This is the correct way,
    #if (is_array($String)) { # but this prevents errors
        error('Attempted to escape non-string.', $NoHTML = true);
        $String = '';
    } else {
        $String = mysqli_real_escape_string($DB->LinkID, $String);
    }

    // Remove user input wildcards
    if ($DisableWildcards) {
        $String = str_replace(array('%','_'), array('\%','\_'), $String);
    }

    return $String;
}


/**
 * db_array
 */
function db_array($Array, $DontEscape = [], $Quote = false)
{
    foreach ($Array as $Key => $Val) {
        if (!in_array($Key, $DontEscape)) {
            if ($Quote) {
                $Array[$Key] = '\''.db_string(trim($Val)).'\'';
            } else {
                $Array[$Key] = db_string(trim($Val));
            }
        }
    }
    return $Array;
}


/**
 * \DB::class
 */
class DB
{
    public $LinkID = false;
    protected $QueryID = false;
    protected $StatementID = false;
    protected $PreparedQuery = false;
    protected $Record = [];
    protected $Row;
    protected $Errno = 0;
    protected $Error = '';

    public $Queries = [];
    public $Time = 0.0;

    protected $Database = '';
    protected $Server = '';
    protected $User = '';
    protected $Pass = '';
    protected $Port = 0;
    protected $Socket = '';

    protected $Key = '';
    protected $Cert = '';
    protected $CA = '';


    /**
     * __construct
     */
    public function __construct(
        $Database = null,
        $User = null,
        $Pass = null,
        $Server = null,
        $Port = null,
        $Socket = null,
        $Key = null,
        $Cert = null,
        $CA = null
    ) {
        $ENV = ENV::go();

        $this->Database = $ENV->getPriv('SQLDB');
        $this->User = $ENV->getPriv('SQLLOGIN');
        $this->Pass = $ENV->getPriv('SQLPASS');
        $this->Server = $ENV->getPriv('SQLHOST');
        $this->Port = $ENV->getPriv('SQLPORT');
        $this->Socket = $ENV->getPriv('SQLSOCK');

        $this->Key = $ENV->getPriv('SQL_KEY');
        $this->Cert = $ENV->getPriv('SQL_CERT');
        $this->CA = $ENV->getPriv('SQL_CA');
    }


    /**
     * halt
     */
    public function halt($Msg)
    {
        global $Debug, $argv;
        $DBError = 'MySQL: '.strval($Msg).' SQL error: '.strval($this->Errno).' ('.strval($this->Error).')';

        if ($this->Errno === 1194) {
            send_irc(ADMIN_CHAN, $this->Error);
        }

        $Debug->analysis('!dev DB Error', $DBError, 3600 * 24);
        if (DEBUG_MODE || check_perms('site_debug') || isset($argv[1])) {
            echo '<pre>'.display_str($DBError).'</pre>';
            if (DEBUG_MODE || check_perms('site_debug')) {
                print_r($this->Queries);
            }
            error(400, $NoHTML = true);
        } else {
            error(-1, $NoHTML = true);
        }
    }


    /**
     * connect
     */
    public function connect()
    {
        if (!$this->LinkID) {
            $this->LinkID = mysqli_init();

            /*
            mysqli_ssl_set(
                $this->LinkID,
                $this->Key,
                $this->Cert,
                $this->CA,
                null,
                null
            );
            */

            mysqli_real_connect(
                $this->LinkID,
                $this->Server,
                $this->User,
                $this->Pass,
                $this->Database,
                $this->Port,
                $this->Socket,
                # Needed for self-signed certs
                MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT
            );

            if (!$this->LinkID) {
                $this->Errno = mysqli_connect_errno();
                $this->Error = mysqli_connect_error();
                $this->halt('Connection failed (host:'.$this->Server.':'.$this->Port.')');
            }
        }
        mysqli_set_charset($this->LinkID, "utf8mb4");
    }


    /**
     * Prepare and execute a prepared query returning the result set.
     *
     * Utility function that wraps DB_MYSQL::prepare and DB_MYSQL::execute
     * as most times, the query is going to be one-off and this will save
     * on keystrokes. If you do plan to be executing a prepared query
     * multiple times with different bound parameters, you'll want to call
     * the two functions separately instead of this function.
     *
     * @param $Query
     * @param mixed ...$Parameters
     * @return bool|mysqli_result
     */
    public function prepared_query($Query, ...$Parameters)
    {
        $this->prepare($Query);
        return $this->execute(...$Parameters);
    }

    /**
     * prepare
     */
    public function prepare($Query, &...$BindVars)
    {
        $this->connect();
        $this->StatementID = mysqli_prepare($this->LinkID, $Query);

        if (!empty($BindVars)) {
            $Types = '';
            $TypeMap = ['string'=>'s', 'double'=>'d', 'integer'=>'i', 'boolean'=>'i'];

            foreach ($BindVars as $BindVar) {
                $Types .= $TypeMap[gettype($BindVar)] ?? 'b';
            }
            mysqli_stmt_bind_param($this->StatementID, $Types, ...$BindVars);
        }

        $this->PreparedQuery = $Query;
        return $this->StatementID;
    }


    /**
     * execute
     */
    public function execute()
    {
        $QueryStartTime = microtime(true);
        mysqli_stmt_execute($this->StatementID);
        $this->QueryID = mysqli_stmt_get_result($this->StatementID);
        $QueryRunTime = (microtime(true) - $QueryStartTime) * 1000;
        $this->Queries[] = [$this->PreppedQuery, $QueryRunTime, null];
        $this->Time += $QueryRunTime;
    }


    /**
     * Runs a raw query assuming pre-sanitized input. However, attempting to self sanitize (such
     * as via db_string) is still not as safe for using prepared statements so for queries
     * involving user input, you really should not use this function (instead opting for
     * prepared_query) {@See DB_MYSQL::prepared_query}
     *
     * When running a batch of queries using the same statement
     * with a variety of inputs, it's more performant to reuse the statement
     * with {@see DB_MYSQL::prepare} and {@see DB_MYSQL::execute}
     *
     * @return mysqli_result|bool Returns a mysqli_result object
     *                            for successful SELECT queries,
     *                            or TRUE for other successful DML queries
     *                            or FALSE on failure.
     *
     * @param $Query
     * @param int $AutoHandle
     * @return mysqli_result|bool
     */
    public function query($Query, &...$BindVars)
    {
        $QueryStartTime = microtime(true);
        $this->connect();

        // In the event of a MySQL deadlock, we sleep allowing MySQL time to unlock, then attempt again for a maximum of 5 tries
        for ($i = 1; $i < 6; $i++) {
            $this->StatementID = mysqli_prepare($this->LinkID, $Query);
            if (!empty($BindVars)) {
                $Types = '';
                $TypeMap = ['string'=>'s', 'double'=>'d', 'integer'=>'i', 'boolean'=>'i'];

                foreach ($BindVars as $BindVar) {
                    $Types .= $TypeMap[gettype($BindVar)] ?? 'b';
                }
                mysqli_stmt_bind_param($this->StatementID, $Types, ...$BindVars);
            }

            mysqli_stmt_execute($this->StatementID);
            $this->QueryID = mysqli_stmt_get_result($this->StatementID);

            if (DEBUG_MODE) {
                // In DEBUG_MODE, return the full trace on a SQL error (super useful
                // For debugging). do not attempt to retry to query
                if (!$this->QueryID) {
                    echo '<pre>' . mysqli_error($this->LinkID) . '<br><br>';
                    debug_print_backtrace();
                    echo '</pre>';
                    error();
                }
            }

            if (!in_array(mysqli_errno($this->LinkID), array(1213, 1205))) {
                break;
            }

            $Debug->analysis('Non-Fatal Deadlock:', $Query, 3600 * 24);
            trigger_error("Database deadlock, attempt $i");
            sleep($i * rand(2, 5)); // Wait longer as attempts increase
        }

        $QueryEndTime = microtime(true);
        $this->Queries[] = array($Query, ($QueryEndTime - $QueryStartTime) * 1000, null);
        $this->Time += ($QueryEndTime - $QueryStartTime) * 1000;

        if (!$this->QueryID && !$this->StatementID) {
            $this->Errno = mysqli_errno($this->LinkID);
            $this->Error = mysqli_error($this->LinkID);
            $this->halt("Invalid Query: $Query");
        }

        $this->Row = 0;
        return $this->QueryID;
    }


    /**
     * inserted_id
     */
    public function inserted_id()
    {
        if ($this->LinkID) {
            return mysqli_insert_id($this->LinkID);
        }
    }


    /**
     * next_record
     */
    public function next_record($Type = MYSQLI_BOTH, $Escape = true)
    { // $Escape can be true, false, or an array of keys to not escape
        if ($this->LinkID) {
            $this->Record = mysqli_fetch_array($this->QueryID, $Type);
            $this->Row++;

            if (!is_array($this->Record)) {
                $this->QueryID = false;
            } elseif ($Escape !== false) {
                $this->Record = Misc::display_array($this->Record, $Escape);
            }
            return $this->Record;
        }
    }


    /**
     * close
     */
    public function close()
    {
        if ($this->LinkID) {
            if (!mysqli_close($this->LinkID)) {
                $this->halt('Cannot close connection or connection did not open.');
            }
            $this->LinkID = false;
        }
    }


    /**
     * Returns an integer with the number of rows found
     * Returns a string if the number of rows found exceeds MAXINT
     */
    public function record_count()
    {
        if ($this->QueryID) {
            return mysqli_num_rows($this->QueryID);
        }
    }


    /**
     * Returns true if the query exists and there were records found
     * Returns false if the query does not exist or if there were 0 records returned
     */
    public function has_results()
    {
        return ($this->QueryID && $this->record_count() !== 0);
    }


    /**
     * affected_rows
     */
    public function affected_rows()
    {
        if ($this->LinkID) {
            return mysqli_affected_rows($this->LinkID);
        }
    }


    /**
     * info
     */
    public function info()
    {
        return mysqli_get_host_info($this->LinkID);
    }


    // Creates an array from a result set
    // If $Key is set, use the $Key column in the result set as the array key
    // Otherwise, use an integer
    public function to_array($Key = false, $Type = MYSQLI_BOTH, $Escape = true)
    {
        $Return = [];
        while ($Row = mysqli_fetch_array($this->QueryID, $Type)) {
            if ($Escape !== false) {
                $Row = Misc::display_array($Row, $Escape);
            }

            if ($Key !== false) {
                $Return[$Row[$Key]] = $Row;
            } else {
                $Return[] = $Row;
            }
        }

        mysqli_data_seek($this->QueryID, 0);
        return $Return;
    }


    //  Loops through the result set, collecting the $ValField column into an array with $KeyField as keys
    public function to_pair($KeyField, $ValField, $Escape = true)
    {
        $Return = [];
        while ($Row = mysqli_fetch_array($this->QueryID)) {
            if ($Escape) {
                $Key = display_str($Row[$KeyField]);
                $Val = display_str($Row[$ValField]);
            } else {
                $Key = $Row[$KeyField];
                $Val = $Row[$ValField];
            }
            $Return[$Key] = $Val;
        }

        mysqli_data_seek($this->QueryID, 0);
        return $Return;
    }


    //  Loops through the result set, collecting the $Key column into an array
    public function collect($Key, $Escape = true)
    {
        $Return = [];
        while ($Row = mysqli_fetch_array($this->QueryID)) {
            $Return[] = $Escape ? display_str($Row[$Key]) : $Row[$Key];
        }
        
        mysqli_data_seek($this->QueryID, 0);
        return $Return;
    }


    /**
     * set_query_id
     */
    public function set_query_id(&$ResultSet)
    {
        $this->QueryID = $ResultSet;
        $this->Row = 0;
    }


    /**
     * get_query_id
     */
    public function get_query_id()
    {
        return $this->QueryID;
    }


    /**
     * Useful extras from OPS
     */


    /**
     * Runs a prepared_query using placeholders and returns the matched row.
     * Stashes the current query id so that this can be used within a block
     * that is looping over an active resultset.
     *
     * @param string  $sql The parameterized query to run
     * @param mixed   $args  The values of the placeholders
     * @return array  resultset or null
     */
    public function row($Query, &...$BindVars)
    {
        $qid = $this->get_query_id();
        $this->query($Query, ...$BindVars);
        $result = $this->next_record(MYSQLI_NUM, false);
        $this->set_query_id($qid);
        return $result;
    }


    /**
     * Runs a prepared_query using placeholders and returns the first element
     * of the first row.
     * Stashes the current query id so that this can be used within a block
     * that is looping over an active resultset.
     *
     * @param string  $sql The parameterized query to run
     * @param mixed   $args  The values of the placeholders
     * @return mixed  value or null
     */
    public function scalar($Query, &...$BindVars)
    {
        $qid = $this->get_query_id();
        $this->query($Query, ...$BindVars);
        $result = $this->has_results() ? $this->next_record(MYSQLI_NUM, false) : [null];
        $this->set_query_id($qid);
        return $result[0];
    }
    # End OPS additions
}

<?php
declare(strict_types = 1);

/**
 * Adapted from
 * https://github.com/OPSnet/Gazelle/blob/master/app/Json.php
 */

abstract class Json
{
    protected $version;
    protected $source;
    protected $mode;


    /**
     * __construct
     */
    public function __construct()
    {
        #parent::__construct();
        $this->source = SITE_NAME;
        $this->mode = 0;
        $this->version = 1;
    }


    /**
     * The payload of a valid JSON response, implemented in the child class.
     * @return array Payload to be passed to json_encode()
     *         null if the payload cannot be produced (permissions, id not found, ...).
     */
    abstract public function payload(): ?array;


    /**
     * Configure JSON printing (any of the json_encode  JSON_* constants)
     *
     * @param int $mode the bit-or'ed values to confgure encoding results
     */
    public function setMode(string $mode)
    {
        $this->mode = $mode;
        return $this;
    }


    /**
     * set the version of the Json payload. Increment the
     * value when there is significant change in the payload.
     * If not called, the version defaults to 1.
     *
     * @param int version
     */
    public function setVersion(int $version)
    {
        $this->version = $version;
        return $this;
    }


    /**
     * General failure routine for when bad things happen.
     *
     * @param string $message The error set in the JSON response
     */
    public function failure(string $message)
    {
        print json_encode(
            array_merge(
                [
                    'status' => 'failure',
                    'response' => [],
                    'error' => $message,
                ],
                $this->info(),
                $this->debug(),
            ),
            $this->mode
        );
    }


    /**
     * emit
     */
    public function emit()
    {
        $payload = $this->payload();
        if (!$payload) {
            return;
        }
        print json_encode(
            array_merge(
                [
                    'status' => 'success',
                    'response' => $payload,
                ],
                $this->info(),
                $this->debug()
            ),
            $this->mode
        );
    }


    /**
     * debug
     */
    protected function debug()
    {
        if (!check_perms('site_debug')) {
            return [];
        }
        global $Debug;
        return [
            'debug' => [
                'queries'  => $Debug->get_queries(),
                'searches' => $Debug->get_sphinxql_queries(),
            ],
        ];
    }


    /**
     * info
     */
    protected function info()
    {
        return [
            'info' => [
                'source' => $this->source,
                'version' => $this->version,
            ]
        ];
    }


    /**
     * fetch
     *
     * Get resources over the API to populate Gazelle display.
     * Instead of copy-pasting the same SQL queries in many places.
     *
     * Takes a query string, e.g., "action=torrentgroup&id=1."
     * Requires an API key for the user ID 0 (minor database surgery).
     */
    public function fetch($Action, $Params = [])
    {
        $ENV = ENV::go();

        $Token = $ENV->getPriv('SELF_API');
        $Params = implode('&', $Params);

        $ch = curl_init();

        # todo: Make this use localhost and not HTTPS
        curl_setopt($ch, CURLOPT_URL, "https://$ENV->SITE_DOMAIN/api.php?action=$Action&$Params");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        # https://docs.biotorrents.de
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Accept: application/json',
                "Authorization: Bearer $Token",
            ]
        );

        $Data = curl_exec($ch);
        curl_close($ch);

        # Error out on bad query
        if (!$Data) {
            return error();
        } else {
            return json_decode($Data);
        }
    }
}

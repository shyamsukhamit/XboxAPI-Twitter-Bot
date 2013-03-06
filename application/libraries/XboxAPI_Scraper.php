<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class XboxAPI_Scraper {

//=================================================================================
// :constants
//=================================================================================

    // scraper version
    const XBOXAPI_SCRAPER_VERSION = 1.0;


//=================================================================================
// :vars
//=================================================================================

    // time units array
    public $units = array(
        "year"   => 29030400, // seconds in a year   (12 months)
        "month"  => 2419200,  // seconds in a month  (4 weeks)
        "week"   => 604800,   // seconds in a week   (7 days)
        "day"    => 86400,    // seconds in a day    (24 hours)
        "hour"   => 3600,     // seconds in an hour  (60 minutes)
        "minute" => 60,       // seconds in a minute (60 seconds)
        "second" => 1         // 1 second
    );

    private $config = array();


//=================================================================================
// :public
//=================================================================================

    /**
     * constructor - Sets some default prefs.
     *
     * the constructor can be passed an array of config values however this is not
     * needed unless you wish to do some customization of your own.
     */
    public function __construct( $options = array() )
    {
        // default configuration options
        $this->config = array_merge(
            array(
                // the current API limit
                'API_Limit' => 150,

                // the current API usage
                'API_Limit_current' => 0,

                // number of attempts to try per gamercard
                'gamertag_requests' => 5,

                // the number of current requests
                'gamertag_attempts' => 0,

                // if we have any errors they will be set here to be called later
                'error' => FALSE,

                // are we wanting to debug?
                'debug' => FALSE
            ),
            $options
        );
    }
    // --------------------------------------------------------------------


    /**
     * public function profile()
     * this is used to fetch the profile data from xboxapi.com
     *
     * @param gamertag  - this is the gamertag we wish to request data of
     * @return object
     */
    public function profile( $gamertag = FALSE )
    {
        if ( !$gamertag )
            return FALSE;

        return $this->fetch_data(
            $gamertag,  // gamertag we wish to lookup
            'profile'   // type of lookup
        );
    }
    //------------------------------------------------------------------


    /**
     * public function games()
     * this is used to fetch the games data from xboxapi.com
     *
     * @param gamertag  - this is the gamertag we wish to request data of
     * @return object
     */
    public function games( $gamertag = FALSE )
    {
        if ( !$gamertag )
            return FALSE;

        return $this->fetch_data(
            $gamertag,  // gamertag we wish to lookup
            'games'     // type of lookup
        );
    }
    //------------------------------------------------------------------


    /**
     * public function achievements()
     * this is used to fetch the achievement data from xboxapi.com
     *
     * @param gamertag  - this is the gamertag we wish to request data of
     * @param game_id   - this is the game id we wish to request data for
     * @return object
     */
    public function achievements ( $gamertag = FALSE, $game_id = FALSE )
    {
        if ( !$gamertag || !$game_id )
            return FALSE;

        return $this->fetch_data(
            $gamertag,          // gamertag we wish to lookup
            'achievements',     // type of lookup
            $game_id            // game id to lookup against
        );
    }
    //------------------------------------------------------------------


    /**
     * public function error()
     * this is used to return the error variable
     *
     * @return string
     */
    public function error()
    {
        return $this->config['error'];
    }
    //------------------------------------------------------------------


//=================================================================================
// :private
//=================================================================================

    /**
     * private fetch_data()
     * this is used to fetch the JSON data from XboxAPI.com
     */
    private function fetch_data( $gamertag = FALSE, $type = 'profile', $game_id = FALSE )
    {
        // have we exceeded the API limit?
        if ( !$this->limit_check() )
        {
            // set the error and return FALSE
            $this->config['error'] =  "API limit exceeded";

            // if were doing a debug, print out
            if ( $this->config['debug'] )
                print $this->config['error'] . PHP_EOL;

            return FALSE;
        }
        else
        {
            // do we have a gamertag?
            if ( !$gamertag )
            {
                $this->config['error'] = "no gamertag specified";

                // if were doing a debug, print out
                if ( $this->config['debug'] )
                    print $this->config['error'] . PHP_EOL;

                return FALSE;
            }

            // do we have a game id if we want achievements?
            if ( $type == 'achievements' && !$game_id )
            {
                $this->config['error'] = "no game id specified";

                // if were doing a debug, print out
                if ( $this->config['debug'] )
                    print $this->config['error'] . PHP_EOL;

                return FALSE;
            }

            // have we tried to get this data 5 times already? (configured by $gamertag_requests)
            if ( $this->config['gamertag_attempts'] >= $this->config['gamertag_requests'] )
            {
                // if were doing a debug, print out
                if ( $this->config['debug'] )
                    print $this->config['error'] . PHP_EOL;

                return FALSE;
            }

            // add 1 to the gamertag attempts
            $this->config['gamertag_attempts']++;

            // print the attempt number
            // if were doing a debug, print out
            if ( $this->config['debug'] )
                print "attempt " . $this->config['gamertag_attempts'] . " of " . $this->config['gamertag_requests'];

            // build up the url we wish to collect from
            switch ( $type )
            {
                case 'profile':
                    $url = "https://xboxapi.com/json/profile/" . strtolower( urlencode( $gamertag ) );
                    break;

                case 'games':
                    $url = "https://xboxapi.com/json/games/" . strtolower( urlencode( $gamertag ) );
                    break;

                case 'achievements':
                    $url = "https://xboxapi.com/json/achievements/" . $game_id . "/" . strtolower( urlencode( $gamertag ) );
                    break;

                default:
                    $url = "https://xboxapi.com/json/profile/" . strtolower( urlencode( $gamertag ) );
                    break;
            }

            // fetch the JSON data via CURL
            $ch = curl_init();
            $timeout = 600;
            curl_setopt($ch, CURLOPT_URL,               $url);
            curl_setopt($ch, CURLOPT_USERAGENT,         "XboxAPI Twitter Bot");
            curl_setopt($ch, CURLOPT_TIMEOUT,           $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,    $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,    TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,    FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,    FALSE);
            $data = curl_exec($ch);
            curl_close($ch);

            // do we have valid JSON?
            $data = json_decode( $data );
            if ( !$data )
            {
                // set and print the error
                $this->config['error'] = "invalid JSON data trying again...";

                // if were doing a debug, print out
                if ( $this->config['debug'] )
                    print $this->config['error'] . PHP_EOL;

                // lets try again shall we...
                $this->fetch_data( $gamertag, $type, $game_id );
            }

            // if so lets use it
            else
            {
                // was it a successful request?
                if ( !$data->Success )
                {
                    // set the error
                    $this->config['error'] =  $data->Error;

                    // if were doing a debug, print out
                    if ( $this->config['debug'] )
                        print $this->config['error'] . PHP_EOL;

                    // lets try again shall we...
                    $this->fetch_data( $gamertag, $type, $game_id );
                }

                // have we exceeded the API limit?
                elseif ( !$this->limit_check( $data->API_Limit ) )
                {
                    // set the error and return FALSE
                    $this->config['error'] =  "API limit exceeded";

                    // if were doing a debug, print out
                    if ( $this->config['debug'] )
                        print $this->config['error'] . PHP_EOL;

                    return FALSE;
                }

                // lets return the data
                else
                {
                    // return the data
                    return $data;
                }
            }
        }
    }
    //------------------------------------------------------------------


    /**
     * private limit_check()
     * this is used to check our API limit from XboxAPI.com
     *
     * @param input - the API limit text responce
     * @return bool
     */
    private function limit_check( $input = FALSE )
    {
        return TRUE;
        // if ( $input != FALSE )
        //     list( $this->config['API_Limit_current'], $this->config['API_Limit'] ) = explode( '/', $input );

        // if ( $this->config['API_Limit_current'] < $this->config['API_Limit'] )
        //     return TRUE;

        // return FALSE;
    }
    //------------------------------------------------------------------


}

// eof.
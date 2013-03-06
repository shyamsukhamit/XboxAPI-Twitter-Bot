<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ------------------------------------------------------------------------------
 *
 * XboxAPI_bot                                   (v1) | codename chockywockydodah
 *
 * ------------------------------------------------------------------------------
 *
 * Copyright (c) 2012, Alan Wynn
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * ------------------------------------------------------------------------------
 */

class Tweetie extends CI_Controller {

//=================================================================================
// :private vars
//=================================================================================

    private $units  = array(
        "year"   => 29030400, // seconds in a year   (12 months)
        "month"  => 2419200,  // seconds in a month  (4 weeks)
        "week"   => 604800,   // seconds in a week   (7 days)
        "day"    => 86400,    // seconds in a day    (24 hours)
        "hour"   => 3600,     // seconds in an hour  (60 minutes)
        "minute" => 60,       // seconds in a minute (60 seconds)
        "second" => 1         // 1 second
    );
    private $debug  = TRUE;
    private $tokens;


//=================================================================================
// :public functions
//=================================================================================

    /**
     * public construct
     */
    public function __construct()
    {
        // init parent
        parent::__construct();

        // loads
        $this->load->library( 'tweet_lib' );
        $this->load->model( 'data_model' );
    }
    //------------------------------------------------------------------


    /**
     * public remap
     */
    public function _remap($method, $params = array())
    {
        if (method_exists($this, $method))
        {
            // do the requested function
            return call_user_func_array(array($this, $method), $params);
        }
        $this->e404_notFound();
    }
    //------------------------------------------------------------------


    /**
     * public index()
     */
    public function index()
    {
        print "hello";
    }
    //------------------------------------------------------------------


    /**
     * public tweet()
     */
    public function mentions()
    {
        print '<pre>';

        $responce = $this->tweet_lib->mentions( $this->data_model->last_id() );
        if ( $responce['code'] !== 200 )
            die( print_r( $responce ) . '</pre>' );

        foreach ( array_reverse( json_decode( $responce['response'], TRUE ) ) as $mention )
        {
            // tweet id?
            $id = $mention['id_str'];

            // tweet text
            $tweet = strtolower( $mention['text'] );
            $tweet = $this->startsWith( $tweet, '@xboxapi_bot ' ) ? str_replace( '@xboxapi_bot ', NULL, $tweet ) : FALSE;

            if ( $tweet != FALSE )
            {
                // who sent the tweet?
                $sender = $mention['user']['screen_name'];

                // '/isonline'
                if (( $gamertag = ( $this->startsWith( $tweet, '/isonline' ) ? str_replace( '/isonline', NULL, $tweet ) : FALSE ) ))
                {
                    // build up the reply
                    // todo
                    $reply_tweet = "@{$sender} " . $this->get_status( trim( $gamertag ) );
                        print "sent - {$reply_tweet}" . PHP_EOL . PHP_EOL;

                    // send the responce
                    if ( $this->send_tweet( $reply_tweet, $id ) )
                    {
                        print "sent - {$reply_tweet}" . PHP_EOL;
                        $this->data_model->new_request( $mention['id_str'] );
                    }
                }
            }
            else
                $this->data_model->new_request( $mention['id_str'] );
        }

        print '</pre>';
    }
    //------------------------------------------------------------------


//=================================================================================
// :private functions
//=================================================================================

    /**
     * private e404_notFound()
     * this function is used when an invalid function is called...
     */
    private function e404_notFound()
    {
        print "Error 404 - Not Found!" . PHP_EOL;
    }
    //------------------------------------------------------------------


    /**
     * private send_tweet
     * this function is used to send the tweet for when the status is down
     *
     * @param tweet - this is the string we wish to tweet
     */
    private function send_tweet( $tweet = FALSE, $in_reply_to = FALSE )
    {
        // do we have a string?
        if ( !$tweet )
            $tweet = 'Current date/time is ' . date("F j, Y, g:i:s a") . "\r\n\r\n" . 'Users IP ' . $_SERVER['REMOTE_ADDR'];

        // post our tweet
        $send_tweet = $this->tweet_lib->new_tweet( $tweet, $in_reply_to );

        return $send_tweet;
    }
    //------------------------------------------------------------------


    /**
     * private startsWith
     */
    private function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
    //------------------------------------------------------------------


    /**
     * private endsWith
     */
    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
    //------------------------------------------------------------------


    /**
     * private get_status
     */
    private function get_status( $gamertag = FALSE )
    {
        if ( !$gamertag )
            return "invalid gamertag";

        // load the XboxAPI Scraper Lib
        $this->load->library('XboxAPI_Scraper', array(), 'XboxAPI_Scraper');

        // fetch the profile data
        $profile = $this->XboxAPI_Scraper->profile( $gamertag );

        if ( !$profile)
            return $this->XboxAPI_Scraper->error();

        // extract the online status
        $gamertag   = $profile->Player->Gamertag;
        $online     = $profile->Player->Status->Online ? 'Online' : 'Offline';
        $status     = $profile->Player->Status->Online_Status;

        // return the string
        if ( $profile->Player->Status->Cheater )
            return "Sorry but {$gamertag} is a cheater, and we don't like cheaters!";
        else
            return "{$gamertag} is {$online}\r\n\r\n{$status}";
    }
    //------------------------------------------------------------------


}

// eof.

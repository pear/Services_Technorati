<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Client library for Technorati's REST-based webservices
 *
 * PHP version 5
 *
 * @category   Services
 * @package    Services_Technorati2
 * @author     James Stewart <james@jystewart.net>
 * @copyright  2006 James Stewart
 * @license    http://www.gnu.org/copyleft/lesser.html  GNU LGPL
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Services_Technorati2
 */

/**
 * using PEAR error management
 */
require_once 'PEAR.php';

/**
 * uses HTTP to send requests
 */ 
require_once 'HTTP/Request.php';

/**
 * We have our own Exception class
 */
require_once 'Technorati2/Exception.php';

/**
 * Client for Technorati's REST-based webservices
 *
 * Technorati is a blog search engine with a number of tools to help
 * explore and utilise the blogosphere. The API provides enhanced
 * access to all the site's features, performing API calls and
 * returning SimpleXML objects of results.
 *
 * @todo        update once attention.xml query is stabilised
 * @category    Webservices
 * @package     Services_Technorati2
 * @author      James Stewart <james@jystewart.net>
 * @license     http://www.gnu.org/copyleft/lesser.html  GNU LGPL
 * @version     Release: @package_version@
 * @link        http://pear.php.net/package/Services_Technorati2
 */
class Services_Technorati2
{
    /**
     * URI of the REST API
     *
     * @access  protected
     * @var     string
     */
    protected $apiUrl = 'http://api.technorati.com';

    /**
     * User agent to send with requests
     * @access  public
     * @var  string
     */
    public $userAgent = 'Services_Technorati v2';

    /**
     * API Key
     *
     * @access  protected
     * @var     string
     */
    protected $apiKey = null;

    /**
     * Create our client
     *
     * @access     public
     * @param      string   apiKey
     * @param      object   cache
     */
    function __construct($apiKey, $cache = null)
    {
        $this->apiKey = $apiKey;
        $this->cache = $cache;
        if ($this->cache) {
            $this->cache->setOption('automaticSerialization', true);
        }
    }

    /**
     * Factory methods to create client. This is in place to prepare for
     * forwards compatibility with future versions of the API.
     *
     * @access     public
     * @param      string               apiKey
     * @param      object               cache
     * @param      float                apiVersion
     * @return     Services_Technorati  object
     */
    function factory($apiKey, $cache = null, $apiVersion = 1.0)
    {
        return new Services_Technorati2($apiKey, $cache);
    }

    /**
     * KeyInfo provides information on daily usage of an API key.
     * A Technorati API key is typically limited to 500 requests
     * per day, where a day is measured as 00:00-23:59 PST
     * This function does not cache, and this call does not use any
     * of the daily request allocation for the given key.
     *
     * @access      public
     * @return      array
     */
    function keyInfo() {
        return $this->sendRequest('keyinfo');
    }

    /**
     * This method handles the majority of the work for most of our queries.
     *
     * @access      private
     * @param       string  the query type we're performing
     * @param       array   an array for our main query parameter (key and value)
     * @param       array   the valid options for this query
     * @param       array   the user's chosen options
     * @return      array
     */
    protected function general($query, $chief_param, $valid_options, $options = null)
    {
        /* Check for invalid options */
        if (is_array($options)) {
            $options = $this->checkOptions($options, $valid_options);
            if (PEAR::isError($options)) {
                return $options;
            }
            $options = array_merge($options, $chief_param);
        } else {
            $options = $chief_param;
        }

        /* Build cache URI */
        $filename = $query . '.' . str_replace(' ', '_', $query);
        if (is_array($options)) {
            $filename = $filename . implode('_', $options);
        }

        /* Check if cached */
        if (isset($this->cache) and 
            $cache = $this->cache->get($filename, 'services_technorati')) {
            return $cache;
        }

        /* Not cached */
        $value = $this->sendRequest($query, $options);

        /* Save the data in the cache if appropriate. We use the filename as the ID */
        if (! PEAR::isError($value) and !empty($this->cache)) {
            $this->cache->save($value, $filename, 'services_technorati');
        }

        return $value;
    }

    /**
     * The BlogPostTags query returns the top tags for a given blog URL.
     *
     * @access      public
     * @param       string  the url to query for
     * @param       array   options
     * @return      array
     */
    function blogPostTags($url, $options = array())
    {
        return $this->general('blogposttags', array('url' => $url), 
            array('limit'), $options);
    }

    /**
     * Cosmos lets you see what blogs are linking to a given URL
     *
     * @access      public
     * @param       string  url
     * @param       array   options
     * @return      array
     */
    function cosmos($url, $options = null)
    {
        $valid_options = array('type', 'limit', 'start', 'current', 'claim',
                'highlight');
        return $this->general('cosmos', array('url' => $url), 
            $valid_options, $options);
    }

    /**
     * The search lets you see what blogs contain a given search string
     *
     * @access      public
     * @param       string  query
     * @param       array   options
     * @return      array
     */
    function search($query, $options = null)
    {
        $valid_options = array('start','limit','claim');
        return $this->general('search', array('query' => $query), 
            $valid_options, $options);
    }
    
    /**
     *  The outbound query lets you see what blogs are linked to from a given
     *  blog, including their associated info.
     *
     * @access      public
     * @param       string  url
     * @param       array   options
     * @return      array
     */
    function outbound($url, $options = null)
    {
        /* Check for invalid options */

        $valid_options = array('start');
        return $this->general('outbound', array('url' => $url), 
            $valid_options, $options);
    }

    /**
     *  The tag query allows you to get a list of posts with the given tag 
     *  associated with it. This API query is currently experimental.
     * 
     * @access      public
     * @param       string  url
     * @param       array   options
     * @return      array
     */
    function tag($tag, $options = null)
    {
        /* Check for invalid options */

        $valid_options = array('limit', 'start', 'format',
            'excerptsize', 'topexcerptsize');
        return $this->general('tag', array('tag' => $tag), 
            $valid_options, $options);
    }

    /**
      * TopTags lets you retrieve a list of the most popular post tags
      * tracked by Technorati
      *
      * @access      public
      * @param       array   options
      * @return      array
      */
     function topTags($options = null)
     {
         $valid_options = array('limit', 'start');
         return $this->general('toptags', array(), 
             $valid_options, $options);
     }

    /**
     * The getinfo query tells you things that Technorati knows about a user
     *
     * @access      public
     * @param       string  username
     * @return      array
     */
    function getInfo($username)
    {
        $options = array('username' => urlencode($username));
        return $this->general('getinfo', $options,
            array(), false);
    }

    /**
     *  The bloginfo query provides info on what blog, if any, is 
     *  associated with a given URL
     *
     * @access      public
     * @param       string  url
     * @return      array
     */
    function blogInfo($url)
    {
        $options = array('url' => $url);
        return $this->general('bloginfo', $options,
            array(), false);
    }

    /**
     *  This lets users retrieve their Attention.XML
     *  This API query is currently experimental.
     *
     * @access      public
     * @param       string  username
     * @param       string  password
     * @return      array
     */
    function attention($user, $password)
    {
        $key_options = array('username' => $user);
        $options = array('password' => md5($password));
        return $this->general('attention', $key_options, array('password'), $options);
    }

    /**
     *  This posts a new Attention.XML file to the Technorati system.
     *  This API query is currently experimental. This is the one call that
     *  doesn't use sendRequest, because it needs to POST a file.
     *
     * @access      public
     * @param       string  username
     * @param       string  password
     * @param       string  filename
     * @return      boolean
     */
    function attentionPost($user, $password, $file)
    {
        $options = array('username' => $user, 'password' => md5($password));

        /* Build cache URI */

        $filename = 'attention.{$user}';

        /* We don't cache this query */
        $request =& new HTTP_Request($this->apiUrl . 'attention');
        $request->addHeader('User-Agent', $this->userAgent);
        $request->setMethod(HTTP_REQUEST_METHOD_POST);
        $addfile = $request->addFile('attention.xml', $file);

        if (PEAR::isError($addfile)) {
            return $addfile;
        }

        $request->addPostData('username', $user);
        $request->addPostData('password', md5($password));

        $request->sendRequest();

        $value = $this->processResponse($request);

        if (PEAR::isError($value)) {
            return $value;
        }

        /* Store in cache */

        $this->cache->save($value, 'services_technorati');

        return $value;
    }

    /**
     *  Send everything out
     *
     * @access      protected
     * @param       string  type of query
     * @param       array   parameters
     * @return      SimpleXML|PEAR_ERROR
     */
    protected function sendRequest($query, $options = null)
    {
        /* Do all the nitty gritty HTTP stuff. Except attentionPost */
        $url = sprintf('%s/%s?key=%s', $this->apiUrl, $query, $this->apiKey);

        $request =& new HTTP_Request($this->apiUrl . 'attention');
        $request->addHeader('User-Agent', $this->userAgent);
        $request->setURL($url);

        if (is_array($options)) {
            foreach ($options as $key => $value) {
                $request->addQueryString($key, $value);
            }
        }

        $request->sendRequest();

        return $this->processResponse($request);
    }

    /**
     * This function takes the request sent by either attentionPost or
     * sendRequest and processes it, returning an unserialized version
     *
     * @access      protected
     * @param       HTTP_Request    $request    the request object
     * @return      array|PEAR_Error
     */
    protected function processResponse($request)
    {
        if ($request->getResponseCode() != 200) {
            throw new Services_Technorati2_Exception('Invalid Response Code', 
              $request->getResponseCode());
        }

        $result = $request->getResponseBody();

        if (PEAR::isError($result)) {
            throw new Services_Technorati2_Exception('Technorati Response Error',
                $result->getError());
        }
        
        $xml = simplexml_load_string($result);
        if ($xml->document && $xml->document->result && $xml->document->result->error) {
            throw new Services_Technorati2_Exception('Technorati Response Error',
                (string)$xml->document->result->error);
        }

        return $xml;
    }

    /**
     *  Filter options for those acceptable for this query. Raise
     *  warnings if others have been passed.
     *
     * @access      protected
     * @param       array       specified options
     * @param       array       acceptable options
     */
    protected function checkOptions($current, $accepted) 
    {
        $accepted_options = array();
        foreach ($current as $option => $value) {
            if (in_array($option, $accepted)) {
                $accepted_options[$option] = $value;
            } else {
                throw new Services_Technorati2_Exception($option .' is not an option for this query');
            }
        }
        return $accepted_options;
    } 
}

?>

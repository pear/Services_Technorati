<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Client library for Technorati's REST-based webservices
 *
 * PHP versions 4 and 5
 *
 * @category   Services
 * @package    Services_Technorati
 * @author     James Stewart <james@jystewart.net>
 * @copyright  2004-2005 James Stewart
 * @license    http://www.gnu.org/copyleft/lesser.html  GNU LGPL
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Services_Technorati
 */

/**
 * using PEAR error management
 */
require_once 'PEAR.php';

/**
 * using XML_Serializer for processing
 */
require_once 'XML/Unserializer.php';

/**
 * uses HTTP to send requests
 */ 
require_once 'HTTP/Request.php';
 
/**
 * Client for Technorati's REST-based webservices
 *
 * Technorati is a blog search engine with a number of tools to help
 * explore and utilise the blogosphere. The API provides enhanced
 * access to all the site's features
 *
 * @todo        update once attention.xml query is stabilised
 * @category    Webservices
 * @package     Services_Technorati
 * @author      James Stewart <james@jystewart.net>
 * @license     http://www.gnu.org/copyleft/lesser.html  GNU LGPL
 * @version     Release: @package_version@
 * @link        http://pear.php.net/package/Services_Technorati
 */
class Services_Technorati
{
    /**
     * URI of the REST API
     *
     * @access  private
     * @var     string
     */
    var $_apiUrl = 'http://api.technorati.com';

    /**
     * API Key
     *
     * @access  private
     * @var     string
     */
    var $_apiKey = null;

    /**
     * XML_Unserializer, used to parse the XML
     *
     * @access  private
     * @var     object  XML_Unserializer
     */
    var $_xmlUs = null;

    /**
     * Create our client
     *
     * @access     public
     * @param      string   apiKey
     * @param      object   cache
     */
    function Services_Technorati($apiKey, $cache = null)
    {
        $this->_apiKey = $apiKey;
        $this->_cache = $cache;
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
        return new Services_Technorati($apiKey, $cache);
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
        return $this->_sendRequest('keyinfo');
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
    function _general($query, $chief_param, $valid_options, $options = null)
    {
        /* Check for invalid options */

        if (is_array($options)) {
            $options = $this->_checkOptions($options, $valid_options);
            if (PEAR::isError($options)) {
                return $options;
            }
            $options = array_merge($options, $chief_param);
        } else {
            $options = $chief_param;
        }

        /* Build cache URI */

        $filename = $query . "." . str_replace(" ", "_", $query);
        if (is_array($options)) {
            $filename = $filename . implode("_", $options);
        }

        /* Check if cached */

        if (isset($this->_cache) and $cache = $this->_cache->get($filename)) {
            return $cache;
        }

        /* Not cached */

        $value = $this->_sendRequest($query, $options);

        if (! PEAR::isError($value) and !empty($this->_cache)) {
            $this->_cache->save($value);
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
        return $this->_general("blogposttags", array("url" => $url), 
            array("limit"), $options);
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
        return $this->_general("cosmos", array("url" => $url), 
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
        return $this->_general("search", array("query" => $query), 
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
        return $this->_general("outbound", array("url" => $url), 
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
        return $this->_general("tag", array("tag" => $tag), 
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
         return $this->_general("topTags", array(), 
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
        return $this->_general("getinfo", $options,
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
        return $this->_general("bloginfo", $options,
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
        return $this->_general("attention", $key_options,
            array("password"), $options);
    }

    /**
     *  This posts a new Attention.XML file to the Technorati system.
     *  This API query is currently experimental. This is the one call that
     *  doesn't use _sendRequest, because it needs to POST a file.
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

        $filename = "attention.{$user}";

        /* We don't cache this query */

        $request =& new HTTP_Request($this->_apiUrl . "attention");
        $request->setMethod(HTTP_REQUEST_METHOD_POST);
        $addfile = $request->addFile("attention.xml", $file);

        if (PEAR::isError($addfile)) {
            return $addfile;
        }

        $request->addPostData("username", $user);
        $request->addPostData("password", md5($password));
        $request->addHeader('User-Agent', 'Services_Technorati');

        $request->sendRequest();

        $value = $this->_processResponse($request);

        if (PEAR::isError($value)) {
            return $value;
        }

        /* Store in cache */

        $this->_cache->save($value);

        return $value;
    }

    /**
     *  Send everything out
     *
     * @access      private
     * @param       string  type of query
     * @param       array   parameters
     * @return      array|PEAR_ERROR
     */
    function _sendRequest($query, $options = null)
    {
        /* Do all the nitty gritty HTTP stuff. Except attentionPost */
        $url = sprintf("%s/%s?key=%s", $this->_apiUrl, $query, $this->_apiKey);

        $request =& new HTTP_Request($url);
        
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                $request->addQueryString($key, $value);
            }
        }
        
        $request->addHeader('User-Agent', 'Services_Technorati');

        $request->sendRequest();

        return $this->_processResponse($request);
    }

    /**
     * This function takes the request sent by either attentionPost or
     * _sendRequest and processes it, returning an unserialized version
     *
     * @access      private
     * @param       HTTP_Request_Response
     * @return      array|PEAR_Error
     */
    function _processResponse(&$request)
    {
        if ($request->getResponseCode() != 200) {
            return PEAR::raiseError('Invalid Response Code', 
                $request->getResponseCode());
        }

        $result = $request->getResponseBody();

        if (!is_object($this->_xmlUs)) {
            $this->_xmlUs =& new XML_Unserializer();
            $this->_xmlUs->setOption('parseAttributes', true);
        }

        $result = $this->_xmlUs->unserialize($result);

        if (PEAR::isError($result)) {
            return $result;
        }
        if (!empty($result['document']['result']['error'])) {
            return PEAR::raiseError("Technorati Response Error",
                $value['document']['result']['error']);
        }
        $unserialized = $this->_xmlUs->getUnserializedData();
        
        if (!empty($unserialized['document']['result']['error'])) {
            return PEAR::raiseError("Technorati Response Error",
                $unserialized['document']['result']['error']);
        }
        return $unserialized;
    }

    /**
     *  Filter options for those acceptable for this query. Raise
     *  warnings if others have been passed.
     *
     * @access      private
     * @param       array       specified options
     * @param       array       acceptable options
     */
    function _checkOptions($current, $accepted) 
    {
        foreach ($current as $option => $value) {
            if (in_array($option, $accepted)) {
                $accepted_options[$option] = $value;
            } else {
                PEAR::raiseError("$option is not an option for this query", null,
                    PEAR_ERROR_TRIGGER, E_USER_WARNING);
            }
        }
        return $accepted_options;
    } 
}

?>

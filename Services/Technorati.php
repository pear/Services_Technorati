<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Client library for Technorati's REST-based webservices
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.ÊÊIf you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Services
 * @package    Services_Technorati
 * @author     James Stewart <james@jystewart.net>
 * @copyright  2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt PHP License 3.0
 * @version    CVS: $id: Technorati.php,v @version@ 2005/04/16 16:49:00 jystewart Exp $
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
 * uses Cache_Lite for caching
 */
 require_once 'Cache/Lite.php';
 
/**
 * Client for Technorati's REST-based webservices
 *
 * Technorati is a blog search engine with a number of tools to help
 * explore and utilise the blogosphere. The API provides enhanced
 * access to all the site's features
 *
 * @author      James Sytewart <james@jystewart.net>
 * @package     Services_Technorati
 * @version     @version@
 * @todo        update once attention.xml query is stabilised
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
     * Hours To Cache
     *
     * @access  private
     * @var     int
     */
    var $_hoursToCache;

    /**
     * Path To Cache
     *
     * @access  private
     * @var     string
     */
    var $_pathToCache;

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
     * @param      int      hoursToCache
     * @param      string   pathToCache
     */

    function Services_Technorati($apiKey, $hoursToCache = null, $pathToCache = null)
    {
        $this->_apiKey = $apiKey;

        if (! empty($hoursToCache)) {
            $this->_hoursToCache = $hoursToCache;
            $this->_pathToCache = $pathToCache;
            $cache_options = array(
                'cacheDir' => $pathToCache,
                'lifeTime' => (3600 * $hoursToCache)
            );
            $this->_cache = new Cache_Lite($cache_options);
        }
    }

    /**
     * Factory methods to create client
     *
     * @access     public
     * @param      string               apiKey
     * @param      int                  hoursToCache
     * @param      string               pathToCache
     * @return     Services_Technorati  object
     */
    function factory($apiKey, $hoursToCache = '', $pathToCache = '')
    {
        return new Services_Technorati($apiKey, $hoursToCache, $pathToCache);
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
     * Cosmos lets you see what blogs are linking to a given URL
     *
     * @access      public
     * @param       string  url
     * @param       array   options
     * @return      array
     */
    function cosmos($url, $options)
    {
        /* Check for invalid options */

        $valid_options = array('type', 'limit', 'start', 'current', 'claim',
                'highlight');
        if (is_array($options)) {
            $options = $this->_checkOptions($options, $valid_options);
            if (PEAR::isError($options)) {
                return $options;
            }
            $options['url'] = urlencode($url);
        } else {
            $options = array('url' => urlencode($url));
        }

        /* Build cache URI */

        $filename = $url . "cosmos" . join("-", $options);

        /* Check if cached */

        if (!empty($this->_cache) && $cache = $this->_cache->get($filename)) {
            return $cache;
        }

        /* Not cached */

        $value = $this->_sendRequest('cosmos', $options);

        if (! PEAR::isError($value) && !empty($this->_cache)) {
            $this->_cache->save($value);
        }

        return $value;
    }

    /**
     * The search lets you see what blogs contain a given search string
     *
     * @access      public
     * @param       string  query
     * @param       array   options
     * @return      array
     */
    function search($query, $options = array())
    {
        /* Check for invalid options */

        $valid_options = array('start','limit','claim');
        if (is_array($options)) {
            $options = $this->_checkOptions($options, $valid_options);
            if (PEAR::isError($options)) {
                return $options;
            }
            $options['query'] = urlencode($query);
        } else {
            $options = array('query' => urlencode($query));
        }

        /* Build cache URI */

        $filename = "search." . implode("_", $query) . implode("_",$options);

        /* Check if cached */

        if ($cache = $this->_cache->get($filename)) {
            return $cache;
        }

        /* Not cached */

        $value = $this->_sendRequest('search', $options);

        if (! PEAR::isError($value) && !empty($this->_cache)) {
            $this->_cache->save($value);
        }

        return $value;
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
        
        /* Build cache URI */

        $filename = "getinfo.$username";

        /* Check if cached */

        if ($cache = $this->_cache->get($filename)) {
            return $cache;
        }

        /* Not cached */

        $value = $this->_sendRequest('getinfo', $options);

        if (! PEAR::isError($value) && !empty($this->_cache)) {
            $this->_cache->save($value);
        }

        return $value;
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
    function outbound($url, $options = array())
    {
        /* Check for invalid options */

        $valid_options = array('start');
        if (is_array($options)) {
            $options = $this->_checkOptions($options, $valid_options);
            if (PEAR::isError($options)) {
                return $options;
            }
            $options['url'] = urlencode($url);
        } else {
            $options = array('url' => urlencode($url));
        }

        /* Build cache URI */

        $filename = $url . "outbound." . join("_",$options);

        /* Check if cached */

        if ($cache = $this->_cache->get($filename)) {
            return $cache;
        }

        /* Not cached */

        $value = $this->_sendRequest('outbound', $options);

        if (! PEAR::isError($value) && !empty($this->_cache)) {
            $this->_cache->save($value);
        }

        return $value;
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
        $options['url'] = urlencode($url);

        /* Build cache URI */

        $filename = $url . "bloginfo.";

        /* Check if cached */

        if ($cache = $this->_cache->get($filename)) {
            return $cache;
        }

        /* Not cached */

        $value = $this->_sendRequest('bloginfo', $options);

        if (! PEAR::isError($value) && !empty($this->_cache)) {
            $this->_cache->save($value);
        }

        return $value;
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
    function tag($tag, $options = array())
    {
        /* Check for invalid options */

        $valid_options = array('limit', 'start', 'format',
            'excerptsize', 'topexcerptsize');
        if (is_array($options)) {
            $options = $this->_checkOptions($options, $valid_options);
            if (PEAR::isError($options)) {
                return $options;
            }
            $options['tag'] = $tag;
        } else {
            $options = array('tag' => $tag);
        }

        /* Build cache URI */

        $filename = "tag.{$tag}";

        /* Check if cached */

        if ($cache = $this->_cache->get($filename)) {
            return $cache;
        }

        /* Not cached */

        $value = $this->_sendRequest('tag', $options);

        if (! PEAR::isError($value) && !empty($this->_cache)) {
            $this->_cache->save($value);
        }

        return $value;
    }

    /**
     * TopTags lets you retrieve a list of the most popular post tagd
     * tracked by Technorati
     *
     * @access      public
     * @param       array   options
     * @return      array
     */
     function topTags($options)
     {
        $valid_options = array('limit', 'start');
        if (is_array($options)) {
            $options = $this->_checkOptions($options, $valid_options);
            if (PEAR::isError($options)) {
                return $options;
            }
            $options['url'] = urlencode($url);
        } else {
            return PEAR::raiseError('You must supply options as an array');
        }

        /* Build cache URI */

        $filename = $url."cosmos" . join("-", $options);

        /* Check if cached */

        if ($cache = $this->_cache->get($filename)) {
            return $cache;
        }

        /* Not cached */

        $value = $this->_sendRequest('toptags', $options);

        if (! PEAR::isError($value) && !empty($this->_cache)) {
            $this->_cache->save($value);
        }

        return $value;
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

        $options = array('username' => $user, 'password' => md5($password));

        /* Build cache URI */

        $filename = "attention.{$user}";

        /* Check if cached */

        if ($cache = $this->_cache->get($filename)) {
            return $cache;
        }

        /* Not cached */

        $value = $this->_sendRequest('attention', $options);

        if (! PEAR::isError($value) && !empty($this->_cache)) {
            $this->_cache->save($value);
        }

        return $value;  
    }

    /**
     *  This posts a new Attention.XML file to the Technorati system.
     *  This API query is currently experimental. This is the one call that
     *  doesn't use _sendRequest, because it needs to POST a file.
     *
     * @access      public
     * @param       string  username
     * @param       string  password
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
        
        if ($request->getResponseCode() != 200) {
            return PEAR::raiseError('Invalid Response Code', 
                    $request->getResponseCode());
        }

        $result = $request->getResponseBody();

        if (!is_object($this->_xmlUs)) {
            $this->_xmlUs =& new XML_Unserializer();
            $this->_xmlUs->setOption('parseAttributes',true);
        }

        $result = $this->_xmlUs->unserialize($result);

        if (PEAR::isError($result)) {
            return $result;
        }
        if (!empty($result['document']['result']['error'])) {
            return PEAR::raiseError("Technorati Response Error",
                $value['document']['result']['error']);
        }

        $value = $this->_xmlUs->getUnserializedData();

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
    function _sendRequest($query, $options = array())
    {
        /* Do all the nitty gritty HTTP stuff. Except attentionPost */
        $url = sprintf("%s/%s?key=%s", $this->_apiUrl, $query,$this->_apiKey);

        foreach ($options as $key => $value) {
            $url = $url . '&' . $key . '=' . urlencode($value);
        }

        $request =& new HTTP_Request($url);
        $request->addHeader('User-Agent', 'Services_Technorati');

        $request->sendRequest();
        if ($request->getResponseCode() != 200) {
            return PEAR::raiseError('Invalid Response Code', 
                $request->getResponseCode());
        }

        $result = $request->getResponseBody();

        if (! is_object($this->_xmlUs)) {
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
                $value['document']['result']['error']);
        }
        return $unserialized;
    }

    /**
     * raise errors if options are specified that aren't allowed
     *
     * @access      private
     * @param       array       specified options
     * @param       array       acceptable options
     */
    function _checkOptions($current, $accepted) 
    {
        foreach ($current as $option => $value) {
            if (in_array($option,$accepted)) {
                $accepted_options[$option] = $value;
            } else {
                PEAR::raiseError("Invalid option passed to Query", $option);
            }
        }
    } 
}

?>

<?php

require_once 'Services/Technorati2.php';
require_once 'PHPUnit.php';

class Services_Technorati2_TestCase extends PHPUnit_TestCase
{
    // contains the object handle of our Technorati class.
    public $tapi;
    
    // contains our API key
    public static $key;
    
    // /* constructor for test suite */
    // function __construct($name) {
    //     $this->PHPUnit_TestCase($name);
    // }
    
    function setUp() {
        $this->tapi =& Services_Technorati2::factory(self::$key, null, 1.0);
    }
    
    function tearDown() {
        unset($this->tapi);
    }
    
    /*
     * Test that the factory brings the same result as going directly to the
     * constructor. This will only work while we're going with the one API
     * version
     */
    function testFactory() {
        $testTapi = new Services_Technorati2(self::$key, null, 1.0);
        $this->assertTrue($this->tapi == $testTapi);
    }
    
    /* Test that the keyinfo query returns the two numeric values we need */
    function testKeyInfo() {
        $result = $this->tapi->keyInfo();
        $this->assertTrue(is_numeric((int)$result->document->result->apiqueries));
        $this->assertTrue(is_numeric((int)$result->document->result->maxqueries));
    }
    
    /* Test that the result of a cosmos query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a URL.
     */
    function testCosmos() {
        $result = $this->tapi->cosmos('http://www.example.com');
        $this->assertTrue(is_object($result));
        $this->assertEquals('http://www.example.com', (string)$result->document->result->url);
    }
    
    /* Test that the result of a search query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single search term.
     */    
    function testSearch() {
        $result = $this->tapi->search('example');
        $this->assertTrue(is_object($result));
        $this->assertEquals('example', (string)$result->document->result->query);
    }
    
    /* Test that the result of a getInfo query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single username.
     */        
    function testGetInfo() {
        $result = $this->tapi->getInfo('jystewart');
        $this->assertTrue($result instanceof SimpleXMLElement);
        $this->assertEquals('jystewart', (string)$result->document->result->username);
    }
    
    /* Test that the result of an outbound query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single URL.
         
    function testOutbound() {
        $result = $this->tapi->outbound('http://www.example.com');
        $this->assertTrue(($result instanceof SimpleXMLElement && 
            (string)$result->document->result->url == 'http://www.example.com') or
            PEAR::isError($result));
    }
    
    /* Test that the result of a blogInfo query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single URL.
     */    
    function testBlogInfo() {
        $result = $this->tapi->blogInfo('http://www.example.com');
        $this->assertTrue($result instanceof SimpleXMLElement);
        $this->assertEquals('http://www.example.com', (string)$result->document->result->url);
    }
    
    /* Test that the result of a tag query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single search term.
     */
    function testTag() {
        $result = $this->tapi->tag('example');
        $this->assertTrue($result instanceof SimpleXMLElement);
        $this->assertEquals('example', (string)$result->document->result->query);
    }
    
    /* Test that the result of a TopTags query is either a relevant PEAR error or an
     * array populated with at least a numeric value for the limit on number of results.
     */        
    function testTopTags() {
        $result = $this->tapi->topTags();
        $this->assertTrue($result instanceof SimpleXMLElement);
        $this->assertTrue(is_numeric((int)$result->document->result->limit));
    }
}

$suite = new PHPUnit_TestSuite('Services_Technorati2_TestCase');
$result = PHPUnit::run($suite, '123');
echo $result->toString();

?>
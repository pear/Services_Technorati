<?php

require '../Services/Technorati2.php';
require 'PHPUnit.php';

class Services_Technorati_Test extends PHPUnit_Testcase
{
    // contains the object handle of our Technorati class.
    var $tapi;
    
    // contains our API key
    var $key = "492a5486316000b6c9797e05cd64d846";
    
    /* constructor for test suite */
    function Services_Technorati_Test($name) {
        $this->PHPUnit_TestCase($name);
    }
    
    function setUp() {
        $this->tapi =& Services_Technorati::factory($this->key, null, 1.0);
    }
    
    function tearDown() {
        unset($this->tapi);
    }
    
    /* Test that the factory brings the same result as going directly to the
     * constructor. This will only work while we're going with the one API
     * version
     */
    function testFactory() {
        $testTapi = new Services_Technorati($this->key, null, 1.0);
        $this->assertTrue($this->tapi == $testTapi);
    }
    
    /* Test that the keyinfo query returns the two numeric values we need */
    function testKeyInfo() {
        $array = $this->tapi->keyInfo();
        $this->assertTrue(is_numeric($array['document']['result']['apiqueries'])
            && is_numeric($array['document']['result']['maxqueries']));
    }
    
    /* Test that the result of a cosmos query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a URL.
     */
    function testCosmos() {
        $result = $this->tapi->cosmos('http://www.example.com');
        $this->assertTrue((is_array($result) && 
            $result['document']['result']['url'] == 'http://www.example.com') or
            (PEAR::isError($result) && $result->message == 'Technorati Response Error'));
    }

    /* Test that the result of a search query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single search term.
     */    
    function testSearch() {
        $result = $this->tapi->search('example');
        $this->assertTrue((is_array($result) && 
            $result['document']['result']['query'] == 'example') or
            (PEAR::isError($result) && $result->message == 'Technorati Response Error'));
    }

    /* Test that the result of a getInfo query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single username.
     */        
    function testGetInfo() {
        $result = $this->tapi->getInfo('jystewart');
        $this->assertTrue((is_array($result) && 
            $result['document']['result']['username'] == 'jystewart') or
            (PEAR::isError($result) && $result->message == 'Technorati Response Error'));
    }

    /* Test that the result of an outbound query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single URL.
     */    
    function testOutbound() {
        $result = $this->tapi->outbound('http://www.example.com');
        $this->assertTrue((is_array($result) && 
            $result['document']['result']['url'] == 'http://www.example.com') or
            (PEAR::isError($result) && $result->message == 'Technorati Response Error'));

    }

    /* Test that the result of a blogInfo query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single URL.
     */    
    function testBlogInfo() {
        $result = $this->tapi->blogInfo('http://www.example.com');
        $this->assertTrue((is_array($result) && 
            $result['document']['result']['url'] == 'http://www.example.com') or
            (PEAR::isError($result) && $result->message == 'Technorati Response Error'));
    }

    /* Test that the result of a tag query is either a relevant PEAR error or an
     * array populated with at least the query metadata. The only parameter
     * passed is a single search term.
     */    
    function testTag() {
        $result = $this->tapi->tag('example');
        $this->assertTrue((is_array($result) && 
            $result['document']['result']['query'] == 'example') or
            (PEAR::isError($result) && $result->message == 'Technorati Response Error'));
    }

    /* Test that the result of a TopTags query is either a relevant PEAR error or an
     * array populated with at least a numeric value for the limit on number of results.
     */        
    function testTopTags() {
        $result = $this->tapi->topTags();
        $this->assertTrue((is_array($result) && 
            is_numeric($result['document']['result']['limit'])) or
            (PEAR::isError($result) && $result->message == 'Technorati Response Error'));
    }
    
    /* Send a query that doesn't exist and test for error */
    function test_sendRequest_404() {
        $this->assertTrue(PEAR::isError($this->tapi->_sendRequest("example")));
    }
    
    /* The ideal here would be to validate the return value against
     * a DTD, but for now we'll just test that a fairly standard query
     * returns an array 
     */
    function test_sendRequest_success() {
        $result = $this->tapi->_sendRequest('cosmos', array('url' => 'http://www.example.com'));
        $this->assertTrue((is_array($result) && 
            $result['document']['result']['url'] == 'http://www.example.com') or
            (PEAR::isError($result) && $result->message == 'Technorati Response Error'));
    }
    
    function test_checkOptions_extraoptions() {
        $current = array(
            'value1' => 1,
            'value2' => 1,
            'value3' => 1,
            'valuea' => 1,
            'valueb' => 1);
        $allowed = array('value1','value3','valueb');
        $this->assertTrue(
            array_keys($this->tapi->_checkOptions($current, $allowed)) == $allowed);
    }

    function test_checkOptions_success() {
        $current = array(
            'value1' => 1,
            'value2' => 1,
            'value3' => 1,
            'valuea' => 1,
            'valueb' => 1);
        $allowed = array('value1','value2','value3','valuea','valueb');
        $this->assertTrue(
            array_keys($this->tapi->_checkOptions($current, $allowed)) == $allowed);
    }
       
}

$suite = new PHPUnit_TestSuite("Webservices_Technorati_TestCase");
$result = PHPUnit::run($suite, '123');
echo $result->toString();
?>
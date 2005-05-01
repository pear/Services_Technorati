<?php

require_once '../Services/Technorati.php';
require_once '/usr/lib/php/PHPUnit.php';

class Webservices_Technorati_TestCase extends PHPUnit_Testcase
{
	// contains the object handle of our Technorati class.
	var $tapi;
	
	// contains our API key
	var $key = '492a5496316000b6c9797e05cd64d846';
	
    /* constructor for test suite */
    function Webservices_Technorati_Testcase($name) {
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
    
    //function testCosmos() {
    //}
    
    //function testSearch() {
    //}
    
    //function testGetInfo() {
    //}
    
    //function testOutbound() {
    //}
    
    //function testBlogInfo() {
    //}
    
    //function testTag() {
    //}
    
    //function testTopTags() {
    //}
    
    /* Send a query that doesn't exist and test for error */
    function test_sendRequest_404() {
    	$this->assertTrue(PEAR::isError($this->tapi->_sendRequest("example")));
    }
    
    /* The ideal here would be to validate the return value against
     * a DTD, but for now we'll just test that a fairly standard query
     * returns an array 
     */
    function test_sendRequest_success() {
        $this->assertTrue(is_array($this->tapi->_sendRequest('cosmos',
            array('url' => 'http://www.example.com'))));
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
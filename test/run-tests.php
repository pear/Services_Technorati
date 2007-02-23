<?php

/**
 * Unit tests for Services_Technorati. Adapted from XML_Serializer's run-tests 
 * file
 *
 * @package    Services_Technorati
 * @subpackage Tests
 */

require_once 'PHPUnit.php';
require_once 'Services/Technorati.php';

$testcases = array('Services_Technorati_Test');

$suite =& new PHPUnit_TestSuite();

foreach ($testcases as $testcase) {
    include_once $testcase . '.php';
    $methods = preg_grep('/^test/i', get_class_methods($testcase));
    foreach ($methods as $method) {
        $suite->addTest(new $testcase($method));
    }
}

# You can get a key at http://www.technorati.com/developers/apikey.html
$api_key = "<enter here>";
Services_Technorati_TestCase::$key = $api_key;

$result = PHPUnit::run($suite);

echo $result->toString();

?>
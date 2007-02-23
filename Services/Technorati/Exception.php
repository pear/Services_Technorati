<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Client library for Technorati's REST-based webservices
 *
 * PHP version 5
 *
 * @category   Services
 * @package    Services_Technorati
 * @author     James Stewart <james@jystewart.net>
 * @copyright  2006 James Stewart
 * @license    http://www.gnu.org/copyleft/lesser.html  GNU LGPL
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Services_Technorati
 */

/**
 * We are extending PEAR_Exception
 */
require_once 'PEAR/Exception.php';

/**
 * Services_Technorati_Exception is a simple extension of PEAR_Exception, existing
 * to help with identification of the source of exceptions.
 *
 * @author  James Stewart <james@jystewart.net>
 * @version Release: @package_version@
 * @package Services_Technorati
 */
class Services_Technorati_Exception extends PEAR_Exception
{
}

?>
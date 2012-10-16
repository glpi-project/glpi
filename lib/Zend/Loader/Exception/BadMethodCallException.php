<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Loader
 */

namespace Zend\Loader\Exception;

require_once __DIR__ . '/ExceptionInterface.php';

/**
 * @category   Zend
 * @package    Zend_Loader
 * @subpackage Exception
 */
class BadMethodCallException extends \BadMethodCallException implements
    ExceptionInterface
{
}

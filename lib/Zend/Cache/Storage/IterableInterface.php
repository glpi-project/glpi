<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Cache
 */

namespace Zend\Cache\Storage;

use IteratorAggregate;

/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 *
 * @method IteratorInterface getIterator() Get the storage iterator
 */
interface IterableInterface extends IteratorAggregate
{
}

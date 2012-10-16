<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Stdlib
 */

namespace Zend\Stdlib;

use ArrayAccess;
use Countable;
use Serializable;
use Traversable;

/*
 * Basically, an ArrayObject. You could simply define something like:
 *     class QueryParams extends ArrayObject implements Parameters {}
 * and have 90% of the functionality
 */
interface ParametersInterface extends ArrayAccess, Countable, Serializable, Traversable
{
    public function __construct(array $values = null);

    /* Allow deserialization from standard array */
    public function fromArray(array $values);

    /* Allow deserialization from raw body; e.g., for PUT requests */
    public function fromString($string);

    /* Allow serialization back to standard array */
    public function toArray();

    /* Allow serialization to query format; e.g., for PUT or POST requests */
    public function toString();

    public function get($name, $default = null);

    public function set($name, $value);
}

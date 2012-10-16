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

use ArrayObject;
use Exception;

/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 */
class ExceptionEvent extends PostEvent
{
    /**
     * The exception to be thrown
     *
     * @var Exception
     */
    protected $exception;

    /**
     * Throw the exception or use the result
     *
     * @var boolean
     */
    protected $throwException = true;

    /**
     * Constructor
     *
     * Accept a target and its parameters.
     *
     * @param  string      $name
     * @param  Adapter     $storage
     * @param  ArrayObject $params
     * @param  mixed       $result
     * @param  Exception   $exception
     */
    public function __construct($name, StorageInterface $storage, ArrayObject $params, & $result, Exception $exception)
    {
        parent::__construct($name, $storage, $params, $result);
        $this->setException($exception);
    }

    /**
     * Set the exception to be thrown
     *
     * @param  Exception $exception
     * @return ExceptionEvent
     */
    public function setException(Exception $exception)
    {
        $this->exception = $exception;
        return $this;
    }

    /**
     * Get the exception to be thrown
     *
     * @return Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Throw the exception or use the result
     *
     * @param  boolean $flag
     * @return ExceptionEvent
     */
    public function setThrowException($flag)
    {
        $this->throwException = (bool) $flag;
        return $this;
    }

    /**
     * Throw the exception or use the result
     *
     * @return boolean
     */
    public function getThrowException()
    {
        return $this->throwException;
    }
}

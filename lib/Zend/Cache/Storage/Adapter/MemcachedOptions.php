<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Cache
 */

namespace Zend\Cache\Storage\Adapter;

use Memcached as MemcachedResource;
use Zend\Cache\Exception;
use Zend\Validator\Hostname;

/**
 * These are options specific to the APC adapter
 *
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 */
class MemcachedOptions extends AdapterOptions
{

    /**
     * Memcached server address
     *
     * @var string
     */
    protected $servers = array();

    /**
     * Libmemcached options
     *
     * @var array
     */
    protected $libOptions = array();

    /**
     * Set namespace.
     *
     * The option Memcached::OPT_PREFIX_KEY will be used as the namespace.
     * It can't be longer than 128 characters.
     *
     * @see AdapterOptions::setNamespace()
     * @see MemcachedOptions::setPrefixKey()
     */
    public function setNamespace($namespace)
    {
        $namespace = (string) $namespace;

        if (128 < strlen($namespace)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a prefix key of no longer than 128 characters',
                __METHOD__
            ));
        }

        return parent::setNamespace($namespace);
    }

    /**
     * Add Server
     *
     * @param string $host
     * @param int $port
     * @return MemcachedOptions
     * @throws Exception\InvalidArgumentException
     */
    public function addServer($host, $port = 11211)
    {
        $hostNameValidator = new Hostname(array('allow' => Hostname::ALLOW_ALL));
        if (!$hostNameValidator->isValid($host)) {
            throw new Exception\InvalidArgumentException(sprintf(
                 '%s expects a valid hostname: %s',
                 __METHOD__,
                 implode("\n", $hostNameValidator->getMessages())
            ));
        }

        if (!is_numeric($port) || $port <= 0) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a positive integer', __METHOD__
            ));
        }

        $this->servers[] = array($host, $port);
        return $this;
    }

    /**
     * Set Servers
     *
     * @param array $servers list of servers in [] = array($host, $port)
     * @return MemcachedOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setServers(array $servers)
    {
        foreach ($servers as $server) {
            if (!isset($server[0])) {
                throw new Exception\InvalidArgumentException('The servers array must contain a host value.');
            }

            if (!isset($server[1])) {
                $this->addServer($server[0]);
            } else {
                $this->addServer($server[0], $server[1]);
            }
        }

        return $this;
    }

    /**
     * Get Servers
     *
     * @return array
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * Set libmemcached options
     *
     * @param array $libOptions
     * @return MemcachedOptions
     * @link http://php.net/manual/memcached.constants.php
     */
    public function setLibOptions(array $libOptions)
    {
        $normalizedOptions = array();
        foreach ($libOptions as $key => $value) {
            $this->normalizeLibOptionKey($key);
            $normalizedOptions[$key] = $value;
        }

        $this->triggerOptionEvent('lib_options', $normalizedOptions);
        $this->libOptions = array_merge($this->libOptions, $normalizedOptions);

        return $this;
    }

    /**
     * Set libmemcached option
     *
     * @param string|int $key
     * @param mixed      $value
     * @return MemcachedOptions
     * @link http://php.net/manual/memcached.constants.php
     */
    public function setLibOption($key, $value)
    {
        $this->normalizeLibOptionKey($key);
        $this->triggerOptionEvent('lib_options', array($key, $value));
        $this->libOptions[$key] = $value;

        return $this;
    }

    /**
     * Get libmemcached options
     *
     * @return array
     * @link http://php.net/manual/memcached.constants.php
     */
    public function getLibOptions()
    {
        return $this->libOptions;
    }

    /**
     * Get libmemcached option
     *
     * @return mixed
     * @link http://php.net/manual/memcached.constants.php
     */
    public function getLibOption($key)
    {
        $this->normalizeLibOptionKey($key);
        if (isset($this->libOptions[$key])) {
            return $this->libOptions[$key];
        }
        return null;
    }

    /**
     * Normalize libmemcached option name into it's constant value
     *
     * @param string|int $key
     * @throws Exception\InvalidArgumentException
     */
    protected function normalizeLibOptionKey(& $key)
    {
        if (is_string($key)) {
            $const = 'Memcached::OPT_' . str_replace(array(' ', '-'), '_', strtoupper($key));
            if (!defined($const)) {
                throw new Exception\InvalidArgumentException("Unknown libmemcached option '{$key}' ({$const})");
            }
            $key = constant($const);
        } else {
            $key = (int) $key;
        }
    }

}

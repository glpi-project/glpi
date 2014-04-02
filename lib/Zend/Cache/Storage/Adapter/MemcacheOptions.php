<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Cache\Storage\Adapter;

use Zend\Cache\Exception;

/**
 * These are options specific to the Memcache adapter
 */
class MemcacheOptions extends AdapterOptions
{
    /**
     * The namespace separator
     * @var string
     */
    protected $namespaceSeparator = ':';

    /**
     * The memcache resource manager
     *
     * @var null|MemcacheResourceManager
     */
    protected $resourceManager;

    /**
     * The resource id of the resource manager
     *
     * @var string
     */
    protected $resourceId = 'default';

    /**
     * Enable compression when data is written
     *
     * @var bool
     */
    protected $compression = false;

    /**
     * Set namespace.
     *
     * It can't be longer than 128 characters.
     *
     * @see AdapterOptions::setNamespace()
     * @see MemcacheOptions::setPrefixKey()
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
     * Set namespace separator
     *
     * @param  string $namespaceSeparator
     * @return MemcacheOptions
     */
    public function setNamespaceSeparator($namespaceSeparator)
    {
        $namespaceSeparator = (string) $namespaceSeparator;
        if ($this->namespaceSeparator !== $namespaceSeparator) {
            $this->triggerOptionEvent('namespace_separator', $namespaceSeparator);
            $this->namespaceSeparator = $namespaceSeparator;
        }
        return $this;
    }

    /**
     * Get namespace separator
     *
     * @return string
     */
    public function getNamespaceSeparator()
    {
        return $this->namespaceSeparator;
    }

    /**
     * Set the memcache resource manager to use
     *
     * @param null|MemcacheResourceManager $resourceManager
     * @return MemcacheOptions
     */
    public function setResourceManager(MemcacheResourceManager $resourceManager = null)
    {
        if ($this->resourceManager !== $resourceManager) {
            $this->triggerOptionEvent('resource_manager', $resourceManager);
            $this->resourceManager = $resourceManager;
        }
        return $this;
    }

    /**
     * Get the memcache resource manager
     *
     * @return MemcacheResourceManager
     */
    public function getResourceManager()
    {
        if (!$this->resourceManager) {
            $this->resourceManager = new MemcacheResourceManager();
        }
        return $this->resourceManager;
    }

    /**
     * Get the memcache resource id
     *
     * @return string
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * Set the memcache resource id
     *
     * @param string $resourceId
     * @return MemcacheOptions
     */
    public function setResourceId($resourceId)
    {
        $resourceId = (string) $resourceId;
        if ($this->resourceId !== $resourceId) {
            $this->triggerOptionEvent('resource_id', $resourceId);
            $this->resourceId = $resourceId;
        }
        return $this;
    }

    /**
     * Is compressed writes turned on?
     *
     * @return boolean
     */
    public function getCompression()
    {
        return $this->compression;
    }

    /**
     * Set whether compressed writes are turned on or not
     *
     * @param boolean $compression
     * @return $this
     */
    public function setCompression($compression)
    {
        $compression = (bool) $compression;
        if ($this->compression !== $compression) {
            $this->triggerOptionEvent('compression', $compression);
            $this->compression = $compression;
        }
        return $this;
    }

    /**
     * Sets a list of memcache servers to add on initialize
     *
     * @param string|array $servers list of servers
     * @return MemcacheOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setServers($servers)
    {
        $this->getResourceManager()->addServers($this->getResourceId(), $servers);
        return $this;
    }

    /**
     * Get Servers
     *
     * @return array
     */
    public function getServers()
    {
        return $this->getResourceManager()->getServers($this->getResourceId());
    }

    /**
     * Set compress threshold
     *
     * @param  int|string|array|ArrayAccess|null $threshold
     * @return MemcacheOptions
     */
    public function setAutoCompressThreshold($threshold)
    {
        $this->getResourceManager()->setAutoCompressThreshold($this->getResourceId(), $threshold);
        return $this;
    }

    /**
     * Get compress threshold
     *
     * @return int|null
     */
    public function getAutoCompressThreshold()
    {
        return $this->getResourceManager()->getAutoCompressThreshold($this->getResourceId());
    }

    /**
     * Set compress min savings option
     *
     * @param  float|string|null $minSavings
     * @return MemcacheOptions
     */
    public function setAutoCompressMinSavings($minSavings)
    {
        $this->getResourceManager()->setAutoCompressMinSavings($this->getResourceId(), $minSavings);
        return $this;
    }

    /**
     * Get compress min savings
     *
     * @return Exception\RuntimeException
     */
    public function getAutoCompressMinSavings()
    {
        return $this->getResourceManager()->getAutoCompressMinSavings($this->getResourceId());
    }

    /**
     * Set default server values
     *
     * @param array $serverDefaults
     * @return MemcacheOptions
     */
    public function setServerDefaults(array $serverDefaults)
    {
        $this->getResourceManager()->setServerDefaults($this->getResourceId(), $serverDefaults);
        return $this;
    }

    /**
     * Get default server values
     *
     * @return array
     */
    public function getServerDefaults()
    {
        return $this->getResourceManager()->getServerDefaults($this->getResourceId());
    }

    /**
     * Set callback for server connection failures
     *
     * @param callable $callback
     * @return $this
     */
    public function setFailureCallback($callback)
    {
        $this->getResourceManager()->setFailureCallback($this->getResourceId(), $callback);
        return $this;
    }

    /**
     * Get callback for server connection failures
     *
     * @return callable
     */
    public function getFailureCallback()
    {
        return $this->getResourceManager()->getFailureCallback($this->getResourceId());
    }
}

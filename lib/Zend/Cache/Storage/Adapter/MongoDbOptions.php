<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Cache\Storage\Adapter;

class MongoDbOptions extends AdapterOptions
{
    /**
     * The namespace separator
     *
     * @var string
     */
    private $namespaceSeparator = ':';

    /**
     * The mongo DB resource manager
     *
     * @var null|MongoDbResourceManager
     */
    private $resourceManager;

    /**
     * The resource id of the resource manager
     *
     * @var string
     */
    private $resourceId = 'default';

    /**
     * Set namespace separator
     *
     * @param  string $namespaceSeparator
     *
     * @return self
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
     * Set the mongodb resource manager to use
     *
     * @param null|MongoDbResourceManager $resourceManager
     *
     * @return self
     */
    public function setResourceManager(MongoDbResourceManager $resourceManager = null)
    {
        if ($this->resourceManager !== $resourceManager) {
            $this->triggerOptionEvent('resource_manager', $resourceManager);

            $this->resourceManager = $resourceManager;
        }

        return $this;
    }

    /**
     * Get the mongodb resource manager
     *
     * @return MongoDbResourceManager
     */
    public function getResourceManager()
    {
        return $this->resourceManager ?: $this->resourceManager = new MongoDbResourceManager();
    }

    /**
     * Get the mongodb resource id
     *
     * @return string
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * Set the mongodb resource id
     *
     * @param string $resourceId
     *
     * @return self
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
     * Set the mongo DB server
     *
     * @param string $server
     * @return self
     */
    public function setServer($server)
    {
        $this->getResourceManager()->setServer($this->getResourceId(), $server);
        return $this;
    }

    public function setConnectionOptions(array $connectionOptions)
    {
        $this->getResourceManager()->setConnectionOptions($this->getResourceId(), $connectionOptions);
        return $this;
    }

    public function setDriverOptions(array $driverOptions)
    {
        $this->getResourceManager()->setDriverOptions($this->getResourceId(), $driverOptions);
        return $this;
    }

    public function setDatabase($database)
    {
        $this->getResourceManager()->setDatabase($this->getResourceId(), $database);
        return $this;
    }

    public function setCollection($collection)
    {
        $this->getResourceManager()->setCollection($this->getResourceId(), $collection);
        return $this;
    }
}

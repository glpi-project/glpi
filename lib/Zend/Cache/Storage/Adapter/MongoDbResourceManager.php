<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Cache\Storage\Adapter;

use MongoCollection;
use MongoException;
use Zend\Cache\Exception;

class MongoDbResourceManager
{
    /**
     * Registered resources
     *
     * @var array[]
     */
    private $resources = array();

    /**
     * Check if a resource exists
     *
     * @param string $id
     *
     * @return bool
     */
    public function hasResource($id)
    {
        return isset($this->resources[$id]);
    }

    /**
     * Set a resource
     *
     * @param string $id
     * @param array|MongoCollection $resource
     *
     * @return self
     *
     * @throws Exception\RuntimeException
     */
    public function setResource($id, $resource)
    {
        if ($resource instanceof MongoCollection) {
            $this->resources[$id] = array(
                'db'                  => (string) $resource->db,
                'db_instance'         => $resource->db,
                'collection'          => (string) $resource,
                'collection_instance' => $resource,
            );
            return $this;
        }

        if (! is_array($resource)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or MongoCollection; received %s',
                __METHOD__,
                (is_object($resource) ? get_class($resource) : gettype($resource))
            ));
        }

        $this->resources[$id] = $resource;
        return $this;
    }

    /**
     * Instantiate and return the MongoCollection resource
     *
     * @param string $id
     * @return MongoCollection
     * @throws Exception\RuntimeException
     */
    public function getResource($id)
    {
        if (!$this->hasResource($id)) {
            throw new Exception\RuntimeException("No resource with id '{$id}'");
        }

        $resource = $this->resources[$id];
        if (!isset($resource['collection_instance'])) {
            try {
                if (!isset($resource['db_instance'])) {
                    if (!isset($resource['client_instance'])) {
                        $clientClass = version_compare(phpversion('mongo'), '1.3.0', '<') ? 'Mongo' : 'MongoClient';
                        $resource['client_instance'] = new $clientClass(
                            isset($resource['server']) ? $resource['server'] : null,
                            isset($resource['connection_options']) ? $resource['connection_options'] : array(),
                            isset($resource['driver_options']) ? $resource['driver_options'] : array()
                        );
                    }

                    $resource['db_instance'] = $resource['client_instance']->selectDB(
                        isset($resource['db']) ? $resource['db'] : ''
                    );
                }

                $collection = $resource['db_instance']->selectCollection(
                    isset($resource['collection']) ? $resource['collection'] : ''
                );
                $collection->ensureIndex(array('key' => 1));

                $this->resources[$id]['collection_instance'] = $collection;
            } catch (MongoException $e) {
                throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->resources[$id]['collection_instance'];
    }

    public function setServer($id, $server)
    {
        $this->resources[$id]['server'] = (string)$server;

        unset($this->resource[$id]['client_instance']);
        unset($this->resource[$id]['db_instance']);
        unset($this->resource[$id]['collection_instance']);
    }

    public function getServer($id)
    {
        if (!$this->hasResource($id)) {
            throw new Exception\RuntimeException("No resource with id '{$id}'");
        }

        return isset($this->resources[$id]['server']) ? $this->resources[$id]['server'] : null;
    }

    public function setConnectionOptions($id, array $connectionOptions)
    {
        $this->resources[$id]['connection_options'] = $connectionOptions;

        unset($this->resource[$id]['client_instance']);
        unset($this->resource[$id]['db_instance']);
        unset($this->resource[$id]['collection_instance']);
    }

    public function getConnectionOptions($id)
    {
        if (!$this->hasResource($id)) {
            throw new Exception\RuntimeException("No resource with id '{$id}'");
        }

        return isset($this->resources[$id]['connection_options'])
            ? $this->resources[$id]['connection_options']
            : array();
    }

    public function setDriverOptions($id, array $driverOptions)
    {
        $this->resources[$id]['driver_options'] = $driverOptions;

        unset($this->resource[$id]['client_instance']);
        unset($this->resource[$id]['db_instance']);
        unset($this->resource[$id]['collection_instance']);
    }

    public function getDriverOptions($id)
    {
        if (!$this->hasResource($id)) {
            throw new Exception\RuntimeException("No resource with id '{$id}'");
        }

        return isset($this->resources[$id]['driver_options']) ? $this->resources[$id]['driver_options'] : array();
    }

    public function setDatabase($id, $database)
    {
        $this->resources[$id]['db'] = (string)$database;

        unset($this->resource[$id]['db_instance']);
        unset($this->resource[$id]['collection_instance']);
    }

    public function getDatabase($id)
    {
        if (!$this->hasResource($id)) {
            throw new Exception\RuntimeException("No resource with id '{$id}'");
        }

        return isset($this->resources[$id]['db']) ? $this->resources[$id]['db'] : '';
    }

    public function setCollection($id, $collection)
    {
        $this->resources[$id]['collection'] = (string)$collection;

        unset($this->resource[$id]['collection_instance']);
    }

    public function getCollection($id)
    {
        if (!$this->hasResource($id)) {
            throw new Exception\RuntimeException("No resource with id '{$id}'");
        }

        return isset($this->resources[$id]['collection']) ? $this->resources[$id]['collection'] : '';
    }
}

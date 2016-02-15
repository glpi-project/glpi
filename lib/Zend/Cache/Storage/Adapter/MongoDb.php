<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Cache\Storage\Adapter;

use MongoCollection as MongoResource;
use MongoDate;
use MongoException as MongoResourceException;
use stdClass;
use Zend\Cache\Exception;
use Zend\Cache\Storage\Capabilities;
use Zend\Cache\Storage\FlushableInterface;

class MongoDb extends AbstractAdapter implements FlushableInterface
{
    /**
     * Has this instance be initialized
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * the mongodb resource manager
     *
     * @var null|MongoDbResourceManager
     */
    private $resourceManager;

    /**
     * The mongodb resource id
     *
     * @var null|string
     */
    private $resourceId;

    /**
     * The namespace prefix
     *
     * @var string
     */
    private $namespacePrefix = '';

    /**
     * {@inheritDoc}
     *
     * @throws Exception\ExtensionNotLoadedException
     */
    public function __construct($options = null)
    {
        if (!class_exists('Mongo') || !class_exists('MongoClient')) {
            throw new Exception\ExtensionNotLoadedException('MongoDb extension not loaded or Mongo polyfill not included');
        }

        parent::__construct($options);

        $initialized = & $this->initialized;

        $this->getEventManager()->attach(
            'option',
            function () use (& $initialized) {
                $initialized = false;
            }
        );
    }

    /**
     * get mongodb resource
     *
     * @return MongoResource
     */
    private function getMongoDbResource()
    {
        if (! $this->initialized) {
            $options = $this->getOptions();

            $this->resourceManager = $options->getResourceManager();
            $this->resourceId      = $options->getResourceId();
            $namespace             = $options->getNamespace();
            $this->namespacePrefix = ($namespace === '' ? '' : $namespace . $options->getNamespaceSeparator());
            $this->initialized     = true;
        }

        return $this->resourceManager->getResource($this->resourceId);
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions($options)
    {
        return parent::setOptions($options instanceof MongoDbOptions ? $options : new MongoDbOptions($options));
    }

    /**
     * Get options.
     *
     * @return MongoDbOptions
     * @see    setOptions()
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null)
    {
        $result  = $this->fetchFromCollection($normalizedKey);
        $success = false;

        if (null === $result) {
            return;
        }

        if (isset($result['expires'])) {
            if (! $result['expires'] instanceof MongoDate) {
                throw new Exception\RuntimeException(sprintf(
                    "The found item _id '%s' for key '%s' is not a valid cache item"
                    . ": the field 'expired' isn't an instance of MongoDate, '%s' found instead",
                    (string) $result['_id'],
                    $this->namespacePrefix . $normalizedKey,
                    is_object($result['expires']) ? get_class($result['expires']) : gettype($result['expires'])
                ));
            }

            if ($result['expires']->sec < time()) {
                $this->internalRemoveItem($key);

                return;
            }
        }

        if (! array_key_exists('value', $result)) {
            throw new Exception\RuntimeException(sprintf(
                "The found item _id '%s' for key '%s' is not a valid cache item: missing the field 'value'",
                (string) $result['_id'],
                $this->namespacePrefix . $normalizedKey
            ));
        }

        $success = true;

        return $casToken = $result['value'];
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    protected function internalSetItem(& $normalizedKey, & $value)
    {
        $mongo     = $this->getMongoDbResource();
        $key       = $this->namespacePrefix . $normalizedKey;
        $ttl       = $this->getOptions()->getTTl();
        $expires   = null;
        $cacheItem = array(
            'key' => $key,
            'value' => $value,
        );

        if ($ttl > 0) {
            $expiresMicro         = microtime(true) + $ttl;
            $expiresSecs          = (int) $expiresMicro;
            $cacheItem['expires'] = new MongoDate($expiresSecs, $expiresMicro - $expiresSecs);
        }

        try {
            $mongo->remove(array('key' => $key));

            $result = $mongo->insert($cacheItem);
        } catch (MongoResourceException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return null !== $result && ((double) 1) === $result['ok'];
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    protected function internalRemoveItem(& $normalizedKey)
    {
        try {
            $result = $this->getMongoDbResource()->remove(array('key' => $this->namespacePrefix . $normalizedKey));
        } catch (MongoResourceException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return false !== $result
            && ((double) 1) === $result['ok']
            && $result['n'] > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function flush()
    {
        $result = $this->getMongoDbResource()->drop();

        return ((double) 1) === $result['ok'];
    }

    /**
     * {@inheritDoc}
     */
    protected function internalGetCapabilities()
    {
        if ($this->capabilities) {
            return $this->capabilities;
        }

        return $this->capabilities = new Capabilities(
            $this,
            $this->capabilityMarker = new stdClass(),
            array(
                'supportedDatatypes' => array(
                    'NULL'     => true,
                    'boolean'  => true,
                    'integer'  => true,
                    'double'   => true,
                    'string'   => true,
                    'array'    => true,
                    'object'   => false,
                    'resource' => false,
                ),
                'supportedMetadata'  => array(
                    '_id',
                ),
                'minTtl'             => 0,
                'maxTtl'             => 0,
                'staticTtl'          => true,
                'ttlPrecision'       => 1,
                'useRequestTime'     => false,
                'expiredRead'        => false,
                'maxKeyLength'       => 255,
                'namespaceIsPrefix'  => true,
            )
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\ExceptionInterface
     */
    protected function internalGetMetadata(& $normalizedKey)
    {
        $result = $this->fetchFromCollection($normalizedKey);

        return null !== $result ? array('_id' => $result['_id']) : false;
    }

    /**
     * Return raw records from MongoCollection
     *
     * @param string $normalizedKey
     *
     * @return array|null
     *
     * @throws Exception\RuntimeException
     */
    private function fetchFromCollection(& $normalizedKey)
    {
        try {
            return $this->getMongoDbResource()->findOne(array('key' => $this->namespacePrefix . $normalizedKey));
        } catch (MongoResourceException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

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

use ArrayObject;
use Memcached as MemcachedResource;
use MemcachedException;
use stdClass;
use Traversable;
use Zend\Cache\Exception;
use Zend\Cache\Storage\AvailableSpaceCapableInterface;
use Zend\Cache\Storage\CallbackEvent;
use Zend\Cache\Storage\Capabilities;
use Zend\Cache\Storage\Event;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\TotalSpaceCapableInterface;

/**
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Storage
 * @subpackage Storage
 */
class Memcached extends AbstractAdapter implements
    AvailableSpaceCapableInterface,
    FlushableInterface,
    TotalSpaceCapableInterface
{
    /**
     * Major version of ext/memcached
     *
     * @var null|int
     */
    protected static $extMemcachedMajorVersion;

    /**
     * Memcached instance
     *
     * @var MemcachedResource
     */
    protected $memcached;

    /**
     * Constructor
     *
     * @param  null|array|Traversable|MemcachedOptions $options
     * @throws Exception\ExceptionInterface
     */
    public function __construct($options = null)
    {
        if (static::$extMemcachedMajorVersion === null) {
            $v = (string) phpversion('memcached');
            static::$extMemcachedMajorVersion = ($v !== '') ? (int)$v[0] : 0;
        }

        if (static::$extMemcachedMajorVersion < 1) {
            throw new Exception\ExtensionNotLoadedException('Need ext/memcached version >= 1.0.0');
        }

        parent::__construct($options);

        // It's ok to init the memcached instance as soon as possible because
        // ext/memcached auto-connects to the server on first use
        $this->memcached = new MemcachedResource();
        $options = $this->getOptions();

        // set lib options
        if (static::$extMemcachedMajorVersion > 1) {
            $this->memcached->setOptions($options->getLibOptions());
        } else {
            foreach ($options->getLibOptions() as $k => $v) {
                $this->memcached->setOption($k, $v);
            }
        }
        $this->memcached->setOption(MemcachedResource::OPT_PREFIX_KEY, $options->getNamespace());

        $servers = $options->getServers();
        if (!$servers) {
            $options->addServer('127.0.0.1', 11211);
            $servers = $options->getServers();
        }
        $this->memcached->addServers($servers);



        // get notified on change options
        $memc   = $this->memcached;
        $memcMV = static::$extMemcachedMajorVersion;
        $this->getEventManager()->attach('option', function ($event) use ($memc, $memcMV) {
            $params = $event->getParams();

            if (isset($params['lib_options'])) {
                if ($memcMV > 1) {
                    $memc->setOptions($params['lib_options']);
                } else {
                    foreach ($params['lib_options'] as $k => $v) {
                        $memc->setOption($k, $v);
                    }
                }
            }

            if (isset($params['namespace'])) {
                $memc->setOption(MemcachedResource::OPT_PREFIX_KEY, $params['namespace']);
            }

            // TODO: update on change/add server(s)
        });
    }

    /* options */

    /**
     * Set options.
     *
     * @param  array|Traversable|MemcachedOptions $options
     * @return Memcached
     * @see    getOptions()
     */
    public function setOptions($options)
    {
        if (!$options instanceof MemcachedOptions) {
            $options = new MemcachedOptions($options);
        }

        return parent::setOptions($options);
    }

    /**
     * Get options.
     *
     * @return MemcachedOptions
     * @see setOptions()
     */
    public function getOptions()
    {
        if (!$this->options) {
            $this->setOptions(new MemcachedOptions());
        }
        return $this->options;
    }

    /* FlushableInterface */

    /**
     * Flush the whole storage
     *
     * @return boolean
     */
    public function flush()
    {
        if (!$this->memcached->flush()) {
            throw $this->getExceptionByResultCode($this->memcached->getResultCode());
        }
        return true;
    }

    /* TotalSpaceCapableInterface */

    /**
     * Get total space in bytes
     *
     * @return int|float
     */
    public function getTotalSpace()
    {
        $stats = $this->memcached->getStats();
        if ($stats === false) {
            throw new Exception\RuntimeException($this->memcached->getResultMessage());
        }

        $mem = array_pop($stats);
        return $mem['limit_maxbytes'];
    }

    /* AvailableSpaceCapableInterface */

    /**
     * Get available space in bytes
     *
     * @return int|float
     */
    public function getAvailableSpace()
    {
        $stats = $this->memcached->getStats();
        if ($stats === false) {
            throw new Exception\RuntimeException($this->memcached->getResultMessage());
        }

        $mem = array_pop($stats);
        return $mem['limit_maxbytes'] - $mem['bytes'];
    }

    /* reading */

    /**
     * Internal method to get an item.
     *
     * @param  string  $normalizedKey
     * @param  boolean $success
     * @param  mixed   $casToken
     * @return mixed Data on success, null on failure
     * @throws Exception\ExceptionInterface
     */
    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null)
    {
        if (func_num_args() > 2) {
            $result = $this->memcached->get($normalizedKey, null, $casToken);
        } else {
            $result = $this->memcached->get($normalizedKey);
        }

        $success = true;
        if ($result === false || $result === null) {
            $rsCode = $this->memcached->getResultCode();
            if ($rsCode == MemcachedResource::RES_NOTFOUND) {
                $result = null;
                $success = false;
            } elseif ($rsCode) {
                $success = false;
                throw $this->getExceptionByResultCode($rsCode);
            }
        }

        return $result;
    }

    /**
     * Internal method to get multiple items.
     *
     * @param  array $normalizedKeys
     * @return array Associative array of keys and values
     * @throws Exception\ExceptionInterface
     */
    protected function internalGetItems(array & $normalizedKeys)
    {
        $result = $this->memcached->getMulti($normalizedKeys);
        if ($result === false) {
            throw $this->getExceptionByResultCode($this->memcached->getResultCode());
        }

        return $result;
    }

    /**
     * Internal method to test if an item exists.
     *
     * @param  string $normalizedKey
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalHasItem(& $normalizedKey)
    {
        $value = $this->memcached->get($normalizedKey);
        if ($value === false || $value === null) {
            $rsCode = $this->memcached->getResultCode();
            if ($rsCode == MemcachedResource::RES_SUCCESS) {
                return true;
            } elseif ($rsCode == MemcachedResource::RES_NOTFOUND) {
                return false;
            } else {
                throw $this->getExceptionByResultCode($rsCode);
            }
        }

        return true;
    }

    /**
     * Internal method to test multiple items.
     *
     * @param  array $normalizedKeys
     * @return array Array of found keys
     * @throws Exception\ExceptionInterface
     */
    protected function internalHasItems(array & $normalizedKeys)
    {
        $result = $this->memcached->getMulti($normalizedKeys);
        if ($result === false) {
            throw $this->getExceptionByResultCode($this->memcached->getResultCode());
        }

        return array_keys($result);
    }

    /**
     * Get metadata of multiple items
     *
     * @param  array $normalizedKeys
     * @return array Associative array of keys and metadata
     * @throws Exception\ExceptionInterface
     */
    protected function internalGetMetadatas(array & $normalizedKeys)
    {
        $result = $this->memcached->getMulti($normalizedKeys);
        if ($result === false) {
            throw $this->getExceptionByResultCode($this->memcached->getResultCode());
        }

        foreach ($result as $key => & $value) {
            $value = array();
        }

        return $result;
    }

    /* writing */

    /**
     * Internal method to store an item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalSetItem(& $normalizedKey, & $value)
    {
        $expiration = $this->expirationTime();
        if (!$this->memcached->set($normalizedKey, $value, $expiration)) {
            throw $this->getExceptionByResultCode($this->memcached->getResultCode());
        }

        return true;
    }

    /**
     * Internal method to store multiple items.
     *
     * @param  array $normalizedKeyValuePairs
     * @return array Array of not stored keys
     * @throws Exception\ExceptionInterface
     */
    protected function internalSetItems(array & $normalizedKeyValuePairs)
    {
        $expiration = $this->expirationTime();
        if (!$this->memcached->setMulti($normalizedKeyValuePairs, $expiration)) {
            throw $this->getExceptionByResultCode($this->memcached->getResultCode());
        }

        return array();
    }

    /**
     * Add an item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalAddItem(& $normalizedKey, & $value)
    {
        $expiration = $this->expirationTime();
        if (!$this->memcached->add($normalizedKey, $value, $expiration)) {
            if ($this->memcached->getResultCode() == MemcachedResource::RES_NOTSTORED) {
                return false;
            }
            throw $this->getExceptionByResultCode($this->memcached->getResultCode());
        }

        return true;
    }

    /**
     * Internal method to replace an existing item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalReplaceItem(& $normalizedKey, & $value)
    {
        $expiration = $this->expirationTime();
        if (!$this->memcached->replace($normalizedKey, $value, $expiration)) {
            if ($this->memcached->getResultCode() == MemcachedResource::RES_NOTSTORED) {
                return false;
            }
            throw $this->getExceptionByResultCode($this->memcached->getResultCode());
        }

        return true;
    }

    /**
     * Internal method to set an item only if token matches
     *
     * @param  mixed  $token
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return boolean
     * @throws Exception\ExceptionInterface
     * @see    getItem()
     * @see    setItem()
     */
    protected function internalCheckAndSetItem(& $token, & $normalizedKey, & $value)
    {
        $expiration = $this->expirationTime();
        $result     = $this->memcached->cas($token, $normalizedKey, $value, $expiration);

        if ($result === false) {
            $rsCode = $this->memcached->getResultCode();
            if ($rsCode !== 0 && $rsCode != MemcachedResource::RES_DATA_EXISTS) {
                throw $this->getExceptionByResultCode($rsCode);
            }
        }


        return $result;
    }

    /**
     * Internal method to remove an item.
     *
     * @param  string $normalizedKey
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalRemoveItem(& $normalizedKey)
    {
        $result = $this->memcached->delete($normalizedKey);

        if ($result === false) {
            $rsCode = $this->memcached->getResultCode();
            if ($rsCode == MemcachedResource::RES_NOTFOUND) {
                return false;
            } elseif ($rsCode != MemcachedResource::RES_SUCCESS) {
                throw $this->getExceptionByResultCode($rsCode);
            }
        }

        return true;
    }

    /**
     * Internal method to remove multiple items.
     *
     * @param  array $normalizedKeys
     * @return array Array of not removed keys
     * @throws Exception\ExceptionInterface
     */
    protected function internalRemoveItems(array & $normalizedKeys)
    {
        // support for removing multiple items at once has been added in ext/memcached-2.0.0
        if (static::$extMemcachedMajorVersion < 2) {
            return parent::internalRemoveItems($normalizedKeys);
        }

        $rsCodes = $this->memcached->deleteMulti($normalizedKeys);

        $missingKeys = array();
        foreach ($rsCodes as $key => $rsCode) {
            if ($rsCode !== true && $rsCode != MemcachedResource::RES_SUCCESS) {
                if ($rsCode != MemcachedResource::RES_NOTFOUND) {
                    throw $this->getExceptionByResultCode($rsCode);
                }
                $missingKeys[] = $key;
            }
        }

        return $missingKeys;
    }

    /**
     * Internal method to increment an item.
     *
     * @param  string $normalizedKey
     * @param  int    $value
     * @return int|boolean The new value on success, false on failure
     * @throws Exception\ExceptionInterface
     */
    protected function internalIncrementItem(& $normalizedKey, & $value)
    {
        $value    = (int)$value;
        $newValue = $this->memcached->increment($normalizedKey, $value);

        if ($newValue === false) {
            $rsCode = $this->memcached->getResultCode();

            // initial value
            if ($rsCode == MemcachedResource::RES_NOTFOUND) {
                $newValue = $value;
                $this->memcached->add($normalizedKey, $newValue, $this->expirationTime());
                $rsCode = $this->memcached->getResultCode();
            }

            if ($rsCode) {
                throw $this->getExceptionByResultCode($rsCode);
            }
        }

        return $newValue;
    }

    /**
     * Internal method to decrement an item.
     *
     * @param  string $normalizedKey
     * @param  int    $value
     * @return int|boolean The new value on success, false on failure
     * @throws Exception\ExceptionInterface
     */
    protected function internalDecrementItem(& $normalizedKey, & $value)
    {
        $value    = (int)$value;
        $newValue = $this->memcached->decrement($normalizedKey, $value);

        if ($newValue === false) {
            $rsCode = $this->memcached->getResultCode();

            // initial value
            if ($rsCode == MemcachedResource::RES_NOTFOUND) {
                $newValue = -$value;
                $this->memcached->add($normalizedKey, $newValue, $this->expirationTime());
                $rsCode = $this->memcached->getResultCode();
            }

            if ($rsCode) {
                throw $this->getExceptionByResultCode($rsCode);
            }
        }

        return $newValue;
    }

    /* status */

    /**
     * Internal method to get capabilities of this adapter
     *
     * @return Capabilities
     */
    protected function internalGetCapabilities()
    {
        if ($this->capabilities === null) {
            $this->capabilityMarker = new stdClass();
            $this->capabilities     = new Capabilities(
                $this,
                $this->capabilityMarker,
                array(
                    'supportedDatatypes' => array(
                        'NULL'     => true,
                        'boolean'  => true,
                        'integer'  => true,
                        'double'   => true,
                        'string'   => true,
                        'array'    => true,
                        'object'   => 'object',
                        'resource' => false,
                    ),
                    'supportedMetadata'  => array(),
                    'minTtl'             => 1,
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

        return $this->capabilities;
    }

    /* internal */

    /**
     * Get expiration time by ttl
     *
     * Some storage commands involve sending an expiration value (relative to
     * an item or to an operation requested by the client) to the server. In
     * all such cases, the actual value sent may either be Unix time (number of
     * seconds since January 1, 1970, as an integer), or a number of seconds
     * starting from current time. In the latter case, this number of seconds
     * may not exceed 60*60*24*30 (number of seconds in 30 days); if the
     * expiration value is larger than that, the server will consider it to be
     * real Unix time value rather than an offset from current time.
     *
     * @return int
     */
    protected function expirationTime()
    {
        $ttl = $this->getOptions()->getTtl();
        if ($ttl > 2592000) {
            return time() + $ttl;
        }
        return $ttl;
    }

    /**
     * Generate exception based of memcached result code
     *
     * @param int $code
     * @return Exception\RuntimeException
     * @throws Exception\InvalidArgumentException On success code
     */
    protected function getExceptionByResultCode($code)
    {
        switch ($code) {
            case MemcachedResource::RES_SUCCESS:
                throw new Exception\InvalidArgumentException(
                    "The result code '{$code}' (SUCCESS) isn't an error"
                );

            default:
                return new Exception\RuntimeException($this->memcached->getResultMessage());
        }
    }
}

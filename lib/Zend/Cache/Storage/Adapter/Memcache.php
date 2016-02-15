<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Cache\Storage\Adapter;

use Memcache as MemcacheResource;
use stdClass;
use Traversable;
use Zend\Cache\Exception;
use Zend\Cache\Storage\AvailableSpaceCapableInterface;
use Zend\Cache\Storage\Capabilities;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\TotalSpaceCapableInterface;

class Memcache extends AbstractAdapter implements
    AvailableSpaceCapableInterface,
    FlushableInterface,
    TotalSpaceCapableInterface
{
    /**
     * Has this instance been initialized
     *
     * @var bool
     */
    protected $initialized = false;

    /**
     * The memcache resource manager
     *
     * @var null|MemcacheResourceManager
     */
    protected $resourceManager;

    /**
     * The memcache resource id
     *
     * @var null|string
     */
    protected $resourceId;

    /**
     * The namespace prefix
     *
     * @var string
     */
    protected $namespacePrefix = '';

    /**
     * Constructor
     *
     * @param  null|array|Traversable|MemcacheOptions $options
     * @throws Exception\ExceptionInterface
     */
    public function __construct($options = null)
    {
        if (version_compare('2.0.0', phpversion('memcache')) > 0) {
            throw new Exception\ExtensionNotLoadedException("Missing ext/memcache version >= 2.0.0");
        }

        parent::__construct($options);

        // reset initialized flag on update option(s)
        $initialized = & $this->initialized;
        $this->getEventManager()->attach('option', function () use (& $initialized) {
            $initialized = false;
        });
    }

    /**
     * Initialize the internal memcache resource
     *
     * @return MemcacheResource
     */
    protected function getMemcacheResource()
    {
        if ($this->initialized) {
            return $this->resourceManager->getResource($this->resourceId);
        }

        $options = $this->getOptions();

        // get resource manager and resource id
        $this->resourceManager = $options->getResourceManager();
        $this->resourceId      = $options->getResourceId();

        // init namespace prefix
        $this->namespacePrefix = '';
        $namespace = $options->getNamespace();
        if ($namespace !== '') {
            $this->namespacePrefix = $namespace . $options->getNamespaceSeparator();
        }

        // update initialized flag
        $this->initialized = true;

        return $this->resourceManager->getResource($this->resourceId);
    }

    /* options */

    /**
     * Set options.
     *
     * @param  array|Traversable|MemcacheOptions $options
     * @return Memcache
     * @see    getOptions()
     */
    public function setOptions($options)
    {
        if (!$options instanceof MemcacheOptions) {
            $options = new MemcacheOptions($options);
        }

        return parent::setOptions($options);
    }

    /**
     * Get options.
     *
     * @return MemcacheOptions
     * @see setOptions()
     */
    public function getOptions()
    {
        if (!$this->options) {
            $this->setOptions(new MemcacheOptions());
        }
        return $this->options;
    }

    /**
     * @param  mixed $value
     * @return int
     */
    protected function getWriteFlag(& $value)
    {
        if (!$this->getOptions()->getCompression()) {
            return 0;
        }
        // Don't compress numeric or boolean types
        return (is_bool($value) || is_int($value) || is_float($value)) ? 0 : MEMCACHE_COMPRESSED;
    }

    /* FlushableInterface */

    /**
     * Flush the whole storage
     *
     * @return bool
     */
    public function flush()
    {
        $memc = $this->getMemcacheResource();
        if (!$memc->flush()) {
            return new Exception\RuntimeException("Memcache flush failed");
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
        $memc  = $this->getMemcacheResource();
        $stats = $memc->getExtendedStats();
        if ($stats === false) {
            return new Exception\RuntimeException("Memcache getStats failed");
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
        $memc  = $this->getMemcacheResource();
        $stats = $memc->getExtendedStats();
        if ($stats === false) {
            throw new Exception\RuntimeException('Memcache getStats failed');
        }

        $mem = array_pop($stats);
        return $mem['limit_maxbytes'] - $mem['bytes'];
    }

    /* reading */

    /**
     * Internal method to get an item.
     *
     * @param  string  $normalizedKey
     * @param  bool    $success
     * @param  mixed   $casToken
     * @return mixed Data on success, null on failure
     * @throws Exception\ExceptionInterface
     */
    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null)
    {
        $memc        = $this->getMemcacheResource();
        $internalKey = $this->namespacePrefix . $normalizedKey;

        $result = $memc->get($internalKey);
        $success = ($result !== false);
        if ($result === false) {
            return;
        }

        $casToken = $result;
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
        $memc = $this->getMemcacheResource();

        foreach ($normalizedKeys as & $normalizedKey) {
            $normalizedKey = $this->namespacePrefix . $normalizedKey;
        }

        $result = $memc->get($normalizedKeys);
        if ($result === false) {
            return array();
        }

        // remove namespace prefix from result
        if ($this->namespacePrefix !== '') {
            $tmp            = array();
            $nsPrefixLength = strlen($this->namespacePrefix);
            foreach ($result as $internalKey => & $value) {
                $tmp[substr($internalKey, $nsPrefixLength)] = & $value;
            }
            $result = $tmp;
        }

        return $result;
    }

    /**
     * Internal method to test if an item exists.
     *
     * @param  string $normalizedKey
     * @return bool
     * @throws Exception\ExceptionInterface
     */
    protected function internalHasItem(& $normalizedKey)
    {
        $memc  = $this->getMemcacheResource();
        $value = $memc->get($this->namespacePrefix . $normalizedKey);
        return ($value !== false);
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
        $memc = $this->getMemcacheResource();

        foreach ($normalizedKeys as & $normalizedKey) {
            $normalizedKey = $this->namespacePrefix . $normalizedKey;
        }

        $result = $memc->get($normalizedKeys);
        if ($result === false) {
            return array();
        }

        // Convert to a single list
        $result = array_keys($result);

        // remove namespace prefix
        if ($result && $this->namespacePrefix !== '') {
            $nsPrefixLength = strlen($this->namespacePrefix);
            foreach ($result as & $internalKey) {
                $internalKey = substr($internalKey, $nsPrefixLength);
            }
        }

        return $result;
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
        $memc = $this->getMemcacheResource();

        foreach ($normalizedKeys as & $normalizedKey) {
            $normalizedKey = $this->namespacePrefix . $normalizedKey;
        }

        $result = $memc->get($normalizedKeys);
        if ($result === false) {
            return array();
        }

        // remove namespace prefix and use an empty array as metadata
        if ($this->namespacePrefix === '') {
            foreach ($result as & $value) {
                $value = array();
            }
            return $result;
        }

        $final          = array();
        $nsPrefixLength = strlen($this->namespacePrefix);
        foreach (array_keys($result) as $internalKey) {
            $final[substr($internalKey, $nsPrefixLength)] = array();
        }
        return $final;
    }

    /* writing */

    /**
     * Internal method to store an item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return bool
     * @throws Exception\ExceptionInterface
     */
    protected function internalSetItem(& $normalizedKey, & $value)
    {
        $memc       = $this->getMemcacheResource();
        $expiration = $this->expirationTime();
        $flag       = $this->getWriteFlag($value);

        if (!$memc->set($this->namespacePrefix . $normalizedKey, $value, $flag, $expiration)) {
            throw new Exception\RuntimeException('Memcache set value failed');
        }

        return true;
    }

    /**
     * Add an item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return bool
     * @throws Exception\ExceptionInterface
     */
    protected function internalAddItem(& $normalizedKey, & $value)
    {
        $memc       = $this->getMemcacheResource();
        $expiration = $this->expirationTime();
        $flag       = $this->getWriteFlag($value);

        return $memc->add($this->namespacePrefix . $normalizedKey, $value, $flag, $expiration);
    }

    /**
     * Internal method to replace an existing item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return bool
     * @throws Exception\ExceptionInterface
     */
    protected function internalReplaceItem(& $normalizedKey, & $value)
    {
        $memc       = $this->getMemcacheResource();
        $expiration = $this->expirationTime();
        $flag       = $this->getWriteFlag($value);

        return $memc->replace($this->namespacePrefix . $normalizedKey, $value, $flag, $expiration);
    }

    /**
     * Internal method to remove an item.
     *
     * @param  string $normalizedKey
     * @return bool
     * @throws Exception\ExceptionInterface
     */
    protected function internalRemoveItem(& $normalizedKey)
    {
        $memc   = $this->getMemcacheResource();
        // Delete's second parameter (timeout) is deprecated and not supported.
        // Values other than 0 may cause delete to fail.
        // http://www.php.net/manual/memcache.delete.php
        return $memc->delete($this->namespacePrefix . $normalizedKey, 0);
    }

    /**
     * Internal method to increment an item.
     *
     * @param  string $normalizedKey
     * @param  int    $value
     * @return int|bool The new value on success, false on failure
     * @throws Exception\ExceptionInterface
     */
    protected function internalIncrementItem(& $normalizedKey, & $value)
    {
        $memc        = $this->getMemcacheResource();
        $internalKey = $this->namespacePrefix . $normalizedKey;
        $value       = (int) $value;
        $newValue    = $memc->increment($internalKey, $value);

        if ($newValue !== false) {
            return $newValue;
        }

        // Set initial value. Don't use compression!
        // http://www.php.net/manual/memcache.increment.php
        $newValue = $value;
        if (!$memc->add($internalKey, $newValue, 0, $this->expirationTime())) {
            throw new Exception\RuntimeException('Memcache unable to add increment value');
        }

        return $newValue;
    }

    /**
     * Internal method to decrement an item.
     *
     * @param  string $normalizedKey
     * @param  int    $value
     * @return int|bool The new value on success, false on failure
     * @throws Exception\ExceptionInterface
     */
    protected function internalDecrementItem(& $normalizedKey, & $value)
    {
        $memc        = $this->getMemcacheResource();
        $internalKey = $this->namespacePrefix . $normalizedKey;
        $value       = (int) $value;
        $newValue    = $memc->decrement($internalKey, $value);

        if ($newValue !== false) {
            return $newValue;
        }

        // Set initial value. Don't use compression!
        // http://www.php.net/manual/memcache.decrement.php
        $newValue = -$value;
        if (!$memc->add($internalKey, $newValue, 0, $this->expirationTime())) {
            throw new Exception\RuntimeException('Memcache unable to add decrement value');
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
        if ($this->capabilities !== null) {
            return $this->capabilities;
        }

        if (version_compare('3.0.3', phpversion('memcache')) <= 0) {
            // In ext/memcache v3.0.3:
            // Scalar data types (int, bool, double) are preserved by get/set.
            // http://pecl.php.net/package/memcache/3.0.3
            //
            // This effectively removes support for `boolean` types since
            // "not found" return values are === false.
            $supportedDatatypes = array(
                'NULL'     => true,
                'boolean'  => false,
                'integer'  => true,
                'double'   => true,
                'string'   => true,
                'array'    => true,
                'object'   => 'object',
                'resource' => false,
            );
        } else {
            // In stable 2.x ext/memcache versions, scalar data types are
            // converted to strings and must be manually cast back to original
            // types by the user.
            //
            // ie. It is impossible to know if the saved value: (string)"1"
            // was previously: (bool)true, (int)1, or (string)"1".
            // Similarly, the saved value: (string)""
            // might have previously been: (bool)false or (string)""
            $supportedDatatypes = array(
                'NULL'     => true,
                'boolean'  => 'boolean',
                'integer'  => 'integer',
                'double'   => 'double',
                'string'   => true,
                'array'    => true,
                'object'   => 'object',
                'resource' => false,
            );
        }

        $this->capabilityMarker = new stdClass();
        $this->capabilities     = new Capabilities(
            $this,
            $this->capabilityMarker,
            array(
                'supportedDatatypes' => $supportedDatatypes,
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
}

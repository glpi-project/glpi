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
use stdClass;
use Zend\Cache\Exception;
use Zend\Cache\Storage\Capabilities;

/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 */
abstract class AbstractZendServer extends AbstractAdapter
{
    /**
     * The namespace separator used on Zend Data Cache functions
     *
     * @var string
     */
    const NAMESPACE_SEPARATOR = '::';

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
        $prefix      = $this->getOptions()->getNamespace() . self::NAMESPACE_SEPARATOR;
        $internalKey = $prefix . $normalizedKey;

        $result = $this->zdcFetch($internalKey);
        if ($result === false) {
            $success = false;
            $result  = null;
        } else {
            $success  = true;
            $casToken = $result;
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
        $prefix  = $this->getOptions()->getNamespace() . self::NAMESPACE_SEPARATOR;
        $prefixL = strlen($prefix);

        $internalKeys = array();
        foreach ($normalizedKeys as $normalizedKey) {
            $internalKeys[] = $prefix . $normalizedKey;
        }

        $fetch  = $this->zdcFetchMulti($internalKeys);
        $result = array();
        foreach ($fetch as $k => & $v) {
            $result[ substr($k, $prefixL) ] = $v;
        }

        return $result;
    }

    /**
     * Internal method to test if an item exists.
     *
     * @param  string $normalizedKey
     * @param  array  $normalizedOptions
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalHasItem(& $normalizedKey)
    {

        $prefix = $this->getOptions()->getNamespace() . self::NAMESPACE_SEPARATOR;
        return  ($this->zdcFetch($prefix . $normalizedKey) !== false);
    }

    /**
     * Internal method to test multiple items.
     *
     * @param  array $keys
     * @return array Array of found keys
     * @throws Exception\ExceptionInterface
     */
    protected function internalHasItems(array & $normalizedKeys)
    {
        $prefix  = $this->getOptions()->getNamespace() . self::NAMESPACE_SEPARATOR;
        $prefixL = strlen($prefix);

        $internalKeys = array();
        foreach ($normalizedKeys as $normalizedKey) {
            $internalKeys[] = $prefix . $normalizedKey;
        }

        $fetch  = $this->zdcFetchMulti($internalKeys);
        $result = array();
        foreach ($fetch as $internalKey => & $value) {
            $result[] = substr($internalKey, $prefixL);
        }

        return $result;
    }

    /**
     * Get metadata for multiple items
     *
     * @param  array $normalizedKeys
     * @return array Associative array of keys and metadata
     *
     * @triggers getMetadatas.pre(PreEvent)
     * @triggers getMetadatas.post(PostEvent)
     * @triggers getMetadatas.exception(ExceptionEvent)
     */
    protected function internalGetMetadatas(array & $normalizedKeys)
    {
        $prefix  = $this->getOptions()->getNamespace() . self::NAMESPACE_SEPARATOR;
        $prefixL = strlen($prefix);

        $internalKeys = array();
        foreach ($normalizedKeys as $normalizedKey) {
            $internalKeys[] = $prefix . $normalizedKey;
        }

        $fetch  = $this->zdcFetchMulti($internalKeys);
        $result = array();
        foreach ($fetch as $internalKey => $value) {
            $result[ substr($internalKey, $prefixL) ] = array();
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
        $options = $this->getOptions();
        $internalKey = $options->getNamespace() . self::NAMESPACE_SEPARATOR . $normalizedKey;
        $this->zdcStore($internalKey, $value, $options->getTtl());
        return true;
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
        $internalKey = $this->getOptions()->getNamespace() . self::NAMESPACE_SEPARATOR . $normalizedKey;
        return $this->zdcDelete($internalKey);
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
                    'maxTtl'             => 0,
                    'staticTtl'          => true,
                    'ttlPrecision'       => 1,
                    'useRequestTime'     => false,
                    'expiredRead'        => false,
                    'maxKeyLength'       => 0,
                    'namespaceIsPrefix'  => true,
                    'namespaceSeparator' => self::NAMESPACE_SEPARATOR,
                )
            );
        }

        return $this->capabilities;
    }

    /* internal wrapper of zend_[disk|shm]_cache_* functions */

    /**
     * Store data into Zend Data Cache (zdc)
     *
     * @param  string $internalKey
     * @param  mixed  $value
     * @param  int    $ttl
     * @return void
     * @throws Exception\RuntimeException
     */
    abstract protected function zdcStore($internalKey, $value, $ttl);

    /**
     * Fetch a single item from Zend Data Cache (zdc)
     *
     * @param  string $internalKey
     * @return mixed The stored value or FALSE if item wasn't found
     * @throws Exception\RuntimeException
     */
    abstract protected function zdcFetch($internalKey);

    /**
     * Fetch multiple items from Zend Data Cache (zdc)
     *
     * @param  array $internalKeys
     * @return array All found items
     * @throws Exception\RuntimeException
     */
    abstract protected function zdcFetchMulti(array $internalKeys);

    /**
     * Delete data from Zend Data Cache (zdc)
     *
     * @param  string $internalKey
     * @return boolean
     * @throws Exception\RuntimeException
     */
    abstract protected function zdcDelete($internalKey);
}

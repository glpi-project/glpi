<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Loader
 */

namespace Zend\Loader;

use Traversable;

/**
 * Plugin class locator interface
 *
 * @category   Zend
 * @package    Zend_Loader
 */
interface PluginClassLocator extends ShortNameLocator, \IteratorAggregate
{
    /**
     * Register a class to a given short name
     *
     * @param  string $shortName
     * @param  string $className
     * @return PluginClassLocator
     */
    public function registerPlugin($shortName, $className);

    /**
     * Unregister a short name lookup
     *
     * @param  mixed $shortName
     * @return void
     */
    public function unregisterPlugin($shortName);

    /**
     * Get a list of all registered plugins
     *
     * @return array|Traversable
     */
    public function getRegisteredPlugins();
}

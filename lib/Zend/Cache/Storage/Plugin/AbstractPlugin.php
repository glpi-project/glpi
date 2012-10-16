<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Cache
 */

namespace Zend\Cache\Storage\Plugin;

/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 */
abstract class AbstractPlugin implements PluginInterface
{
    /**
     * @var PluginOptions
     */
    protected $options;

    /**
     * Set pattern options
     *
     * @param  PluginOptions $options
     * @return AbstractPlugin
     */
    public function setOptions(PluginOptions $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get all pattern options
     *
     * @return PluginOptions
     */
    public function getOptions()
    {
        if (null === $this->options) {
            $this->setOptions(new PluginOptions());
        }
        return $this->options;
    }
}

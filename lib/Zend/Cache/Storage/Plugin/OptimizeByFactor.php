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

use Traversable;
use Zend\Cache\Exception;
use Zend\Cache\Storage\OptimizableInterface;
use Zend\Cache\Storage\PostEvent;
use Zend\EventManager\EventManagerInterface;

/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 */
class OptimizeByFactor extends AbstractPlugin
{
    /**
     * Handles
     *
     * @var array
     */
    protected $handles = array();

    /**
     * Attach
     *
     * @param  EventManagerInterface $events
     * @param  int                   $priority
     * @return OptimizeByFactor
     * @throws Exception\LogicException
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $index = spl_object_hash($events);
        if (isset($this->handles[$index])) {
            throw new Exception\LogicException('Plugin already attached');
        }

        $handles = array();
        $this->handles[$index] = & $handles;

        $callback = array($this, 'optimizeByFactor');
        $handles[] = $events->attach('removeItem.post',  $callback, $priority);
        $handles[] = $events->attach('removeItems.post', $callback, $priority);

        return $this;
    }

    /**
     * Detach
     *
     * @param  EventManagerInterface $events
     * @return OptimizeByFactor
     * @throws Exception\LogicException
     */
    public function detach(EventManagerInterface $events)
    {
        $index = spl_object_hash($events);
        if (!isset($this->handles[$index])) {
            throw new Exception\LogicException('Plugin not attached');
        }

        // detach all handles of this index
        foreach ($this->handles[$index] as $handle) {
            $events->detach($handle);
        }

        // remove all detached handles
        unset($this->handles[$index]);

        return $this;
    }

    /**
     * Optimize by factor on a success _RESULT_
     *
     * @param  PostEvent $event
     * @return void
     */
    public function optimizeByFactor(PostEvent $event)
    {
        $storage = $event->getStorage();
        if (!($storage instanceof OptimizableInterface)) {
            return;
        }

        $factor = $this->getOptions()->getOptimizingFactor();
        if ($factor && mt_rand(1, $factor) == 1) {
            $storage->optimize();
        }
    }
}

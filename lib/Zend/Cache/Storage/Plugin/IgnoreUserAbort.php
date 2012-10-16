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

use Zend\Cache\Exception;
use Zend\Cache\Storage\Event;
use Zend\EventManager\EventManagerInterface;

/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 */
class IgnoreUserAbort extends AbstractPlugin
{
    /**
     * Handles
     *
     * @var array
     */
    protected $handles = array();

    /**
     * The storage who activated ignore_user_abort.
     *
     * @var null|\Zend\Cache\Storage\StorageInterface
     */
    protected $activatedTarget = null;

    /**
     * Attach
     *
     * @param  EventManagerInterface $events
     * @param  int                   $priority
     * @return Serializer
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

        $cbOnBefore = array($this, 'onBefore');
        $cbOnAfter  = array($this, 'onAfter');

        $handles[] = $events->attach('setItem.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('setItem.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('setItem.exception', $cbOnAfter, $priority);

        $handles[] = $events->attach('setItems.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('setItems.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('setItems.exception', $cbOnAfter, $priority);

        $handles[] = $events->attach('addItem.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('addItem.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('addItem.exception', $cbOnAfter, $priority);

        $handles[] = $events->attach('addItems.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('addItems.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('addItems.exception', $cbOnAfter, $priority);

        $handles[] = $events->attach('replaceItem.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('replaceItem.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('replaceItem.exception', $cbOnAfter, $priority);

        $handles[] = $events->attach('replaceItems.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('replaceItems.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('replaceItems.exception', $cbOnAfter, $priority);

        $handles[] = $events->attach('checkAndSetItem.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('checkAndSetItem.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('checkAndSetItem.exception', $cbOnAfter, $priority);

        // increment / decrement item(s)
        $handles[] = $events->attach('incrementItem.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('incrementItem.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('incrementItem.exception', $cbOnAfter, $priority);

        $handles[] = $events->attach('incrementItems.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('incrementItems.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('incrementItems.exception', $cbOnAfter, $priority);

        $handles[] = $events->attach('decrementItem.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('decrementItem.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('decrementItem.exception', $cbOnAfter, $priority);

        $handles[] = $events->attach('decrementItems.pre',       $cbOnBefore, $priority);
        $handles[] = $events->attach('decrementItems.post',      $cbOnAfter, $priority);
        $handles[] = $events->attach('decrementItems.exception', $cbOnAfter, $priority);

        return $this;
    }

    /**
     * Detach
     *
     * @param  EventManagerInterface $events
     * @return Serializer
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
     * Activate ignore_user_abort if not already done
     * and save the target who activated it.
     *
     * @param  Event $event
     * @return void
     */
    public function onBefore(Event $event)
    {
        if ($this->activatedTarget === null && !ignore_user_abort(true)) {
            $this->activatedTarget = $event->getTarget();
        }
    }

    /**
     * Reset ignore_user_abort if it's activated and if it's the same target
     * who activated it.
     *
     * If exit_on_abort is enabled and the connection has been aborted
     * exit the script.
     *
     * @param  Event $event
     * @return void
     */
    public function onAfter(Event $event)
    {
        if ($this->activatedTarget === $event->getTarget()) {
            // exit if connection aborted
            if ($this->getOptions()->getExitOnAbort() && connection_aborted()) {
                exit;
            }

            // reset ignore_user_abort
            ignore_user_abort(false);

            // remove activated target
            $this->activatedTarget = null;
        }
    }
}

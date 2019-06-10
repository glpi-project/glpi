<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2019 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\EventSubscriber\SIEM;

use Glpi\Event\ITILEventHostEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin;

class ITILEventHostEventHookMapper implements EventSubscriberInterface
{
    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public static function getSubscribedEvents()
    {
        return [
            ITILEventHostEvent::HOST_DOWN                => 'doHostDownHook',
            ITILEventHostEvent::HOST_UP                  => 'doHostUpHook',
            ITILEventHostEvent::HOST_UNREACHABLE         => 'doHostUnreachableHook',
            ITILEventHostEvent::HOST_ACKNOWLEDGE         => 'doHostAcknowledgeHook',
            ITILEventHostEvent::HOST_START_FLAPPING      => 'doHostStartFlappingHook',
            ITILEventHostEvent::HOST_STOP_FLAPPING       => 'doHostStopFlappingHook',
            ITILEventHostEvent::HOST_DISABLE_FLAPPING    => 'doHostDisableFlappingHook',
        ];
    }

    /**
     * Call 'itileventhost_down' hook.
     *
     * @param ITILEventHostEvent $event
     *
     * @return void
     */
    public function doHostDownHook(ITILEventHostEvent $event)
    {
        $this->plugin->doHook('itileventhost_down', $event->getHost());
    }

    /**
     * Call 'itileventhost_up' hook.
     *
     * @param ITILEventHostEvent $event
     *
     * @return void
     */
    public function doHostUpHook(ITILEventHostEvent $event)
    {
        $this->plugin->doHook('itileventhost_up', $event->getHost());
    }

    /**
     * Call 'itileventhost_unreachable' hook.
     *
     * @param ITILEventHostEvent $event
     *
     * @return void
     */
    public function doHostUnreachableHook(ITILEventHostEvent $event)
    {
        $this->plugin->doHook('itileventhost_unreachable', $event->getHost());
    }

    /**
     * Call 'itileventhost_acknowledge' hook.
     *
     * @param ITILEventHostEvent $event
     *
     * @return void
     */
    public function doHostAcknowledgeHook(ITILEventHostEvent $event)
    {
        $this->plugin->doHook('itileventhost_acknowledge', $event->getHost());
    }

    /**
     * Call 'itileventhost_start_flapping' hook.
     *
     * @param ITILEventHostEvent $event
     *
     * @return void
     */
    public function doHostStartFlappingHook(ITILEventHostEvent $event)
    {
        $this->plugin->doHook('itileventhost_start_flapping', $event->getHost());
    }

    /**
     * Call 'itileventhost_stop_flapping' hook.
     *
     * @param ITILEventHostEvent $event
     *
     * @return void
     */
    public function doHostStopFlappingHook(ITILEventHostEvent $event)
    {
        $this->plugin->doHook('itileventhost_stop_flapping', $event->getHost());
    }

    /**
     * Call 'itileventhost_disable_flapping' hook.
     *
     * @param ITILEventHostEvent $event
     *
     * @return void
     */
    public function doHostDisableFlappingHook(ITILEventHostEvent $event)
    {
        $this->plugin->doHook('itileventhost_disable_flapping', $event->getHost());
    }
}

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

use Glpi\Event\SIEMHostEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin;

class SIEMHostEventHookMapper implements EventSubscriberInterface
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
            SIEMHostEvent::HOST_DOWN                => 'doHostDownHook',
            SIEMHostEvent::HOST_UP                  => 'doHostUpHook',
            SIEMHostEvent::HOST_UNREACHABLE         => 'doHostUnreachableHook',
            SIEMHostEvent::HOST_ACKNOWLEDGE         => 'doHostAcknowledgeHook',
            SIEMHostEvent::HOST_START_FLAPPING      => 'doHostStartFlappingHook',
            SIEMHostEvent::HOST_STOP_FLAPPING       => 'doHostStopFlappingHook',
            SIEMHostEvent::HOST_DISABLE_FLAPPING    => 'doHostDisableFlappingHook',
        ];
    }

    /**
     * Call 'siemhost_down' hook.
     *
     * @param SIEMHostEvent $event
     *
     * @return void
     */
    public function doHostDownHook(SIEMHostEvent $event)
    {
        $this->plugin->doHook('siemhost_down', $event->getHost());
    }

    /**
     * Call 'siemhost_up' hook.
     *
     * @param SIEMHostEvent $event
     *
     * @return void
     */
    public function doHostUpHook(SIEMHostEvent $event)
    {
        $this->plugin->doHook('siemhost_up', $event->getHost());
    }

    /**
     * Call 'siemhost_unreachable' hook.
     *
     * @param SIEMHostEvent $event
     *
     * @return void
     */
    public function doHostUnreachableHook(SIEMHostEvent $event)
    {
        $this->plugin->doHook('siemhost_unreachable', $event->getHost());
    }

    /**
     * Call 'siemhost_acknowledge' hook.
     *
     * @param SIEMHostEvent $event
     *
     * @return void
     */
    public function doHostAcknowledgeHook(SIEMHostEvent $event)
    {
        $this->plugin->doHook('siemhost_acknowledge', $event->getHost());
    }

    /**
     * Call 'siemhost_start_flapping' hook.
     *
     * @param SIEMHostEvent $event
     *
     * @return void
     */
    public function doHostStartFlappingHook(SIEMHostEvent $event)
    {
        $this->plugin->doHook('siemhost_start_flapping', $event->getHost());
    }

    /**
     * Call 'siemhost_stop_flapping' hook.
     *
     * @param SIEMHostEvent $event
     *
     * @return void
     */
    public function doHostStopFlappingHook(SIEMHostEvent $event)
    {
        $this->plugin->doHook('siemhost_stop_flapping', $event->getHost());
    }

    /**
     * Call 'siemhost_disable_flapping' hook.
     *
     * @param SIEMHostEvent $event
     *
     * @return void
     */
    public function doHostDisableFlappingHook(SIEMHostEvent $event)
    {
        $this->plugin->doHook('siemhost_disable_flapping', $event->getHost());
    }
}

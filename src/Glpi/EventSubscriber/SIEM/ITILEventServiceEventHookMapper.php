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

use Glpi\Event\ITILEventServiceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin;

class ITILEventServiceEventHookMapper implements EventSubscriberInterface
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
            ITILEventServiceEvent::SERVICE_PROBLEM          => 'doServiceProblemHook',
            ITILEventServiceEvent::SERVICE_RECOVERY         => 'doServiceRecoveryHook',
            ITILEventServiceEvent::SERVICE_ACKNOWLEDGE      => 'doServiceAcknowledgeHook',
            ITILEventServiceEvent::SERVICE_ENABLE           => 'doServiceEnableHook',
            ITILEventServiceEvent::SERVICE_DISABLE          => 'doServiceDisableHook',
            ITILEventServiceEvent::SERVICE_START_FLAPPING   => 'doServiceStartFlappingHook',
            ITILEventServiceEvent::SERVICE_STOP_FLAPPING    => 'doServiceStopFlappingHook',
            ITILEventServiceEvent::SERVICE_DISABLE_FLAPPING => 'doServiceDisableFlappingHook',
        ];
    }

    /**
     * Call 'itileventservice_problem' hook.
     *
     * @param ITILEventServiceEvent $event
     *
     * @return void
     */
    public function doServiceProblemHook(ITILEventServiceEvent $event)
    {
        $this->plugin->doHook('itileventservice_problem', $event->getService());
    }

    /**
     * Call 'itileventservice_recovery' hook.
     *
     * @param ITILEventServiceEvent $event
     *
     * @return void
     */
    public function doServiceRecoveryHook(ITILEventServiceEvent $event)
    {
        $this->plugin->doHook('itileventservice_recovery', $event->getService());
    }

    /**
     * Call 'itileventservice_acknowledge' hook.
     *
     * @param ITILEventServiceEvent $event
     *
     * @return void
     */
    public function doServiceAcknowledgeHook(ITILEventServiceEvent $event)
    {
        $this->plugin->doHook('itileventservice_acknowledge', $event->getService());
    }

    /**
     * Call 'itileventservice_enable' hook.
     *
     * @param ITILEventServiceEvent $event
     *
     * @return void
     */
    public function doServiceEnableHook(ITILEventServiceEvent $event)
    {
        $this->plugin->doHook('itileventservice_enable', $event->getService());
    }

    /**
     * Call 'itileventservice_disable' hook.
     *
     * @param ITILEventServiceEvent $event
     *
     * @return void
     */
    public function doServiceDisableHook(ITILEventServiceEvent $event)
    {
        $this->plugin->doHook('itileventservice_disable', $event->getService());
    }

    /**
     * Call 'itileventservice_start_flapping' hook.
     *
     * @param ITILEventServiceEvent $event
     *
     * @return void
     */
    public function doServiceStartFlappingHook(ITILEventServiceEvent $event)
    {
        $this->plugin->doHook('itileventservice_start_flapping', $event->getService());
    }

    /**
     * Call 'itileventservice_stop_flapping' hook.
     *
     * @param ITILEventServiceEvent $event
     *
     * @return void
     */
    public function doServiceStopFlappingHook(ITILEventServiceEvent $event)
    {
        $this->plugin->doHook('itileventservice_stop_flapping', $event->getService());
    }

    /**
     * Call 'itileventservice_disable_flapping' hook.
     *
     * @param ITILEventServiceEvent $event
     *
     * @return void
     */
    public function doServiceDisableFlappingHook(ITILEventServiceEvent $event)
    {
        $this->plugin->doHook('itileventservice_disable_flapping', $event->getService());
    }
}

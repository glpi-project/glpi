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

use Glpi\Event\SIEMServiceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin;

class SIEMServiceEventHookMapper implements EventSubscriberInterface
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
            SIEMServiceEvent::SERVICE_PROBLEM          => 'doServiceProblemHook',
            SIEMServiceEvent::SERVICE_RECOVERY         => 'doServiceRecoveryHook',
            SIEMServiceEvent::SERVICE_ACKNOWLEDGE      => 'doServiceAcknowledgeHook',
            SIEMServiceEvent::SERVICE_ENABLE           => 'doServiceEnableHook',
            SIEMServiceEvent::SERVICE_DISABLE          => 'doServiceDisableHook',
            SIEMServiceEvent::SERVICE_START_FLAPPING   => 'doServiceStartFlappingHook',
            SIEMServiceEvent::SERVICE_STOP_FLAPPING    => 'doServiceStopFlappingHook',
            SIEMServiceEvent::SERVICE_DISABLE_FLAPPING => 'doServiceDisableFlappingHook',
        ];
    }

    /**
     * Call 'siemservice_problem' hook.
     *
     * @param SIEMServiceEvent $event
     *
     * @return void
     */
    public function doServiceProblemHook(SIEMServiceEvent $event)
    {
        $this->plugin->doHook('siemservice_problem', $event->getService());
    }

    /**
     * Call 'siemservice_recovery' hook.
     *
     * @param SIEMServiceEvent $event
     *
     * @return void
     */
    public function doServiceRecoveryHook(SIEMServiceEvent $event)
    {
        $this->plugin->doHook('siemservice_recovery', $event->getService());
    }

    /**
     * Call 'siemservice_acknowledge' hook.
     *
     * @param SIEMServiceEvent $event
     *
     * @return void
     */
    public function doServiceAcknowledgeHook(SIEMServiceEvent $event)
    {
        $this->plugin->doHook('siemservice_acknowledge', $event->getService());
    }

    /**
     * Call 'siemservice_enable' hook.
     *
     * @param SIEMServiceEvent $event
     *
     * @return void
     */
    public function doServiceEnableHook(SIEMServiceEvent $event)
    {
        $this->plugin->doHook('siemservice_enable', $event->getService());
    }

    /**
     * Call 'siemservice_disable' hook.
     *
     * @param SIEMServiceEvent $event
     *
     * @return void
     */
    public function doServiceDisableHook(SIEMServiceEvent $event)
    {
        $this->plugin->doHook('siemservice_disable', $event->getService());
    }

    /**
     * Call 'siemservice_start_flapping' hook.
     *
     * @param SIEMServiceEvent $event
     *
     * @return void
     */
    public function doServiceStartFlappingHook(SIEMServiceEvent $event)
    {
        $this->plugin->doHook('siemservice_start_flapping', $event->getService());
    }

    /**
     * Call 'siemservice_stop_flapping' hook.
     *
     * @param SIEMServiceEvent $event
     *
     * @return void
     */
    public function doServiceStopFlappingHook(SIEMServiceEvent $event)
    {
        $this->plugin->doHook('siemservice_stop_flapping', $event->getService());
    }

    /**
     * Call 'siemservice_disable_flapping' hook.
     *
     * @param SIEMServiceEvent $event
     *
     * @return void
     */
    public function doServiceDisableFlappingHook(SIEMServiceEvent $event)
    {
        $this->plugin->doHook('siemservice_disable_flapping', $event->getService());
    }
}

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

use Glpi\Event\ScheduledDowntimeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin;

class ScheduledDowntimeEventHookMapper implements EventSubscriberInterface
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
            ScheduledDowntimeEvent::DOWNTIME_START   => 'doScheduledDowntimeStartHook',
            ScheduledDowntimeEvent::DOWNTIME_STOP    => 'doScheduledDowntimeStopHook',
            ScheduledDowntimeEvent::DOWNTIME_CANCEL  => 'doScheduledDowntimeCancelHook',
        ];
    }

    /**
     * Call 'scheduleddowntime_start' hook.
     *
     * @param ScheduledDowntimeEvent $event
     *
     * @return void
     */
    public function doScheduledDowntimeStartHook(ScheduledDowntimeEvent $event)
    {
        $this->plugin->doHook('scheduleddowntime_start', $event->getScheduledDowntime());
    }

    /**
     * Call 'scheduleddowntime_stop' hook.
     *
     * @param ScheduledDowntimeEvent $event
     *
     * @return void
     */
    public function doScheduledDowntimeStopHook(ScheduledDowntimeEvent $event)
    {
        $this->plugin->doHook('scheduleddowntime_stop', $event->getScheduledDowntime());
    }

    /**
     * Call 'scheduleddowntime_cancel' hook.
     *
     * @param ScheduledDowntimeEvent $event
     *
     * @return void
     */
    public function doScheduledDowntimeCancelHook(ScheduledDowntimeEvent $event)
    {
        $this->plugin->doHook('scheduleddowntime_cancel', $event->getScheduledDowntime());
    }
}

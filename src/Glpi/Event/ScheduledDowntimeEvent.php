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

namespace Glpi\Event;

use CommonDBTM;
use Symfony\Component\EventDispatcher\Event;

/**
 * @since 10.0.0
 */
class ScheduledDowntimeEvent extends Event
{
    /**
     * Name of event triggered when a scheduled downtime period starts.
     */
    const DOWNTIME_START = 'scheduleddowntime.start';

    /**
     * Name of event triggered when a scheduled downtime period ends.
     */
    const DOWNTIME_STOP = 'scheduleddowntime.stop';

    /**
     * Name of event triggered when a scheduled downtime is deleted/cancelled.
     */
    const DOWNTIME_CANCEL = 'scheduleddowntime.cancel';

    /**
     * @var ITILEventService
     */
    private $downtime;

    /**
     * @param CommonDBTM $item
     */
    public function __construct(ScheduledDowntime $downtime)
    {
        $this->downtime = $downtime;
    }

    /**
     * ScheduledDowntime on which event applies.
     *
     * @return ScheduledDowntime
     */
    public function getScheduledDowntime(): \ScheduledDowntime
    {
        return $this->downtime;
    }
}

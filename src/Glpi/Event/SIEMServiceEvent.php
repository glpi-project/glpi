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
class SIEMServiceEvent extends Event
{
    /**
     * Name of event triggered when a service enters or continues to be in a warning, exception, or unknown state.
     */
    const SERVICE_PROBLEM = 'siemservice.problem';

    /**
     * Name of event triggered when a service enters an OK state from a warning, exception, or unknown state.
     */
    const SERVICE_RECOVERY = 'siemservice.recovery';

    /**
     * Name of event triggered when a service has a problem and is acknowledged.
     */
    const SERVICE_ACKNOWLEDGE = 'siemservice.acknowledge';

    /**
     * Name of event triggered when a service is enabled on a host (not added).
     */
    const SERVICE_ENABLE = 'siemservice.enable';

    /**
     * Name of event triggered when a service is disabled on a host (not removed).
     */
    const SERVICE_DISABLE = 'siemservice.disable';

    /**
     * Name of event triggered when a service starts flapping.
     */
    const SERVICE_START_FLAPPING = 'siemservice.start_flapping';

    /**
     * Name of event triggered when a service stops flapping.
     */
    const SERVICE_STOP_FLAPPING = 'siemservice.stop_flapping';

    /**
     * Name of event triggered when flap detection is disabled (Service may still be flapping).
     */
    const SERVICE_DISABLE_FLAPPING = 'siemservice.disable_flapping';

    /**
     * @var SIEMService
     */
    private $service;

    private $is_hard_status;

    /**
     * @param CommonDBTM $item
     */
    public function __construct(\SIEMService $service, bool $is_hard_status)
    {
        $this->service = $service;
        $this->is_hard_status = $is_hard_status;
    }

    /**
     * SIEMService on which event applies.
     *
     * @return SIEMService
     */
    public function getService(): \SIEMService
    {
        return $this->service;
    }

    public function isHardStatus(): bool
    {
       return $this->is_hard_status;
    }
}

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
class SIEMHostEvent extends Event
{
    /**
     * Name of event triggered when a host enters a down state.
     */
    const HOST_DOWN = 'siemhost.down';

    /**
     * Name of event triggered when a host enters a up state.
     */
    const HOST_UP = 'siemhost.up';

    /**
     * Name of event triggered when a host becomes unreachable.
     */
    const HOST_UNREACHABLE = 'siemhost.unreachable';

    /**
     * Name of event triggered when a host has a problem and is acknowledged.
     */
    const HOST_ACKNOWLEDGE = 'siemhost.acknowledge';

    /**
     * Name of event triggered when a host starts flapping.
     */
    const HOST_START_FLAPPING = 'siemhost.start_flapping';

    /**
     * Name of event triggered when a host stops flapping.
     */
    const HOST_STOP_FLAPPING = 'siemhost.stop_flapping';

    /**
     * Name of event triggered when flap detection is disabled (Host may still be flapping).
     */
    const HOST_DISABLE_FLAPPING = 'siemhost.disable_flapping';

    /**
     * @var SIEMHost
     */
    private $host;

    private $is_hard_status;

    /**
     * @param CommonDBTM $item
     */
    public function __construct(\SIEMHost $host, bool $is_hard_status = true)
    {
        $this->host = $host;
        $this->is_hard_status = $is_hard_status;
    }

    /**
     * SIEMHost on which event applies.
     *
     * @return SIEMHost
     */
    public function getHost(): \SIEMHost
    {
        return $this->host;
    }

    public function isHardStatus(): bool
    {
       return $this->is_hard_status;
    }
}
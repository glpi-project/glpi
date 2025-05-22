<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\PHPUnit\Tests\Glpi;

trait ITILTrait
{
    /**
     * @param $data array additionnal data (override default data (@see getValidTicketData())
     */
    private function createTicket(array $data = [], array $skip_fields = []): \Ticket
    {
        return $this->createItem(
            \Ticket::class,
            $data + $this->getValidTicketData(),
            $skip_fields
        );
    }

    private function getValidTicketData(): array
    {
        return [
            //            'id' => 0,
            //            'entities_id' => 0,
            'name' => 'ticket name ' . time(),
            //            'date' => null,
            //            'closedate' => null,
            //            'solvedate' => null,
            //            'takeintoaccountdate' => null,
            //            'date_mod' => null,
            //            'users_id_lastupdater' => 0,
            'status' => \CommonITILObject::WAITING,
            //            'users_id_recipient' => 0,
            //            'requesttypes_id' => 0,
            'content' => 'Ticket Example content',
            //            'urgency' => 1,
            //            'impact' => 1,
            //            'priority' => 1,
            //            'itilcategories_id' => 0,
            //            'type' => 1,
            //            'global_validation' => 1,
            //            'slas_id_ttr' => 0,
            //            'slas_id_tto' => 0,
            //            'slalevels_id_ttr' => 0,
            //            'time_to_resolve' => null,
            //            'time_to_own' => null,
            //            'begin_waiting_date' => null,
            //            'sla_waiting_duration' => 0,
            //            'waiting_duration' => 0,
            //            'close_delay_stat' => 0,
            //            'solve_delay_stat' => 0,
            //            'takeintoaccount_delay_stat' => 0,
            //            'actiontime' => 0,
            //            'is_deleted' => 0,
            //            'locations_id' => 0,
            //            'validation_percent' => 0,
            //            'date_creation' => null,
            //            'tickettemplates_id' => 0,
            //            'externalid' => null,
        ];
    }

}

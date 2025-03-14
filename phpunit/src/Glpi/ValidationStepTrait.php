<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use CommonITILValidation;
use Ticket;
use TicketValidation;
use User;
use ValidationStep;

trait ValidationStepTrait
{
    protected function getInitialDefaultValidationStep(): ValidationStep
    {
        return getItemByTypeName(ValidationStep::class, 'Validation');
    }

    /**
     * @return array{\Ticket, \ValidationStep}
     */
    private function createValidationStepWithValidations(int $mininal_required_validation_percent, array $validations_statuses): array
    {
        $ticket = $this->createItem(Ticket::class, ['name' => __METHOD__, 'content' => __METHOD__,]);
        $validation_step = $this->addValidationStepWithValidations($mininal_required_validation_percent, $validations_statuses, $ticket);

        // $ticket status changes because of added validation : reload it
        $ticket->getFromDB($ticket->getID());

        return [$ticket, $validation_step];
    }

    private function addValidationStepWithValidations(int $mininal_required_validation_percent, array $validations_statuses, Ticket $ticket): ValidationStep
    {
        // create validation step
        $validation_step = $this->createValidationStep($mininal_required_validation_percent);

        foreach ($validations_statuses as $status) {
            // ticket validation can only be created with Waiting status
            $validation = $this->createItem(TicketValidation::class, $this->getValidTicketValidationData($ticket, $validation_step, CommonITILValidation::WAITING));
            // update status if needed
            if ($status != CommonITILValidation::WAITING) {
                assert($validation->update(['status' => $status] + $validation->fields));
            }
        }

        return $validation_step;
    }

    /**
     * @param int $mininal_required_validation_percent
     */
    private function createValidationStep(int $mininal_required_validation_percent): ValidationStep
    {
        $data = $this->getValidValidationStepData();
        $data['minimal_required_validation_percent'] = $mininal_required_validation_percent;

        return $this->createItem(ValidationStep::class, $data);
    }

    /**
     * Fields for a valid validation step
     *
     * @return array<string, mixed>
     */
    private function getValidValidationStepData(): array
    {
        return [
            'name' => 'Tech team',
            'minimal_required_validation_percent' => 100,
        ];
    }

    public function getValidTicketValidationData(Ticket $ticket, ValidationStep $validation_step, int $validation_status): array
    {
        return [
            'tickets_id' => $ticket->getID(),
            'itemtype_target' => 'User',
            'items_id_target' => getItemByTypeName(User::class, TU_USER)->getID(),
            'validationsteps_id' => $validation_step->getID(),
            'status' => $validation_status,
        ];
    }

    private function getValidationFailureMessage(int $expected_status, int $result): string
    {
        $status_to_label = function (int $status) {
            $states = [
                CommonITILValidation::WAITING => 'WAITING',
                CommonITILValidation::ACCEPTED => 'ACCEPTED',
                CommonITILValidation::REFUSED => 'REFUSED',
            ];
            return $states[$status] ?? throw new \InvalidArgumentException("Unexpected status to display : " . var_export($status, true));
        };

        return 'Unexpected validation step status. Expected : ' . $status_to_label($expected_status) . ' - Result : ' . $status_to_label($result);
    }
}

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

use ChangeValidationStep;
use CommonITILObject;
use CommonITILValidation;
use InvalidArgumentException;
use Ticket;
use TicketValidation;
use TicketValidationStep;
use User;
use ValidationStep;

trait ValidationStepTrait
{
    protected function getInitialDefaultValidationStep(): ValidationStep
    {
        return getItemByTypeName(ValidationStep::class, 'Validation');
    }

    /**
     * @return array{\Ticket|\Change, \TicketValidationStep|\ChangeValidationStep}
     *
     * Also create the related itilobject
     */
    private function createITILSValidationStepWithValidations(ValidationStep $validation_step, array $validations_statuses, ?int $expected_status = null, ?string $itil_classname = null): array
    {
        $itil_classname ??= $this->getITILClassname();
        $itil = $this->createItem($itil_classname, ['name' => __METHOD__, 'content' => __METHOD__,]);
        $itils_validation_step = $this->addITILValidationStepWithValidations($validation_step, $validations_statuses, $itil, $expected_status);

        // $itil status changes because of added validation : reload it
        $itil->getFromDB($itil->getID());

        return [$itil, $itils_validation_step];
    }

    private function addITILValidationStepWithValidations(
        ValidationStep $validation_step,
        array $validations_statuses,
        CommonITILObject $itil,
        ?int $expected_status = null
    ): TicketValidationStep|ChangeValidationStep {
        assert(!empty($validations_statuses), '$validations_statuses must not be empty');

        $itil_validationstep_id = null;
        foreach ($validations_statuses as $status) {
            // itil validation can only be created with Waiting status
            $validation = $this->createItem(
                $itil::getValidationClassName(),
                $this->getValidITILValidationData($itil, $validation_step, CommonITILValidation::WAITING)
            );
            // update status if needed
            if ($status != CommonITILValidation::WAITING) {
                $this->updateItem(
                    $validation::class,
                    $validation->getID(),
                    ['status' => $status, 'comment_validation' => 'validation comment']
                );
            }

            // ensure validations are created with the same itils_validation_step, real assertion hidden here.
            if (!is_null($itil_validationstep_id)) {
                $this->assertEquals($validation->fields['itils_validationsteps_id'], $itil_validationstep_id, 'All Validations must be created with the same itils_validation_step');
            }
            $itil_validationstep_id = $validation->fields['itils_validationsteps_id'];
        }

        $ivs = $itil::getValidationStepInstance();
        // rely on the last created validation, not a problem,
        // the itils_validation_step is the same for all validations because we use a single validation step
        $ivs->getFromDB($validation->fields['itils_validationsteps_id']);

        // check created itil validation step has the exepected status
        // expected status is explicitely given in argument
        if (!is_null($expected_status)) {
            $checked_status = $ivs::getITILValidationStepStatus($ivs->getID());
            assert($expected_status === $checked_status, 'failed to create itil_validation step with status ' . $this->statusToLabel($expected_status) . ' it has status ' . $this->statusToLabel($checked_status));
        } elseif (count($validations_statuses) === 1 && $validations_statuses[0] !== CommonITILValidation::NONE) {
            // expected status is implicitely the only status given in argument (except NONE)
            $checked_status = $ivs::getITILValidationStepStatus($ivs->getID());
            assert($validations_statuses[0] === $checked_status, 'failed to create itil_validation step with status ' . $this->statusToLabel($validations_statuses[0]) . ' it has status ' . $this->statusToLabel($checked_status));
        }

        return $ivs;
    }

    private function createValidationStep(int $mininal_required_validation_percent): ValidationStep
    {
        $data = $this->getValidValidationStepData();
        $data['minimal_required_validation_percent'] = $mininal_required_validation_percent;

        return $this->createItem(ValidationStep::class, $data);
    }

    /**
     * Update an existing ITIL_ValidationStep related to a Change with a new validation percent
     *
     * @param \CommonITILValidation $validation
     * @param int $validation_percent
     * @return void
     */
    protected function updateITIL_ValidationStepOfItil(CommonITILValidation $validation, int $validation_percent): void
    {
        $itils_validationsteps_id = $validation->fields['itils_validationsteps_id'];
        $itils_validationsteps = $validation::$itemtype::getValidationStepClassName();
        $this->updateItem($itils_validationsteps, $itils_validationsteps_id, ['minimal_required_validation_percent' => $validation_percent]);
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

    /**
     * Data for itil validation step creation (->update() method)
     *
     * @param \CommonITILObject $itil
     * @param ValidationStep $validation_step
     * @param int $validation_status
     * @return array
     */
    public function getValidITILValidationData(
        CommonITILObject $itil,
        ValidationStep $validation_step,
        int $validation_status
    ): array {
        return [
            $itil::getForeignKeyField() => $itil->getID(),
            'itemtype_target' => 'User',
            'items_id_target' => getItemByTypeName(User::class, TU_USER)->getID(),
            '_validationsteps_id' => $validation_step->getID(),
            'status' => $validation_status,
            'comment_validation' => 'validation comment'
        ];
    }

    protected function assertValidationStatusEquals(int $expected_status, int $result_status): void
    {
        $this->assertEquals(
            $expected_status,
            $result_status,
            'Unexpected validation step status. Expected : ' . $this->statusToLabel($expected_status) . ' - Result : ' . $this->statusToLabel($result_status)
        );
    }

    protected function statusToLabel(int $status): string
    {
        $states = [
            CommonITILValidation::NONE => 'NONE',
            CommonITILValidation::WAITING => 'WAITING',
            CommonITILValidation::ACCEPTED => 'ACCEPTED',
            CommonITILValidation::REFUSED => 'REFUSED',
        ];

        return $states[$status] ?? throw new InvalidArgumentException("Unexpected status to convert to human readable label : " . var_export($status, true));
    }
}

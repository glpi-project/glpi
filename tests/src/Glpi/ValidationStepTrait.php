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

namespace Glpi\Tests\Glpi;

use CommonITILObject;
use CommonITILValidation;
use InvalidArgumentException;
use User;
use ValidationStep;

trait ValidationStepTrait
{
    protected function getInitialDefaultValidationStep(): ValidationStep
    {
        return getItemByTypeName(ValidationStep::class, 'Approval');
    }

    /**
     * @return array{\Ticket|\Change, \TicketValidationStep|\ChangeValidationStep}
     * @param ValidationStep $validation_step
     * @param array<int, int> $validations_statuses CommonITILValidation:: statuses constants
     * @param int|null $expected_status Expected status of the created ITIL_ValidationStep
     * @param string|null $itil_classname Class name of the ITIL object to create (e.g. Ticket, Change)
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
    ): \ITIL_ValidationStep {
        assert(!empty($validations_statuses), '$validations_statuses must not be empty');

        $ivs_crit = [
            'itemtype' => $itil::class,
            'items_id' => $itil->getID(),
            'validationsteps_id' => $validation_step->getID(),
        ];

        $ivs = $itil::getValidationStepInstance();
        if (!$ivs->getFromDbByCrit($ivs_crit)) {
            $ivs = $this->createItem(
                $itil::getValidationStepClassName(),
                $ivs_crit + [
                    'minimal_required_validation_percent' => $validation_step->fields['minimal_required_validation_percent'],
                ]
            );
        }

        foreach ($validations_statuses as $status) {
            // itil validation can only be created with Waiting status
            $validation = $this->createItem(
                $itil::getValidationClassName(),
                [
                    $itil::getForeignKeyField() => $itil->getID(),
                    'itemtype_target' => 'User',
                    'items_id_target' => getItemByTypeName(User::class, TU_USER, true),
                    'itils_validationsteps_id' => $ivs->getID(),
                    'status' => CommonITILValidation::WAITING,
                    'comment_validation' => 'validation comment',
                ],
                [
                    $itil::getForeignKeyField(),
                ]
            );
            // update status if needed
            if ($status != CommonITILValidation::WAITING) {
                $this->updateItem(
                    $validation::class,
                    $validation->getID(),
                    ['status' => $status, 'comment_validation' => 'validation comment']
                );
            }
        }

        // check created itil validation step has the exepected status
        // expected status is explicitely given in argument
        if (!is_null($expected_status)) {
            $checked_status = $ivs->getStatus();
            assert($expected_status === $checked_status, 'failed to create itil_validation step with status ' . $this->statusToLabel($expected_status) . ' it has status ' . $this->statusToLabel($checked_status));
        } elseif (count($validations_statuses) === 1 && $validations_statuses[0] !== CommonITILValidation::NONE) {
            // expected status is implicitely the only status given in argument (except NONE)
            $checked_status = $ivs->getStatus();
            assert($validations_statuses[0] === $checked_status, 'failed to create itil_validation step with status ' . $this->statusToLabel($validations_statuses[0]) . ' it has status ' . $this->statusToLabel($checked_status));
        }

        return $ivs;
    }

    private function createValidationStepTemplate(int $mininal_required_validation_percent): ValidationStep
    {
        $data = $this->getValidValidationStepData();
        $data['minimal_required_validation_percent'] = $mininal_required_validation_percent;

        return $this->createItem(ValidationStep::class, $data);
    }

    /**
     * Update an existing ITIL_ValidationStep related to a Change with a new validation percent
     *
     * @param CommonITILValidation $validation
     * @param int $validation_percent
     * @return void
     */
    protected function updateITIL_ValidationStepOfItil(CommonITILValidation $validation, int $validation_percent): void
    {
        $itils_validationsteps_id = $validation->fields['itils_validationsteps_id'];
        $itils_validationsteps = $validation::getItilObjectItemType()::getValidationStepClassName();
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

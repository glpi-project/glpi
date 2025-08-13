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

use Glpi\Application\View\TemplateRenderer;

abstract class ITIL_ValidationStep extends CommonDBChild
{
    public $dohistory              = false;

    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    /**
     * Related validation class name.
     *
     * @var class-string<CommonITILValidation>
     */
    protected static string $validation_classname;

    public static function getTable($classname = null)
    {
        return $classname
            ? parent::getTable($classname)
            : parent::getTable(ITIL_ValidationStep::class);
    }

    public static function canCreate(): bool
    {
        return static::$validation_classname::canCreate();
    }

    public static function canUpdate(): bool
    {
        // Update right does not exist on ChangeValidation and TicketValidation, use create right
        return static::$validation_classname::canCreate();
    }

    public static function canView(): bool
    {
        // View right does not exist on ChangeValidation and TicketValidation, use create right
        return static::$validation_classname::canCreate();
    }

    public static function canPurge(): bool
    {
        // Cannot be deleted
        return false;
    }

    #[Override()]
    public function cleanDBonPurge()
    {
        $validation = getItemForItemtype(static::$validation_classname);
        $validation->deleteByCriteria([
            $this->fields['itemtype']::getForeignKeyField() => $this->fields['items_id'],
        ]);

        parent::cleanDBonPurge();
    }

    /**
     * Post Update
     *
     * If minimal_required_validation_percent has changed : recompute Tickets validation status
     */
    public function post_updateItem($history = true)
    {
        // if minimal_required_validation_percent has changed : recompute Ticket validation status
        if (
            isset($this->oldvalues['minimal_required_validation_percent'])
            && $this->oldvalues['minimal_required_validation_percent'] !== $this->fields['minimal_required_validation_percent']
        ) {
            $itil = $this->getItem();
            if (!($itil instanceof CommonITILObject)) {
                throw new RuntimeException();
            }

            $new_status = static::getValidationStatusForITIL($itil);

            if ($itil->fields['global_validation'] !== $new_status) {
                if (
                    !$itil->update(
                        [
                            'id' => $itil->getID(),
                            'global_validation' => $new_status,
                            '_from_itilvalidation' => true, // mandatory to allow modification of global_validation @see \CommonITILObject::handleTemplateFields()
                        ]
                    )
                ) {
                    throw new RuntimeException();
                }
            }
        }
        parent::post_updateItem($history);
    }

    /**
     * Validation status computed from all the attached validations.
     *
     * @return CommonITILValidation::WAITING|CommonITILValidation::ACCEPTED|CommonITILValidation::REFUSED
     */
    public function getStatus(): int
    {
        $required_percent = $this->fields['minimal_required_validation_percent'];

        $achievements = $this->getAchievements();
        // special case for 0% required validation
        if ($required_percent == 0) {
            if ($achievements[CommonITILValidation::ACCEPTED] > 0) {
                return CommonITILValidation::ACCEPTED;
            }
            if ($achievements[CommonITILValidation::REFUSED] > 0) {
                return CommonITILValidation::REFUSED;
            }
            return CommonITILValidation::WAITING;
        }

        // required validation threshold is reached
        if ($achievements[CommonITILValidation::ACCEPTED] >= $required_percent) {
            return CommonITILValidation::ACCEPTED;
        }
        // required validation threshold can be reached
        if ($achievements[CommonITILValidation::ACCEPTED] + $achievements[CommonITILValidation::WAITING] >= $required_percent) {
            return CommonITILValidation::WAITING;
        }

        return CommonITILValidation::REFUSED;
    }

    /**
     * Validation step achievements by status.
     *
     * In case of non integer percentages, values will be rounded down (floor) and one of the status will get a have a higher percentage to reach 100%.
     * The affected status is the one with a non-zero value, the highest decimal part and comming first in the list of statuses (accepted at the moment).
     *
     * @return array{2: float, 3: float, 4: float} array keys are the status constants
     */
    public function getAchievements(): array
    {
        $validations = getItemForItemtype(static::$validation_classname)->find([
            'itils_validationsteps_id' => $this->getID(),
        ]);

        $validations_count = count($validations);

        if ($validations_count === 0) {
            return [
                CommonITILValidation::ACCEPTED => 0,
                CommonITILValidation::REFUSED => 0,
                CommonITILValidation::WAITING => 0,
            ];
        }

        $count_by_status = fn($status) => count(array_filter($validations, fn($v) => $v["status"] === $status));

        $result = [
            CommonITILValidation::ACCEPTED => $count_by_status(CommonITILValidation::ACCEPTED) / $validations_count * 100,
            CommonITILValidation::REFUSED => $count_by_status(CommonITILValidation::REFUSED) / $validations_count * 100,
            CommonITILValidation::WAITING => $count_by_status(CommonITILValidation::WAITING) / $validations_count * 100,
        ];

        return $result;
    }

    /**
     * @param CommonITILObject $itil
     * @return int
     */
    public static function getValidationStatusForITIL(CommonITILObject $itil): int
    {
        $validation_steps_status = static::getValidationStepsStatus($itil);

        // No validation for the ticket -> NONE
        if ($validation_steps_status === []) {
            return CommonITILValidation::NONE;
        }
        // One validation step is REFUSED -> REFUSED
        $has_refused = !empty(array_filter($validation_steps_status, fn($status) => $status === CommonITILValidation::REFUSED));
        if ($has_refused) {
            return CommonITILValidation::REFUSED;
        }

        // One validation step is WAITING -> WAITING
        $has_waiting = !empty(array_filter($validation_steps_status, fn($status) => $status === CommonITILValidation::WAITING));
        if ($has_waiting) {
            return CommonITILValidation::WAITING;
        }

        // All validation steps are ACCEPTED -> ACCEPTED
        return CommonITILValidation::ACCEPTED;
    }

    /**
     * Array of itils_validationsteps status for an itil
     *
     * Return each step status for an itil in an array
     *
     * @param CommonITILObject $itil
     * @return int[] array of validation steps status : ComomITILValidation::WAITING|ComomITILValidation::ACCEPTED|ComomITILValidation::REFUSED
     */
    public static function getValidationStepsStatus(CommonITILObject $itil): array
    {
        // find all validations id related to the itil
        $validation_steps = $itil->getValidationStepInstance()->find([
            'itemtype' => $itil::class,
            'items_id' => $itil->getID(),
        ]);
        // find all itils_validationsteps_id related to the validations
        $validationstep_ids = array_column($validation_steps, 'id');

        $result = [];
        foreach ($validationstep_ids as $validationstep_id) {
            $itil_vs = new static();
            if (!$itil_vs->getFromDB($validationstep_id)) {
                throw new RuntimeException();
            }

            $result[$validationstep_id] = $itil_vs->getStatus();
        }

        return $result;
    }

    public function getFormFields(): array
    {
        return ['minimal_required_validation_percent'];
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);

        TemplateRenderer::getInstance()->display(
            'components/itilobject/validationstep.html.twig',
            [
                'item'   => $this,
                'params' => $options,
                'no_header' => true,
            ]
        );
        return true;
    }
}

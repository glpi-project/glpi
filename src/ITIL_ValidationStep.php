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

abstract class ITIL_ValidationStep extends CommonDBTM
{
    public $dohistory              = false;

    /**
     * @var class-string<CommonITILValidation>
     * To override in child classes
     */
    protected static string $validation_classname = TicketValidation::class;

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

    /**
     * Update right does not exist on ChangeValidation and TicketValidation, use create right
     */
    public static function canUpdate(): bool
    {
        return static::$validation_classname::canCreate();
    }

    /**
     * View right does not exist on ChangeValidation and TicketValidation, use create right
     */
    public static function canView(): bool
    {
        return static::$validation_classname::canCreate();
    }

    /**
     * ValidationSteps cannot be deleted
     */
    public static function canPurge(): bool
    {
        return false;
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
            // find if these itil validation steps are used in ticket validation or in change validation
            $validation = new (static::$validation_classname);
            $validations = $validation->find(['itils_validationsteps_id' => $this->getID()]);
            $itils_id = array_unique(array_column($validations, $validation::$itemtype::getForeignKeyField()));

            foreach ($itils_id as $itil_id) {
                $itil = (new $validation::$itemtype())->getByID($itil_id);
                $vs = $itil::getValidationStepInstance();
                $new_status = $vs::getValidationStatusForITIL($itil);

                if ($itil->fields['global_validation'] !== $new_status) {
                    if (
                        !$itil->update(
                            [
                                'id' => $itil->getID(),
                                'global_validation' => $new_status,
                                '_from_itilvalidation' => true // mandatory to allow modification of global_validation @see \CommonITILObject::handleTemplateFields()
                            ]
                        )
                    ) {
                        Session::addMessageAfterRedirect(msg: 'Failed to update related ' . $validation::$itemtype . ' global validation status on Itil #' . $itil->getID(), message_type: ERROR);
                    }
                }
            }
        }
        parent::post_updateItem($history);
    }

    /**
     * Validation status for an itil validation step
     *
     * @param int $itils_validationsteps_id
     *
     * @return int CommonITILValidation::WAITING|CommonITILValidation::ACCEPTED|CommonITILValidation::REFUSED
     */
    public static function getITILValidationStepStatus(int $itils_validationsteps_id): int
    {
        // get Validation step $required_percent
        $vs = new static();
        if (!$vs->getFromDB($itils_validationsteps_id)) {
            throw new InvalidArgumentException('ITILValidation step not found #' . $itils_validationsteps_id);
        }

        $required_percent = $vs->fields['minimal_required_validation_percent'];

        $achievements = static::getITILValidationStepAchievements($itils_validationsteps_id);
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
     * Get validation step achievements by status for a ticket
     *
     * In case of non integer percentages, values will be rounded down (floor) and one of the status will get a have a higher percentage to reach 100%.
     * The affected status is the one with a non-zero value, the highest decimal part and comming first in the list of statuses (accepted at the moment).
     *
     * @param int $itils_validationsteps_id
     *
     * @return array{2: int, 3: int, 4: int} array keys are the status constants
     */
    public static function getITILValidationStepAchievements(int $itils_validationsteps_id): array
    {
        $validations = static::getValidationsForITILValidationStep($itils_validationsteps_id);
        $validations_count = count($validations);

        $count_by_status = fn($status) => count(array_filter($validations, fn($v) => $v["status"] === $status));

        $exact_percentages = [
            CommonITILValidation::ACCEPTED => $count_by_status(CommonITILValidation::ACCEPTED) / $validations_count * 100,
            CommonITILValidation::REFUSED => $count_by_status(CommonITILValidation::REFUSED) / $validations_count * 100,
            CommonITILValidation::WAITING => $count_by_status(CommonITILValidation::WAITING) / $validations_count * 100
        ];

        // result with rounded percentages
        $result = [
            CommonITILValidation::ACCEPTED => (int)$exact_percentages[CommonITILValidation::ACCEPTED],
            CommonITILValidation::REFUSED => (int)$exact_percentages[CommonITILValidation::REFUSED],
            CommonITILValidation::WAITING => (int)$exact_percentages[CommonITILValidation::WAITING]
        ];

        // because of rounding, the sum of the percentages may not be 100
        // -> adjust the result to have a sum of 100 by adding the difference to the status with the highest decimal part
        $sum = array_sum($result);
        $difference = 100 - $sum;

        if ($difference > 0) {
            // compute difference for each status
            $decimal_parts = [];
            foreach ($exact_percentages as $status => $value) {
                $decimal_parts[$status] = $value - floor($value);
            }

            // sort by decimal part in descending order
            arsort($decimal_parts);

            // add the difference to the status with the highest decimal part (avoiding statuses with 0%)
            foreach ($decimal_parts as $status => $decimal_part) {
                if ($exact_percentages[$status] > 0) {
                    $result[$status] += $difference;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param int $itils_validationsteps_id
     * @return CommonITILValidation[]
     */
    protected static function getValidationsForITILValidationStep(int $itils_validationsteps_id): array
    {
        // collect all related validations in TicketValidation, ChangeValidation, etc.
        return (new static::$validation_classname())->find([
            'itils_validationsteps_id' => $itils_validationsteps_id
        ]);
    }

    /**
     * @param \CommonITILObject $itil
     * @return int
     */
    public static function getValidationStatusForITIL(CommonITILObject $itil): int
    {
        $validation_steps_status = static::getValidationStepsStatus($itil);

        // No validation for the ticket -> NONE
        if (empty($validation_steps_status)) {
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
     * @param \CommonITILObject $itil
     * @return int[] array of validation steps status : ComomITILValidation::WAITING|ComomITILValidation::ACCEPTED|ComomITILValidation::REFUSED
     */
    public static function getValidationStepsStatus(CommonITILObject $itil): array
    {
        [$itil_id_field, $itil_validation_classname] = match (get_class($itil)) {
            Ticket::class => ['tickets_id', TicketValidation::class],
            Change::class => ['changes_id', ChangeValidation::class],
            default => throw new InvalidArgumentException('Unsupported ITIL object type')
        };

        // find all validations id related to the itil
        $validations = (new $itil_validation_classname())->find([
            $itil_id_field => $itil->getID(),
        ]);
        // find all itils_validationsteps_id related to the validations
        $itils_validationstep_ids = array_unique(array_column($validations, 'itils_validationsteps_id'));

        return array_map(
            fn($itil_vs_id) => static::getITILValidationStepStatus($itil_vs_id),
            $itils_validationstep_ids
        );
    }
}

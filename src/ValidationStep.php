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

class ValidationStep extends \CommonDropdown
{
    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Approval step', 'Approval steps', $nb);
    }

    /**
     * Get validation step achievements by status for a ticket
     *
     * In case of non integer percentages, values will be rounded down (floor) and one of the status will get a have a higher percentage to reach 100%.
     * The affected status is the one with a non-zero value, the highest decimal part and comming first in the list of statuses (accepted at the moment).
     *
     * @return array{2: int, 3: int, 4: int} array keys are the status constants
     */
    public static function getValidationStepAchievements(int $ticket_id, int $validationstep_id): array
    {
        $validations = self::getValidationsForTicketAndValidationStep($ticket_id, $validationstep_id);
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
     * @return int TicketValidation::WAITING|TicketValidation::ACCEPTED|TicketValidation::REFUSED
     */
    public static function getValidationStepStatusForTicket(int $ticket_id, int $validationstep_id): int
    {
        // get Validation step $required_percent
        $vs = new ValidationStep();
        $vs->getFromDB($validationstep_id);
        $required_percent = $vs->getField('minimal_required_validation_percent');

        $achievements = self::getValidationStepAchievements($ticket_id, $validationstep_id);
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
     * @param int $ticket_id
     * @param int $validationstep_id
     * @return TicketValidation[]
     */
    private static function getValidationsForTicketAndValidationStep(int $ticket_id, int $validationstep_id): array
    {
        // collect all validation for the ticket with the given validation step
        $validations = (new TicketValidation())->find([
            'tickets_id' => $ticket_id,
            'validationsteps_id' => $validationstep_id
        ]);

        // @todo if no validation found, throw an exception ? return false ?
        if (empty($validations)) {
            throw new \LogicException('Get validation step status for a ticket without any validation step');
        }

        return $validations;
    }

    /**
     * @param Ticket $ticket
     *
     * No validation for the ticket -> NONE
     * One validation step is REFUSED -> REFUSED
     * One validation step is WAITING -> WAITING
     * All validation steps are ACCEPTED -> ACCEPTED
     *
     * @return int Validation status
     */
    public static function getValidationStatusForTicket(Ticket $ticket): int
    {
        $validation_steps_status = self::getValidationStepsStatus($ticket);

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
     * Array of validation steps status for a ticket
     *
     * Return each step status for a ticket in an array
     *
     * @param \Ticket $ticket
     * @return int[] array of validation steps status : ComomITILValidation::WAITING|ComomITILValidation::ACCEPTED|ComomITILValidation::REFUSED
     */
    public static function getValidationStepsStatus(Ticket $ticket): array
    {
        $validation_steps = (new TicketValidation())->find([
            'tickets_id' => $ticket->getID(),
        ]);

        return array_map(
            fn($vs) => self::getValidationStepStatusForTicket($ticket->getID(), $vs['validationsteps_id']),
            $validation_steps
        );
    }

    /**
     * Ensure there is always a default validation step
     * and eventually set it as default.
     */
    public function pre_deleteItem()
    {
        if (count($this->find([])) == 1) {
            return false;
        }

        return parent::pre_deleteItem();
    }

    public function post_purgeItem()
    {
        if ($this->isDefault()) {
            $this->setAnotherAsDefault();
        }

        parent::post_purgeItem();
    }

    public function post_addItem()
    {
        if ($this->isDefault()) {
            $this->removeDefaultToOthers();
        }

        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {
        if ($this->isDefault()) {
            $this->removeDefaultToOthers();
        }

        if (!$this->isDefault() && $this->wasDefault()) {
            $this->setAnotherAsDefault();
        }

        parent::post_updateItem($history);
    }

    public function prepareInputForAdd($input)
    {
        $is_input_valid = true;
        // name is mandatory
        if (!isset($input['name']) || strlen($input['name']) < 3) {
            $message = sprintf(__s('The %s field is mandatory'), $this->getAdditionalField('name')['label'] ?? 'name');
            Session::addMessageAfterRedirect(msg: $message, message_type: ERROR);
            $is_input_valid = false;
        }

        // percent is mandatory and must be a percentage
        if (!isset($input['minimal_required_validation_percent']) || !is_numeric($input['minimal_required_validation_percent']) || $input['minimal_required_validation_percent'] < 0 || $input['minimal_required_validation_percent'] > 100) {
            $message = sprintf(__s('The %s field is mandatory and must be beetween 0 and 100.'), $this->getAdditionalField('minimal_required_validation_percent')['label'] ?? 'minimal_required_validation_percent');
            Session::addMessageAfterRedirect(msg: $message, message_type: ERROR);
            $is_input_valid = false;
        }

        if (!$is_input_valid) {
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        $is_input_valid = true;

        // name is mandatory
        if (isset($input['name']) && strlen($input['name']) < 3) {
            $message = sprintf(__s('The %s field is mandatory'), $this->getAdditionalField('name')['label'] ?? 'name');
            Session::addMessageAfterRedirect(msg: $message, message_type: ERROR);
            $is_input_valid = false;
        }

        // percent is mandatory and must be a percentage
        if (
            isset($input['minimal_required_validation_percent'])
            && (!is_numeric($input['minimal_required_validation_percent']) || $input['minimal_required_validation_percent'] < 0 || $input['minimal_required_validation_percent'] > 100)
        ) {
            $message = sprintf(__s('The %s field is mandatory and must be beetween 0 and 100.'), $this->getAdditionalField('minimal_required_validation_percent')['label'] ?? 'minimal_required_validation_percent');
            Session::addMessageAfterRedirect(msg: $message, message_type: ERROR);
            $is_input_valid = false;
        }

        if (!$is_input_valid) {
            return false;
        }

        return parent::prepareInputForUpdate($input);
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'   => 'is_default',
                'label'  => __('Use as default'),
                'type'   => 'bool',
                'required' => true,
            ],
            [
                'name'  => 'minimal_required_validation_percent',
                'label' => __('Minimal required validation percent'),
                'type'  => 'integer',
                'min'   => 0,
                'max'   => 100
            ],

        ] + parent::getAdditionalFields();
    }


    public static function getDefault(): self
    {
        $vs = new self();
        if (!$vs->getFromDBByCrit(['is_default' => 1])) {
            throw new LogicException('No default validation step found');
        };

        return $vs;
    }

    /**
     * Default Validation steps data
     * Used to populate the database with default values
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getDefaults(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Validation',
                'minimal_required_validation_percent' => 100,
                'is_default' => 1,
                'date_creation' => date('Y-m-d H:i:s'),
                'date_mod' => date('Y-m-d H:i:s'),
                'comment' => '',
            ],
        ];
    }

    private function removeDefaultToOthers(): void
    {
        $all_except_this = $this->find(['is_default' => 1, ['NOT' => ['id' => $this->getID()]]]);
        foreach ($all_except_this as $to_update) {
            $vs = new self();
            $vs->update([
                'id' => $to_update['id'],
                'is_default' => 0
            ]);
        }
    }

    private function setAnotherAsDefault(): void
    {
        $all_except_this = $this->find([['NOT' => ['id' => $this->getID()]]]);
        if (empty($all_except_this)) {
            throw new LogicException('no other validation to set as default but there should always remain a validation step - this should not happen, review the code');
        }
        $first = array_shift($all_except_this);
        (new self())->update([
            'id' => $first['id'],
            'is_default' => 1
        ]);
    }

    private function isDefault(): bool
    {
        return $this->getField('is_default') == 1;
    }

    private function wasDefault(): bool
    {
        return isset($this->oldvalues['is_default']) && $this->oldvalues['is_default'] == 1;
    }
}

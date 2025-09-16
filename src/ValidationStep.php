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


class ValidationStep extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {
        return _n('Approval step', 'Approval steps', $nb);
    }

    public function pre_deleteItem()
    {
        if ($this->isDefault() || $this->isInUsage()) {
            return false;
        }

        return parent::pre_deleteItem();
    }

    public function post_addItem()
    {
        if ($this->isDefault()) {
            $this->removeDefaultFromOthers();
        }

        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {
        if ($this->isDefault()) {
            $this->removeDefaultFromOthers();
        }

        parent::post_updateItem($history);
    }

    public function prepareInputForAdd($input)
    {
        $is_input_valid = true;
        // name is mandatory
        if (!isset($input['name'])) {
            $message = sprintf(
                __s('The %s field is mandatory'),
                htmlescape($this->getAdditionalField('name')['label'] ?? 'name')
            );
            Session::addMessageAfterRedirect(msg: $message, message_type: ERROR);
            $is_input_valid = false;
        }

        // percent is mandatory and must be a percentage
        if (!isset($input['minimal_required_validation_percent']) || !is_numeric($input['minimal_required_validation_percent']) || $input['minimal_required_validation_percent'] < 0 || $input['minimal_required_validation_percent'] > 100) {
            $message = sprintf(
                __s('The %s field is mandatory and must be beetween 0 and 100.'),
                htmlescape($this->getAdditionalField('minimal_required_validation_percent')['label'] ?? 'minimal_required_validation_percent')
            );
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
            $message = sprintf(
                __s('The %s field is mandatory'),
                htmlescape($this->getAdditionalField('name')['label'] ?? 'name')
            );
            Session::addMessageAfterRedirect(msg: $message, message_type: ERROR);
            $is_input_valid = false;
        }

        // percent is mandatory and must be a percentage
        if (
            isset($input['minimal_required_validation_percent'])
            && (!is_numeric($input['minimal_required_validation_percent']) || $input['minimal_required_validation_percent'] < 0 || $input['minimal_required_validation_percent'] > 100)
        ) {
            $message = sprintf(
                __s('The %s field is mandatory and must be beetween 0 and 100.'),
                htmlescape($this->getAdditionalField('minimal_required_validation_percent')['label'] ?? 'minimal_required_validation_percent')
            );
            Session::addMessageAfterRedirect(msg: $message, message_type: ERROR);
            $is_input_valid = false;
        }

        if ($this->isDefault() && !isset($input['_from_post_update'])) {
            // Prevent having no default step
            unset($input['is_default']);
        }

        if (!$is_input_valid) {
            return false;
        }

        return parent::prepareInputForUpdate($input);
    }

    public function canPurgeItem(): bool
    {
        return !$this->isDefault() && !$this->isInUsage();
    }

    public function getAdditionalFields()
    {
        $is_default_select = [
            'name' => 'is_default',
            'label' => __('Use as default'),
            'type' => 'bool',
        ];
        if ($this->isDefault()) {
            $is_default_select['form_params'] = [
                'disabled' => true,
                'tooltip' => __('This is the default approval step, it cannot be changed. Update another step to make it the default one.')];
        }

        $additional_fields[] = $is_default_select;
        $additional_fields[] = [
            'name' => 'minimal_required_validation_percent',
            'label' => __('Minimal required approval percent'),
            'type' => 'integer',
            'min' => 0,
            'max' => 100,
        ];

        return $additional_fields + parent::getAdditionalFields();
    }


    public function rawSearchOptions()
    {
        $tab[] = [
            'id'                => '252',
            'table'             => $this->getTable(),
            'field'             => 'is_default',
            'name'              => __('Default'),
            'datatype'          => 'bool',
            'massiveaction'     => false,
        ];

        return $tab + parent::rawSearchOptions();
    }

    public static function getDefault(): self
    {
        $vs = new self();
        if (!$vs->getFromDBByCrit(['is_default' => 1])) {
            throw new LogicException('No default approval step found');
        };

        return $vs;
    }

    private function removeDefaultFromOthers(): void
    {
        $all_except_this = $this->find(['is_default' => 1, ['NOT' => ['id' => $this->getID()]]]);
        foreach ($all_except_this as $to_update) {
            $vs = new self();
            $vs->update([
                'id' => $to_update['id'],
                'is_default' => 0,
                '_from_post_update' => true, // prevent `is_default` protection on the `prepareInputForUpdate()` method
            ]);
        }
    }

    private function isDefault(): bool
    {
        return isset($this->fields['is_default']) && $this->fields['is_default'] == 1;
    }

    private function isInUsage(): bool
    {
        if (count((new TicketValidationStep())->find([static::getForeignKeyField() => $this->getID()])) > 0) {
            return true;
        }
        if (count((new ChangeValidationStep())->find([static::getForeignKeyField() => $this->getID()])) > 0) {
            return true;
        }

        return false;
    }
}

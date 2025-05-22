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
            $this->removeDefaultFromOthers();
        }

        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {
        if ($this->isDefault()) {
            $this->removeDefaultFromOthers();
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
                'label' => __('Minimal required approval percent'),
                'type'  => 'integer',
                'min'   => 0,
                'max'   => 100,
            ],

        ] + parent::getAdditionalFields();
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
            ]);
        }
    }

    private function setAnotherAsDefault(): void
    {
        $all_except_this = $this->find([['NOT' => ['id' => $this->getID()]]]);
        if (empty($all_except_this)) {
            throw new LogicException('no other approval to set as default but there should always remain a approval step - this should not happen, review the code');
        }
        $first = array_shift($all_except_this);
        (new self())->update([
            'id' => $first['id'],
            'is_default' => 1,
        ]);
    }

    private function isDefault(): bool
    {
        return $this->fields['is_default'] == 1;
    }

    private function wasDefault(): bool
    {
        return isset($this->oldvalues['is_default']) && $this->oldvalues['is_default'] == 1;
    }
}

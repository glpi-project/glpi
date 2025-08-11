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

namespace Glpi\Form\Condition\ConditionHandler;

use CommonDBTM;
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\ValueOperator;
use Override;

use function Safe\preg_match;

final class UserDevicesConditionHandler implements ConditionHandlerInterface
{
    public function __construct(
        private bool $is_multiple_devices = false,
    ) {}

    #[Override]
    public function getSupportedValueOperators(): array
    {
        if ($this->is_multiple_devices) {
            return [
                ValueOperator::AT_LEAST_ONE_ITEM_OF_ITEMTYPE,
                ValueOperator::ALL_ITEMS_OF_ITEMTYPE,
            ];
        } else {
            return [
                ValueOperator::IS_ITEMTYPE,
                ValueOperator::IS_NOT_ITEMTYPE,
            ];
        }
    }

    #[Override]
    public function getTemplate(): string
    {
        return '/pages/admin/form/condition_handler_templates/user_devices_dropdown.html.twig';
    }

    #[Override]
    public function getTemplateParameters(ConditionData $condition): array
    {
        return [
            'is_multiple_devices' => $this->is_multiple_devices,
            'itemtypes'           => array_combine(
                $this->getSupportedDeviceTypes(),
                $this->getSupportedDeviceTypes()
            ),
        ];
    }

    #[Override]
    public function applyValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        if ($this->is_multiple_devices) {
            return $this->applyMultipleDevicesValueOperator($a, $operator, $b);
        } else {
            return $this->applySingleDeviceValueOperator($a, $operator, $b);
        }
    }

    private function applyMultipleDevicesValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        if (!is_array($a) || !is_array($b)) {
            return false;
        }

        // Format follows this pattern: "Computer_1"
        $actual_itemtypes = array_filter(
            array_map(
                fn(string $item) => preg_match('/^([A-Za-z]+)_\d+$/', $item, $matches) ? $matches[1] : null,
                $a
            )
        );

        return match ($operator) {
            ValueOperator::AT_LEAST_ONE_ITEM_OF_ITEMTYPE => array_reduce(
                $actual_itemtypes,
                fn(bool $carry, string $actual_itemtype) => $carry || in_array(
                    $actual_itemtype,
                    $b,
                    true
                ),
                false
            ),
            ValueOperator::ALL_ITEMS_OF_ITEMTYPE => $actual_itemtypes !== [] && array_reduce(
                $actual_itemtypes,
                fn(bool $carry, string $actual_itemtype) => $carry && in_array(
                    $actual_itemtype,
                    $b,
                    true
                ),
                true
            ),

            // Unsupported operators
            default => false,
        };
    }

    private function applySingleDeviceValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        if (!is_string($a) || !is_string($b)) {
            return false;
        }

        // Format follows this pattern: "Computer_1"
        if (preg_match('/^([A-Za-z]+)_\d+$/', $a, $matches)) {
            $actual_itemtype = $matches[1];
        }

        return match ($operator) {
            ValueOperator::IS_ITEMTYPE => isset($actual_itemtype) && $actual_itemtype === $b,
            ValueOperator::IS_NOT_ITEMTYPE => !isset($actual_itemtype) || ($actual_itemtype !== $b),

            // Unsupported operators
            default => false,
        };
    }

    /**
     * Get all device types supported by getMyDevices
     *
     * @return class-string<CommonDBTM>[]
     */
    private function getSupportedDeviceTypes(): array
    {
        global $CFG_GLPI;

        $device_types = [];

        // Collect all device types from configuration arrays
        foreach (['assignable_types', 'software_types', 'directconnect_types'] as $type_key) {
            foreach ($CFG_GLPI[$type_key] as $itemtype) {
                if (class_exists($itemtype)) {
                    $device_types[] = $itemtype;
                }
            }
        }

        return array_unique($device_types);
    }
}

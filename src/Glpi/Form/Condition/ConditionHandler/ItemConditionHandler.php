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
use CommonTreeDropdown;
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Migration\ConditionHandlerDataConverterInterface;
use Override;

use function Safe\json_decode;

final class ItemConditionHandler implements ConditionHandlerInterface, ConditionHandlerDataConverterInterface
{
    use ArrayConditionHandlerTrait;

    /** @param class-string<CommonDBTM> $itemtype */
    public function __construct(
        private string $itemtype,
    ) {}

    #[Override]
    public function getSupportedValueOperators(): array
    {
        return [
            ValueOperator::EQUALS,
            ValueOperator::NOT_EQUALS,
        ];
    }

    #[Override]
    public function getTemplate(): string
    {
        return '/pages/admin/form/condition_handler_templates/item_dropdown.html.twig';
    }

    #[Override]
    public function getTemplateParameters(ConditionData $condition): array
    {
        return ['itemtype' => $this->itemtype];
    }

    #[Override]
    public function applyValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        // During form rendering, applyValueOperator is called to compute questions visibility
        // Default value is used as value
        if (!is_array($a) && json_validate($a)) {
            $a = json_decode($a, true);

            // itemtype key is not provided in the default value, use the one from the question
            $a['itemtype'] = $this->itemtype;
        }

        return $this->applyArrayValueOperator($a, $operator, $b);
    }

    #[Override]
    public function convertConditionValue(string $value): int
    {
        $nameFields = [];
        $item = getItemForItemtype($this->itemtype);
        if ($item instanceof CommonTreeDropdown) {
            $nameFields[] = $item::getCompleteNameField();
        }
        $nameFields[] = $item::getNameField();

        foreach ($nameFields as $nameField) {
            // Retrieve item by name
            if ($item->getFromDBByCrit([$nameField => $value])) {
                return $item->getID();
            }
        }

        return 0;
    }
}

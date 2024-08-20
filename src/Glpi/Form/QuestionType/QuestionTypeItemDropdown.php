<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Form\QuestionType;

use Dropdown;
use Override;
use Session;

final class QuestionTypeItemDropdown extends QuestionTypeItem
{
    #[Override]
    public function __construct()
    {
        parent::__construct();

        $this->itemtype_aria_label = __('Select a dropdown type');
        $this->items_id_aria_label = __('Select a dropdown item');
    }

    public function getAllowedItemtypes(): array
    {
        $dropdown_itemtypes = Dropdown::getStandardDropdownItemTypes();

        /**
         * It is necessary to replace the values with their corresponding keys
         * because the values returned by getStandardDropdownItemTypes() are
         * translations and not item type keys.
         * The array_keys() function is not used because it does not work for nested arrays.
         */
        array_walk_recursive($dropdown_itemtypes, function (&$value, $key) {
            $value = $key;
        });

        return $dropdown_itemtypes;
    }

    #[Override]
    public function getName(): string
    {
        return _n('Dropdown', 'Dropdowns', Session::getPluralNumber());
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-edit';
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }
}

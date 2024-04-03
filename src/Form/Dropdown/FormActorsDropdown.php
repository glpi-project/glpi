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

namespace Glpi\Form\Dropdown;

use AbstractRightsDropdown;
use Group;
use Override;
use Supplier;
use User;

final class FormActorsDropdown extends AbstractRightsDropdown
{
    #[Override]
    protected static function getAjaxUrl(): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return $CFG_GLPI['root_doc'] . "/ajax/getFormQuestionActorsDropdownValue.php";
    }

    #[Override]
    protected static function getTypes(): array
    {
        $allowed_types = [
            User::getType(),
            Group::getType(),
            Supplier::getType(),
        ];

        if (isset($_POST['allowed_types'])) {
            $allowed_types = array_intersect($allowed_types, $_POST['allowed_types']);
        }

        return $allowed_types;
    }

    #[Override]
    public static function show(string $name, array $values, array $params = []): string
    {
        $params['width'] = '100%';

        return parent::show($name, $values, $params);
    }
}

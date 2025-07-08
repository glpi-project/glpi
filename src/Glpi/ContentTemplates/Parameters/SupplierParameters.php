<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Supplier;

/**
 * Parameters for "Supplier" items.
 *
 * @since 10.0.0
 */
class SupplierParameters extends TreeDropdownParameters
{
    public static function getDefaultNodeName(): string
    {
        return 'supplier';
    }

    public static function getObjectLabel(): string
    {
        return Supplier::getTypeName(1);
    }

    protected function getTargetClasses(): array
    {
        return [Supplier::class];
    }

    public function getAvailableParameters(): array
    {
        return [
            new AttributeParameter("id", __('ID')),
            new AttributeParameter("name", __('Name')),
            new AttributeParameter("address", __('Address')),
            new AttributeParameter("city", __('City')),
            new AttributeParameter("postcode", __('Postal code')),
            new AttributeParameter("state", _x('location', 'State')),
            new AttributeParameter("country", __('Country')),
            new AttributeParameter("phone", _n('Phone', 'Phones', 1)),
            new AttributeParameter("fax", __('Fax')),
            new AttributeParameter("email", _n('Email', 'Emails', 1)),
            new AttributeParameter("website", __('Website')),
        ];
    }

    protected function defineValues(CommonDBTM $user): array
    {
        $fields = $user->fields;

        return [
            'id'        => $fields['id'],
            'name'      => $fields['name'],
            'address'   => $fields['address'],
            'city'      => $fields['town'],
            'postcode'  => $fields['postcode'],
            'state'     => $fields['state'],
            'country'   => $fields['country'],
            'phone'     => $fields['phonenumber'],
            'fax'       => $fields['fax'],
            'email'     => $fields['email'],
            'website'   => $fields['website'],
        ];
    }
}

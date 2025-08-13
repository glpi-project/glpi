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
use Glpi\ContentTemplates\Parameters\ParametersTypes\ArrayParameter;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Glpi\ContentTemplates\Parameters\ParametersTypes\ObjectParameter;
use Location;
use User;
use UserCategory;
use UserEmail;
use UserTitle;

/**
 * Parameters for "User" items.
 *
 * @since 10.0.0
 */
class UserParameters extends AbstractParameters
{
    public static function getDefaultNodeName(): string
    {
        return 'user';
    }

    public static function getObjectLabel(): string
    {
        return User::getTypeName(1);
    }

    protected function getTargetClasses(): array
    {
        return [User::class];
    }

    public function getAvailableParameters(): array
    {
        return [
            new AttributeParameter("id", __('ID')),
            new AttributeParameter("login", __('Login')),
            new AttributeParameter("fullname", __('Full name')),
            new AttributeParameter("email", _n('Email', 'Emails', 1)),
            new AttributeParameter("phone", _n('Phone', 'Phones', 1)),
            new AttributeParameter("phone2", __('Phone 2')),
            new AttributeParameter("mobile", __('Mobile')),
            new AttributeParameter("firstname", __('First name')),
            new AttributeParameter("realname", __('Surname')),
            new AttributeParameter("responsible", __('Supervisor')),
            new ObjectParameter(new LocationParameters()),
            new ObjectParameter(new UserTitleParameters()),
            new ObjectParameter(new UserCategoryParameters()),
            new ArrayParameter('used_items', new AssetParameters(), "Used items"),
        ];
    }

    protected function defineValues(CommonDBTM $user): array
    {
        global $CFG_GLPI;

        $fields = $user->fields;

        $values = [
            'id'        => $fields['id'],
            'login'     => $fields['name'],
            'fullname'  => $user->getFriendlyName(),
            'email'     => UserEmail::getDefaultForUser($fields['id']),
            'phone'     => $fields['phone'],
            'phone2'    => $fields['phone2'],
            'mobile'    => $fields['mobile'],
            'firstname' => $fields['firstname'],
            'realname'  => $fields['realname'],
        ];

        // Add responsible
        if ($responsible = User::getById($fields['users_id_supervisor'])) {
            $values['responsible'] = $responsible->getFriendlyName();
        }

        // Add location
        if ($location = Location::getById($fields['locations_id'])) {
            $location_parameters = new LocationParameters();
            $values['location'] = $location_parameters->getValues($location);
        }

        // Add usertitle
        if ($usertitle = UserTitle::getById($fields['usertitles_id'])) {
            $usertitle_parameters = new UserTitleParameters();
            $values['usertitle'] = $usertitle_parameters->getValues($usertitle);
        }

        // Add usercategory
        if ($usercategory = UserCategory::getById($fields['usercategories_id'])) {
            $usercategory_parameters = new UserCategoryParameters();
            $values['usercategory'] = $usercategory_parameters->getValues($usercategory);
        }

        // Add assets
        $values['used_items'] = [];
        foreach ($CFG_GLPI["asset_types"] as $asset_type) {
            $item = \getItemForItemtype($asset_type);
            $asset_items_data = $item->find(['users_id' => $fields['id']] + $item->getSystemSQLCriteria());
            foreach ($asset_items_data as $asset_item_data) {
                $asset_parameters = new AssetParameters();
                if ($asset_item = $item::getById($asset_item_data['id'])) {
                    $values['used_items'][] = $asset_parameters->getValues($asset_item);
                }
            }
        }

        return $values;
    }
}

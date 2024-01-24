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

namespace Glpi\Asset\Capacity;

use Change;
use Change_Item;
use CommonGLPI;
use Item_Problem;
use Problem;
use Profile;
use Ticket;

class HasCommonITILObjectCapacity extends AbstractCapacity
{
    // #Override
    public function getLabel(): string
    {
        return __("Assistance");
    }

    // #Override
    public function getHelpText(): string
    {
        return __("Allow the asset to be associated with Tickets, Changes and Problems");
    }

    // #Override
    public function getConfigurationMessages(string $classname): array
    {
        $messages = [];

        $profiles_count = $this->countEnabledProfiles($classname);
        if ($profiles_count == 0) {
            $messages[] = [
                'type' => WARNING,
                'text' => __("This asset definition is not enabled on any profiles."),
                'link' => $this->getLinkToEnabledProfilesSearch($classname),

            ];
        } else {
            $messages[] = [
                'type' => INFO,
                'text' => sprintf(
                    __("This asset definition is enabled on %d profile(s)."),
                    $profiles_count
                ),
                'link' => $this->getLinkToEnabledProfilesSearch($classname),
            ];
        }

        return $messages;
    }

    // #Override
    public function onClassBootstrap(string $classname): void
    {
        // Allow the asset to be associated with Tickets, Changes and Problems
        $this->registerToTypeConfig('ticket_types', $classname);

        // Register the needed tabs into our item
        CommonGLPI::registerStandardTab(
            $classname,
            Ticket::class,
            51
        );
        CommonGLPI::registerStandardTab(
            $classname,
            Item_Problem::class,
            52
        );
        CommonGLPI::registerStandardTab(
            $classname,
            Change_Item::class,
            53
        );
    }

    // #Override
    public function onCapacityDisabled(string $classname): void
    {
        // Clear data from profiles (helpdesk_item_type)
        $profiles = (new Profile())->find([
            'helpdesk_item_type' => [
                'LIKE',
                "%" . $this->escapeAndEncodeClassName($classname) . "%"
            ]
        ]);
        foreach ($profiles as $row) {
            $itemtypes = importArrayFromDB($row["helpdesk_item_type"]);
            $itemtypes = array_keys(
                array_diff(
                    $itemtypes,
                    [$classname]
                )
            );

            (new Profile())->update([
                'id' => $row["id"],
                'helpdesk_item_type' => exportArrayToDB($itemtypes),
            ]);
        }

        // Clean display preferences
        $this->deleteDisplayPreferences(
            $classname,
            Ticket::rawSearchOptionsToAdd($classname)
        );
        $this->deleteDisplayPreferences(
            $classname,
            Problem::rawSearchOptionsToAdd($classname)
        );
        $this->deleteDisplayPreferences(
            $classname,
            Change::rawSearchOptionsToAdd($classname)
        );

        // Delete history
        $this->deleteRelationLogs($classname, Ticket::getType());
        $this->deleteRelationLogs($classname, Problem::getType());
        $this->deleteRelationLogs($classname, Change::getType());

        // Unregister from the "ticket types" config definition
        // Must be done after search options are cleaned /!\
        $this->unregisterFromTypeConfig('ticket_types', $classname);
    }

    /**
     * Count how many profiles have this asset definition enabled in their
     * helpdesk_item_type field.
     *
     * @param string $classname
     *
     * @return int
     */
    protected function countEnabledProfiles(string $classname): int
    {
        $classname = $this->escapeAndEncodeClassName($classname);

        return countElementsInTable(
            Profile::getTable(),
            [
                'helpdesk_item_type' => ['LIKE', "%{$classname}%"]
            ]
        );
    }

    /**
     * Get a link to the search page for profiles that have this asset
     * definition enabled in their helpdesk_item_type field.
     *
     * @param string $classname
     *
     * @return string
     */
    protected function getLinkToEnabledProfilesSearch(
        string $classname
    ): string {
        return Profile::getSearchURL() . "?" . http_build_query([
            'criteria' => [
                [
                    'link'       => 'AND',
                    'field'      => 87, //helpdesk_item_type
                    'searchtype' => 'contains',
                    'value'      =>  json_encode($classname),
                ],
            ],
        ]);
    }
}

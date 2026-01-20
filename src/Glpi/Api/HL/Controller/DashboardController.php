<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Api\HL\Controller;

use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Route;
use Glpi\Dashboard;

#[Route(path: '/Dashboards', priority: 1, tags: ['Dashboards'])]
#[Doc\Route(
    parameters: [
        new Doc\Parameter(
            name: 'dashboard_id',
            schema: new Doc\Schema(Doc\Schema::TYPE_INTEGER),
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'id',
            schema: new Doc\Schema(Doc\Schema::TYPE_INTEGER),
            location: Doc\Parameter::LOCATION_PATH,
        ),
    ]
)]
final class DashboardController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        global $DB;

        // always include core and mini_core contexts
        $known_contexts = ['core', 'mini_core'];
        $iterator = $DB->request([
            'SELECT'          => 'context',
            'DISTINCT'        => true,
            'FROM'            => Dashboard\Dashboard::getTable(),
        ]);
        foreach ($iterator as $data) {
            $context = $data['context'];
            if (!in_array($context, $known_contexts, true)) {
                $known_contexts[] = $context;
            }
        }

        $all_cards = (new Dashboard\Grid())->getAllDasboardCards();
        $all_widgets = [];
        foreach ($all_cards as $card) {
            if (isset($card['widgettype']) && is_array($card['widgettype'])) {
                $all_widgets = array_merge($all_widgets, $card['widgettype']);
            }
        }
        $all_widgets = array_unique(array_filter($all_widgets));

        $known_filters = array_map(static fn($f) => $f::getId(), Dashboard\Filter::getRegisteredFilterClasses());

        return [
            'Dashboard' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Dashboard\Dashboard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'readOnly' => true,
                    ],
                    'key' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'readOnly' => true,
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                    ],
                    'context' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Dashboard context which controls where it may be used',
                        'enum' => $known_contexts,
                    ],
                    'user' => self::getDropdownTypeSchema(class: \User::class, full_schema: 'User'),
                ],
            ],
            'DashboardFilter' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Dashboard\Filter::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'readOnly' => true,
                    ],
                    'dashboard' => self::getDropdownTypeSchema(class: Dashboard\Dashboard::class, full_schema: 'Dashboard'),
                    'user' => self::getDropdownTypeSchema(class: \User::class, full_schema: 'User'),
                    'filter' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'JSON encoded filters',
                    ],
                ],
            ],
            'DashboardItem' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Dashboard\Item::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'readOnly' => true,
                    ],
                    'dashboard' => self::getDropdownTypeSchema(class: Dashboard\Dashboard::class, full_schema: 'Dashboard'),
                    'unique_key' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-field' => 'gridstack_id',
                        'description' => 'Unique key of the item in the dashboard',
                    ],
                    'card' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-field' => 'card_id',
                        'description' => 'The card type of the dashboard item. Usually corresponds to a widget key and some extra information. For example, "bn_count_Computer" is big number widget showing the count of computers.',
                    ],
                    'x' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'X position of the item in the dashboard grid',
                    ],
                    'y' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Y position of the item in the dashboard grid',
                    ],
                    'width' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Width of the item in the dashboard grid',
                    ],
                    'height' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Height of the item in the dashboard grid',
                    ],
                    'card_options' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'JSON encoded options specific to the card type',
                    ],
                ],
            ],
            'DashboardRight' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Dashboard\Right::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'readOnly' => true,
                    ],
                    'dashboard' => self::getDropdownTypeSchema(class: Dashboard\Dashboard::class, full_schema: 'Dashboard'),
                    'itemtype' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Type of the item the right is granted to',
                        'enum' => [
                            \User::class,
                            \Group::class,
                            \Entity::class,
                            \Profile::class,
                        ],
                    ],
                    'items_id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'ID of the item the right is granted to',
                    ],
                ],
            ],
            'DashboardCard' => [
                'x-version-introduced' => '2.2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'card' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The card type key',
                    ],
                    'widget' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'List of widget types that can be used to display this card',
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                            'enum' => $all_widgets,
                        ],
                    ],
                    'group' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The localized group name this card belongs to',
                    ],
                    'itemtype' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The itemtype this card is related to, if any',
                    ],
                    'label' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The localized label of the card',
                    ],
                    'filters' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'List of filters applicable to this card',
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                            'enum' => $known_filters,
                        ],
                    ],
                ],
            ],
        ];
    }
}

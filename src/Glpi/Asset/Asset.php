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

namespace Glpi\Asset;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Glpi\CustomObject\CustomObjectTrait;
use Group_Item;
use Entity;
use Group;
use Location;
use Manufacturer;
use State;
use User;

abstract class Asset extends CommonDBTM
{
    use CustomObjectTrait;

    use \Glpi\Features\AssignableItem;
    use \Glpi\Features\Clonable;
    use \Glpi\Features\State;
    use \Glpi\Features\Inventoriable;

    /**
     * Asset definition.
     *
     * Must be defined here to make PHPStan happy (see https://github.com/phpstan/phpstan/issues/8808).
     * Must be defined by child class too to ensure that assigning a value to this property will affect
     * each child classe independently.
     */
    protected static AssetDefinition $definition;

    final public function __construct()
    {
        foreach (static::getDefinition()->getEnabledCapacities() as $capacity) {
            $capacity->onObjectInstanciation($this);
        }
    }

    /**
     * Get the asset definition related to concrete class.
     *
     * @return AssetDefinition
     */
    public static function getDefinition(): AssetDefinition
    {
        if (!(static::$definition instanceof AssetDefinition)) {
            throw new \RuntimeException('Asset definition is expected to be defined in concrete class.');
        }

        return static::$definition;
    }

    public static function getDefinitionClass(): string
    {
        return AssetDefinition::class;
    }

    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        $search_options = array_merge($search_options, Location::rawSearchOptionsToAdd());

        /** @var AssetModel $asset_model_class */
        $asset_model_class = $this->getDefinition()->getAssetModelClassName();
        /** @var AssetType $asset_type_class */
        $asset_type_class = $this->getDefinition()->getAssetTypeClassName();

        $search_options[] = [
            'id'            => '2',
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'massiveaction' => false,
            'datatype'      => 'number'
        ];

        $search_options[] = [
            'id'        => '4',
            'table'     => $asset_type_class::getTable(),
            'field'     => 'name',
            'name'      => $asset_type_class::getTypeName(1),
            'datatype'  => 'dropdown',
            // Search class could not be able to retrieve the concrete type class when using `getItemTypeForTable()`
            // so we have to define an `itemtype` here.
            'itemtype'  => $asset_type_class,
        ];

        $search_options[] = [
            'id'        => '40',
            'table'     => $asset_model_class::getTable(),
            'field'     => 'name',
            'name'      => $asset_model_class::getTypeName(1),
            'datatype'  => 'dropdown',
            // Search class could not be able to retrieve the concrete model class when using `getItemTypeForTable()`
            // so we have to define an `itemtype` here.
            'itemtype'  => $asset_model_class,
        ];

        $search_options[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $search_options[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'serial',
            'name'               => __('Serial number'),
            'datatype'           => 'string',
        ];

        $search_options[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string',
        ];

        $search_options[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $search_options[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'contact',
            'name'               => __('Alternate username'),
            'datatype'           => 'string',
        ];

        $search_options[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'contact_num',
            'name'               => __('Alternate username number'),
            'datatype'           => 'string',
        ];

        $search_options[] = [
            'id'                 => '70',
            'table'              => User::getTable(),
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all'
        ];

        $search_options[] = [
            'id'                 => '71',
            'table'              => Group::getTable(),
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_itemgroup' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_NORMAL]
                    ]
                ]
            ],
            'datatype'           => 'dropdown'
        ];

        $search_options[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $search_options[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];


        $search_options[] = [
            'id'                 => '23',
            'table'              => Manufacturer::getTable(),
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $search_options[] = [
            'id'                 => '24',
            'table'              => User::getTable(),
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge of the hardware'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket'
        ];

        $search_options[] = [
            'id'                 => '49',
            'table'              => Group::getTable(),
            'field'              => 'completename',
            'linkfield'          => 'groups_id_tech',
            'name'               => __('Group in charge of the hardware'),
            'condition'          => ['is_assign' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_NORMAL]
                    ]
                ]
            ],
            'datatype'           => 'dropdown'
        ];

        // TODO 65 for template

        $search_options[] = [
            'id'                 => '80',
            'table'              => Entity::getTable(),
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $search_options[] = [
            'id'                 => '250',
            'table'              => $this->getTable(),
            'field'              => AssetDefinition::getForeignKeyField(),
            'name'               => AssetDefinition::getTypeName(),
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        foreach (static::getDefinition()->getEnabledCapacities() as $capacity) {
            array_push($search_options, ...$capacity->getSearchOptions(static::class));
        }

        $search_options = $this->amendSearchOptions($search_options);

        return $search_options;
    }

    public function getUnallowedFieldsForUnicity()
    {
        $not_allowed = parent::getUnallowedFieldsForUnicity();
        $not_allowed[] = AssetDefinition::getForeignKeyField();
        return $not_allowed;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display(
            'pages/assets/asset.html.twig',
            [
                'item'   => $this,
                'params' => $options,
            ]
        );
        return true;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareGroupFields($input);

        return $this->prepareDefinitionInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareGroupFields($input);

        return $this->prepareDefinitionInput($input);
    }


    public function getCloneRelations(): array
    {
        $relations = [];
        $capacities = static::getDefinition()->getEnabledCapacities();
        foreach ($capacities as $capacity) {
            $relations = [...$relations, ...$capacity->getCloneRelations()];
        }
        return array_unique($relations);
    }
}

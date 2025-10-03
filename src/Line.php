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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Features\StateInterface;

/**
 * @since 9.2
 */


class Line extends CommonDBTM implements AssignableItemInterface, StateInterface
{
    use Glpi\Features\State;
    use AssignableItem;

    // From CommonDBTM
    public $dohistory                   = true;

    public static $rightname                   = 'line';
    protected $usenotepad               = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Phone line', 'Phone lines', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'financial';
    }

    /**
     * @see CommonDBTM::useDeletedToLockIfDynamic()
     *
     * @since 0.84
     **/
    public function useDeletedToLockIfDynamic()
    {
        return false;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(Item_Line::class, $ong, $options);
        $this->addStandardTab(Infocom::class, $ong, $options);
        $this->addStandardTab(Contract_Item::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }


    /**
     * Print the line form
     *
     * @param $ID integer ID of the item
     * @param $options array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return boolean item found
     **/
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/management/line.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_linetypes',
            'field'              => 'name',
            'name'               => LineType::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_itemgroup' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_NORMAL],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '184',
            'table'              => 'glpi_lineoperators',
            'field'              => 'name',
            'name'               => LineOperator::getTypeName(1),
            'massiveaction'      => true,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '185',
            'table'              => $this->getTable(),
            'field'              => 'caller_num',
            'name'               => __('Caller number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '186',
            'table'              => $this->getTable(),
            'field'              => 'caller_name',
            'name'               => __('Caller name'),
            'datatype'           => 'string',
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "ti ti-phone-calling";
    }

    public static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = false, ?CommonDBTM $checkitem = null)
    {
        global $CFG_GLPI;

        parent::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);

        $action_prefix = 'Item_Line' . MassiveAction::CLASS_ACTION_SEPARATOR;
        if (in_array($itemtype, $CFG_GLPI['line_types'], true)) {
            $actions[$action_prefix . 'add']    = "<i class='" . htmlescape(self::getIcon()) . "'></i>"
                . _sx('button', 'Add a phone line');
            $actions[$action_prefix . 'remove'] = _sx('button', 'Remove a phone line');
        }
        if ((is_a($itemtype, self::class, true)) && (static::canUpdate())) {
            $actions[$action_prefix . 'add_item']    = _sx('button', 'Add an item');
            $actions[$action_prefix . 'remove_item'] = _sx('button', 'Remove an item');
        }
    }
}

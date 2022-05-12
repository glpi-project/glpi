<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/**
 *  Locked fields for inventory
 **/
class Lockedfield extends CommonDBTM
{
    /** @var CommonDBTM */
    private $item;

   // From CommonDBTM
    public $dohistory                   = false;

    public static $rightname                   = 'config';

    public static function getTypeName($nb = 0)
    {
        return _n('Locked field', 'Locked fields', $nb);
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => 1,
            'table'              => $this->getTable(),
            'field'              => 'field',
            'name'               => __('Field name'),
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => __('Item type'),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'date'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'value',
            'name'               => __('Last inventoried value'),
            'nosort'             => true,
            'nosearch'           => true,
        ];

        $tab[] = [
            'id' => '7',
            'table' => $this->getTable(),
            'field' => 'is_global',
            'name' => __('Global'),
            'datatype' => 'bool',
            'massiveaction' => false
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => _n('Associated element', 'Associated elements', 1),
            'datatype'           => 'specific',
            'comments'           => true,
            'nosort'             => true,
            'nosearch'           => true,
            'additionalfields'   => ['itemtype'],
            'joinparams'         => [
                'jointype'           => 'child'
            ],
            'forcegroupby'       => true,
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "ti ti-lock";
    }

    /**
     * Can item have locked fields
     *
     * @param CommonDBTM $item Item instance
     *
     * @retrun boolean
     */
    public function isHandled(CommonGLPI $item)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        $this->item = $item;
        return (bool)$item->isDynamic();
    }

    /**
     * Get locked fields
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     *
     * return array
     */
    public function getLocks($itemtype, $items_id)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                [
                    'OR' => [
                        'items_id'  => $items_id,
                        'is_global' => 1
                    ]
                ]
            ]
        ]);

        $locks = [];
        foreach ($iterator as $row) {
            $locks[] = $row['field'];
        }
        return $locks;
    }

    /**
     * Item has been deleted, remove all locks
     *
     * @return boolean
     */
    public function itemDeleted()
    {
        global $DB;
        return $DB->delete(
            $this->getTable(),
            [
                'itemtype'  => $this->item->getType(),
                'items_id'  => $this->item->fields['id']
            ]
        );
    }

    /**
     * Store value from inventory on locked fields
     *
     * @return boolean
     */
    public function setLastValue($itemtype, $items_id, $field, $value)
    {
        global $DB;
        return $DB->update(
            $this->getTable(),
            [
                'value'  => $value
            ],
            [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
                'field'     => $field
            ]
        );
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'items_id':
                if (isset($values['items_id']) && !$values['items_id']) {
                    return '-';
                }
                if (isset($values['itemtype'])) {
                    $itemtype = $values['itemtype'];
                    $item = new $itemtype();
                    $item->getFromDB($values['items_id']);
                    return $item->getLink(['comments' => $options['comments'] ?? false]);
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public function getForbiddenStandardMassiveAction()
    {
        return ['update', 'clone'];
    }

    public static function canPurge()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

    protected function prepareInput($input)
    {
        if (isset($input['item'])) {
            list($itemtype, $field) = explode(' - ', $input['item']);
            $input['itemtype'] = $itemtype;
            $input['items_id'] = 0;
            $input['field'] = $field;
            $input['is_global'] = 1;
        }

        return $input;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        unset($this->fields['is_global']);
        TemplateRenderer::getInstance()->display('pages/admin/inventory/lockedfield.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }

    public static function canCreate()
    {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, UPDATE);
        }
        return false;
    }

    /**
     * List of itemtypes/fields that can be locked globally
     *
     * @return array
     */
    public function getFieldsToLock(): array
    {
        global $CFG_GLPI, $DB;

        $lockable = [];
        $std_fields = [
            'name',
            'serial',
            'otherserial',
            'contact',
            'contactnum',
            'users_id_tech',
            'groups_id_tech',
            'users_id',
            'groups_id',
            'states_id',
            'locations_id',
            'networks_id',
            'manufacturers_id',
            'uuid'
        ];
        $itemtypes = $CFG_GLPI['inventory_types'] + $CFG_GLPI['inventory_lockable_objects'];

        foreach ($itemtypes as $itemtype) {
            $fields = $std_fields;
            $fields[] = strtolower($itemtype) . 'models_id'; //model relation field
            $fields[] = strtolower($itemtype) . 'types_id'; //type relation field

            foreach ($fields as $field) {
                if ($DB->fieldExists($itemtype::getTable(), $field)) {
                    $name = sprintf(
                        '%1$s - %2$s',
                        $itemtype,
                        $field
                    );
                    $dname = sprintf(
                        '%1$s - %2$s',
                        $itemtype::getTypeName(1),
                        $field
                    );

                    $lockable[$name] = $dname;
                }
            }
        }

        return $lockable;
    }
}
